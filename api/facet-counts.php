<?php
/**
 * SONIC OPAC — Facet Counts (distribusi katalog untuk badge jumlah)
 * Read-only, output JSON murni.
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, must-revalidate');

$out = ['gmd' => [], 'location' => [], 'colltype' => []];
try {
    if (isset($dbs) && is_object($dbs)) {
        $r = $dbs->query('SELECT g.gmd_name AS n, COUNT(b.biblio_id) AS c FROM mst_gmd g LEFT JOIN biblio b ON b.gmd_id=g.gmd_id GROUP BY g.gmd_name');
        if ($r) { while ($row = $r->fetch_assoc()) { $out['gmd'][$row['n']] = (int)$row['c']; } }

        $r = $dbs->query('SELECT l.location AS n, COUNT(i.item_code) AS c FROM mst_location l LEFT JOIN item i ON i.location_id=l.location_id GROUP BY l.location');
        if ($r) { while ($row = $r->fetch_assoc()) { $out['location'][$row['n']] = (int)$row['c']; } }

        $r = $dbs->query('SELECT ct.coll_type_name AS n, COUNT(i.item_code) AS c FROM mst_coll_type ct LEFT JOIN item i ON i.coll_type_id=ct.coll_type_id GROUP BY ct.coll_type_name');
        if ($r) { while ($row = $r->fetch_assoc()) { $out['colltype'][$row['n']] = (int)$row['c']; } }
    }
} catch (Throwable $e) { /* silent */ }

echo json_encode($out);