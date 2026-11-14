<?php
/**
 * SONIC OPAC — Facet Counts (BATCH 1 + BATCH 8: rate-limit + ETag, cache 120s)
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');
header('X-Content-Type-Options: nosniff');

/* BATCH 8: rate-limit + ETag (graceful) */
if (is_file(__DIR__ . '/../parts/_platform.php')) {
    require_once __DIR__ . '/../parts/_platform.php';
    if (function_exists('sonic_api_rate')) sonic_api_rate('facet', 30, 60);
}

$cacheFile = sys_get_temp_dir() . '/sonic_facet_' . md5(defined('SB') ? SB : __DIR__) . '.json';
if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile)) < 120) {
    $cached = (string)@file_get_contents($cacheFile);
    if (function_exists('sonic_etag_send')) { sonic_etag_send($cached, 'application/json; charset=utf-8', 120); } else { echo $cached; exit; }
}

$out = ['gmd' => [], 'location' => [], 'colltype' => []];
try {
    if (isset($dbs) && is_object($dbs)) {
        $r = $dbs->query('SELECT g.gmd_name AS n, COUNT(b.biblio_id) AS c FROM mst_gmd g LEFT JOIN biblio b ON b.gmd_id=g.gmd_id GROUP BY g.gmd_name');
        if ($r) { while ($row = $r->fetch_assoc()) { $out['gmd'][$row['n']] = (int)$row['c']; } }

        $r = $dbs->query('SELECT l.location_name AS n, COUNT(i.item_code) AS c FROM mst_location l LEFT JOIN item i ON i.location_id=l.location_id GROUP BY l.location_name');
        if ($r) { while ($row = $r->fetch_assoc()) { $out['location'][$row['n']] = (int)$row['c']; } }

        $r = $dbs->query('SELECT ct.coll_type_name AS n, COUNT(i.item_code) AS c FROM mst_coll_type ct LEFT JOIN item i ON i.coll_type_id=ct.coll_type_id GROUP BY ct.coll_type_name');
        if ($r) { while ($row = $r->fetch_assoc()) { $out['colltype'][$row['n']] = (int)$row['c']; } }
    }
} catch (Throwable $e) { /* silent */ }

$json = json_encode($out);
@file_put_contents($cacheFile, $json, LOCK_EX);
if (function_exists('sonic_etag_send')) { sonic_etag_send($json, 'application/json; charset=utf-8', 120); } else { echo $json; }