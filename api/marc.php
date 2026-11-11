<?php
/**
 * SONIC OPAC — MARC21 (ISO 2709) exporter mandiri.
 * Menggantikan endpoint core yang 500 di PHP 8. Read-only, output application/marc.
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';

header('Content-Type: application/marc; charset=utf-8');
header('Cache-Control: no-store');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 || !isset($dbs) || !is_object($dbs)) { http_response_code(400); exit('bad id'); }

$FT = chr(30); $RT = chr(29); $SF = chr(31);
$clean = function ($s) { return str_replace([chr(29), chr(30), chr(31)], '', (string)$s); };

/* ---- Ambil data ---- */
$b = null;
try {
    $q = $dbs->query('SELECT * FROM biblio WHERE biblio_id=' . $id);
    if ($q) $b = $q->fetch_assoc();
} catch (Throwable $e) {}
if (!$b) { http_response_code(404); exit('not found'); }

$authors = []; $topics = []; $locs = [];
try {
    $q = $dbs->query('SELECT a.author_name FROM biblio_author ba JOIN mst_author a ON ba.author_id=a.author_id WHERE ba.biblio_id=' . $id . ' ORDER BY ba.level ASC');
    if ($q) while ($r = $q->fetch_row()) $authors[] = $r[0];
    $q = $dbs->query('SELECT t.topic FROM biblio_topic bt JOIN mst_topic t ON bt.topic_id=t.topic_id WHERE bt.biblio_id=' . $id);
    if ($q) while ($r = $q->fetch_row()) $topics[] = $r[0];
    $q = $dbs->query('SELECT l.location_name FROM item i JOIN mst_location l ON i.location_id=l.location_id WHERE i.biblio_id=' . $id . ' LIMIT 3');
    if ($q) while ($r = $q->fetch_row()) $locs[] = $r[0];
} catch (Throwable $e) {}

/* ---- Builder field ---- */
$fields = [];
$ctl = function ($tag, $val) use (&$fields, $FT, $clean) { $fields[] = ['tag' => $tag, 'data' => $clean($val) . $FT]; };
$fld = function ($tag, $i1, $i2, $subs) use (&$fields, $FT, $SF, $clean) {
    $d = $i1 . $i2;
    foreach ($subs as $s) { if ($s[1] === '' || $s[1] === null) continue; $d .= $SF . $s[0] . $clean($s[1]); }
    $fields[] = ['tag' => $tag, 'data' => $d . $FT];
};

$year = substr(preg_replace('/\D/', '', $b['publish_year'] ?? ''), 0, 4);
$year = str_pad($year, 4, ' ');
$entered = !empty($b['input_date']) ? date('ymd', strtotime($b['input_date'])) : date('ymd');
$lang = substr(preg_replace('/[^a-z]/', '', strtolower($b['language'] ?? '')), 0, 3);
$lang = str_pad($lang ?: '   ', 3, ' ');
$upd = !empty($b['last_update']) ? date('YmdHis', strtotime($b['last_update'])) . '.0' : date('YmdHis') . '.0';

$ctl('001', (string)$id);
$ctl('003', 'SLiMS');
$ctl('005', $upd);
$ctl('008', $entered . 's' . $year . '    xx ' . str_repeat('|', 17) . $lang . '||');
if (!empty($b['isbn_issn'])) $fld('020', ' ', ' ', [['a', $b['isbn_issn']]]);
$fld('040', ' ', ' ', [['a', 'SLiMS'], ['c', 'SLiMS']]);
if (!empty($b['call_number'])) $fld('082', '0', '4', [['a', $b['call_number']]]);
if (!empty($authors[0])) $fld('100', '1', ' ', [['a', $authors[0]]]);
$fld('245', '1', '0', [['a', $b['title'] ?? ''], ['c', $b['sor'] ?? '']]);
if (!empty($b['edition'])) $fld('250', ' ', ' ', [['a', $b['edition']]]);
$fld('260', ' ', ' ', [['a', $b['publish_place'] ?? ''], ['b', $b['publisher_name'] ?? ''], ['c', $year]]);
if (!empty($b['collation'])) $fld('300', ' ', ' ', [['a', $b['collation']]]);
if (!empty($b['series_title'])) $fld('490', '0', ' ', [['a', $b['series_title']]]);
foreach ($topics as $t) $fld('650', ' ', '4', [['a', $t]]);
for ($i = 1; $i < count($authors); $i++) $fld('700', '1', ' ', [['a', $authors[$i]]]);
foreach ($locs as $l) $fld('852', ' ', ' ', [['a', $l], ['j', $b['call_number'] ?? '']]);

/* ---- Rakit ISO 2709 ---- */
$dir = ''; $data = ''; $offset = 0;
foreach ($fields as $f) {
    $len = strlen($f['data']);
    $dir .= $f['tag'] . sprintf('%04d', $len) . sprintf('%05d', $offset);
    $data .= $f['data'];
    $offset += $len;
}
$dir .= $FT;
$base = 24 + strlen($dir);
$total = $base + strlen($data) + 1;
$leader = sprintf('%05d', $total) . 'nam a22' . sprintf('%05d', $base) . ' a 4500';

header('Content-Disposition: attachment; filename=biblio-' . $id . '.mrc');
echo $leader . $dir . $data . $RT;
exit;