<?php
/**
 * SONIC OPAC — Discovery Endpoint (BATCH 5 + BATCH 8: rate-limit + ETag)
 * action=dym | shelf | coloan. Guard try/catch, cache TTL, toggle Kustomisasi.
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('X-Content-Type-Options: nosniff');

/* BATCH 8: rate-limit + ETag (graceful) */
if (is_file(__DIR__ . '/../parts/_platform.php')) {
    require_once __DIR__ . '/../parts/_platform.php';
    if (function_exists('sonic_api_rate')) sonic_api_rate('discovery', 60, 60);
}

if (!function_exists('sonic_disc_cache')) {
    function sonic_disc_cache($key, $ttl, $callback) {
        static $mem = [];
        if (array_key_exists($key, $mem)) return $mem[$key];
        $dir = sys_get_temp_dir() . '/sonic_cache';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/' . md5((defined('SB') ? SB : '') . 'disc_' . $key) . '.json';
        if (is_file($file) && (time() - (int)@filemtime($file)) < $ttl) {
            $d = json_decode((string)@file_get_contents($file), true);
            if ($d !== null) return $mem[$key] = $d;
        }
        $data = $callback();
        if ($data !== null) {
            @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
            $mem[$key] = $data;
        }
        return $data;
    }
}
function sonic_disc_cover($img) {
    $p = (!empty($img)) ? 'images/docs/' . $img : 'images/default/image.png';
    return SWB . 'lib/minigalnano/createthumb.php?filename=' . urlencode($p) . '&width=240';
}
function sonic_disc_on($key) {
    global $sysconf;
    return (int)($sysconf['template'][$key] ?? 1) === 1;
}

$action = (string)($_GET['action'] ?? '');
$out = [];
try {
    if (isset($dbs) && is_object($dbs)) {

        /* ================= DID-YOU-MEAN ================= */
        if ($action === 'dym') {
            $q = trim((string)($_GET['q'] ?? ''));
            $q = mb_substr($q, 0, 60);
            if (mb_strlen($q) >= 2 && sonic_disc_on('sonic_dym_fuzzy')) {
                $out = sonic_disc_cache('dym_' . md5($q), 600, function () use ($dbs, $q) {
                    $res = ['original' => $q, 'suggestions' => [], 'source' => 'none'];
                    $hasIndex = false;
                    try {
                        $t = $dbs->query("SHOW TABLES LIKE 'index_words'");
                        if ($t && $t->num_rows > 0) {
                            $c = $dbs->query('SELECT COUNT(*) FROM index_words');
                            $hasIndex = $c && (int)$c->fetch_row()[0] > 0;
                        }
                    } catch (Throwable $e) { $hasIndex = false; }

                    $words = preg_split('/\s+/', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY);
                    $corrected = []; $changed = false;
                    if ($hasIndex && $words) {
                        foreach ($words as $w) {
                            $len = mb_strlen($w);
                            if ($len < 3 || !preg_match('/^[a-z0-9]+$/i', $w)) { $corrected[] = $w; continue; }
                            $escw = $dbs->real_escape_string($w);
                            $exact = $dbs->query("SELECT word FROM index_words WHERE word='{$escw}' LIMIT 1");
                            if ($exact && $exact->num_rows > 0) { $corrected[] = $w; continue; }
                            $first = $dbs->real_escape_string(mb_substr($w, 0, 1));
                            $lo = max(2, $len - 2); $hi = $len + 2;
                            $maxd = ($len <= 4) ? 1 : (($len <= 7) ? 2 : 3);
                            $best = null; $bestD = 99; $bestHits = -1;
                            try {
                                $cand = $dbs->query("SELECT word, doc_hits FROM index_words WHERE word LIKE '{$first}%' AND CHAR_LENGTH(word) BETWEEN {$lo} AND {$hi} ORDER BY doc_hits DESC LIMIT 300");
                                if ($cand) while ($r = $cand->fetch_assoc()) {
                                    $cw = (string)$r['word'];
                                    $d = levenshtein($w, $cw);
                                    if ($d > $maxd) {
                                        if (!(soundex($w) === soundex($cw) && $d <= $maxd + 1)) continue;
                                        $d = $maxd;
                                    }
                                    $hits = (int)$r['doc_hits'];
                                    if ($d < $bestD || ($d === $bestD && $hits > $bestHits)) { $best = $cw; $bestD = $d; $bestHits = $hits; }
                                }
                            } catch (Throwable $e) {}
                            if ($best !== null && $best !== $w) { $corrected[] = $best; $changed = true; }
                            else { $corrected[] = $w; }
                        }
                    }
                    if ($changed) {
                        $phrase = implode(' ', $corrected);
                        if (mb_strtolower($phrase) !== mb_strtolower($q)) {
                            $res['suggestions'][] = $phrase;
                            $res['source'] = 'fuzzy';
                        }
                    }
                    if (count($res['suggestions']) === 0) {
                        $esc = $dbs->real_escape_string($q);
                        $esc = str_replace(['%', '_'], ['\\%', '\\_'], $esc);
                        try {
                            $r = $dbs->query("SELECT DISTINCT title FROM biblio WHERE title LIKE '%{$esc}%' ORDER BY CHAR_LENGTH(title) ASC LIMIT 3");
                            if ($r) while ($row = $r->fetch_assoc()) {
                                $tv = trim((string)$row['title']);
                                if ($tv !== '' && mb_strtolower($tv) !== mb_strtolower($q)) $res['suggestions'][] = $tv;
                            }
                            if ($res['suggestions']) $res['source'] = 'like';
                        } catch (Throwable $e) {}
                    }
                    $res['suggestions'] = array_values(array_unique(array_slice($res['suggestions'], 0, 5)));
                    return $res;
                });
            }

        /* ================= VIRTUAL SHELF ================= */
        } elseif ($action === 'shelf') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id > 0 && sonic_disc_on('sonic_virtual_shelf')) {
                $n = min(12, max(2, (int)($sysconf['template']['sonic_shelf_limit'] ?? 8)));
                $out = sonic_disc_cache("shelf_{$id}_{$n}", 3600, function () use ($dbs, $id, $n) {
                    $res = ['current' => null, 'before' => [], 'after' => [], 'key' => '', 'prefix' => ''];
                    $r = $dbs->query('SELECT biblio_id, title, image, call_number, classification, publish_year FROM biblio WHERE biblio_id=' . $id);
                    if (!$r || !($cur = $r->fetch_assoc())) return $res;
                    $cn = trim((string)$cur['call_number']);
                    $cl = trim((string)$cur['classification']);
                    $keyCol = ($cn !== '') ? 'call_number' : 'classification';
                    $keyVal = ($cn !== '') ? $cn : $cl;
                    if ($keyVal === '') return $res;
                    $prefix = ($cl !== '') ? substr($cl, 0, 3) : substr($keyVal, 0, 3);
                    $ek = $dbs->real_escape_string($keyVal);
                    $ep = $dbs->real_escape_string($prefix);
                    $scope = ($prefix !== '') ? " AND classification LIKE '{$ep}%'" : '';
                    $cols = 'biblio_id, title, image, call_number, publish_year';
                    $fetch = function ($sql) use ($dbs) {
                        $rows = [];
                        try { $q = $dbs->query($sql); if ($q) while ($x = $q->fetch_assoc()) $rows[] = [
                            'id' => (int)$x['biblio_id'], 'title' => (string)$x['title'],
                            'cover' => sonic_disc_cover($x['image'] ?? ''), 'call_number' => (string)($x['call_number'] ?? ''),
                            'year' => (string)($x['publish_year'] ?? ''),
                        ]; } catch (Throwable $e) {}
                        return $rows;
                    };
                    $before = $fetch("SELECT {$cols} FROM biblio WHERE biblio_id != {$id} AND {$keyCol} <> '' AND {$keyCol} <= '{$ek}'{$scope} ORDER BY {$keyCol} DESC, biblio_id DESC LIMIT {$n}");
                    $after  = $fetch("SELECT {$cols} FROM biblio WHERE biblio_id != {$id} AND {$keyCol} <> '' AND {$keyCol} > '{$ek}'{$scope} ORDER BY {$keyCol} ASC, biblio_id ASC LIMIT {$n}");
                    if (count($before) + count($after) < 2 && $scope !== '') {
                        $before = $fetch("SELECT {$cols} FROM biblio WHERE biblio_id != {$id} AND {$keyCol} <> '' AND {$keyCol} <= '{$ek}' ORDER BY {$keyCol} DESC, biblio_id DESC LIMIT {$n}");
                        $after  = $fetch("SELECT {$cols} FROM biblio WHERE biblio_id != {$id} AND {$keyCol} <> '' AND {$keyCol} > '{$ek}' ORDER BY {$keyCol} ASC, biblio_id ASC LIMIT {$n}");
                    }
                    $res['before'] = array_reverse($before);
                    $res['after'] = $after;
                    $res['key'] = $keyVal; $res['prefix'] = $prefix;
                    $res['current'] = ['id' => $id, 'title' => (string)$cur['title'], 'cover' => sonic_disc_cover($cur['image'] ?? ''), 'call_number' => $cn !== '' ? $cn : $cl, 'year' => (string)($cur['publish_year'] ?? '')];
                    return $res;
                });
            }

        /* ================= CO-LOAN ================= */
        } elseif ($action === 'coloan') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id > 0 && sonic_disc_on('sonic_coloan')) {
                $n = min(12, max(2, (int)($sysconf['template']['sonic_coloan_limit'] ?? 8)));
                $out = sonic_disc_cache("coloan_{$id}_{$n}", 3600, function () use ($dbs, $id, $n) {
                    $rows = [];
                    try {
                        $sql = "SELECT b.biblio_id, b.title, b.image, b.publish_year,
                                COUNT(DISTINCT l2.member_id) AS score,
                                (SELECT GROUP_CONCAT(ma.author_name SEPARATOR ' · ') FROM biblio_author ba JOIN mst_author ma ON ba.author_id=ma.author_id WHERE ba.biblio_id=b.biblio_id) AS author
                            FROM loan l1
                            INNER JOIN item i1 ON l1.item_code = i1.item_code
                            INNER JOIN loan l2 ON l2.member_id = l1.member_id
                            INNER JOIN item i2 ON l2.item_code = i2.item_code
                            INNER JOIN biblio b ON i2.biblio_id = b.biblio_id
                            WHERE i1.biblio_id = {$id} AND i2.biblio_id <> {$id} AND b.title IS NOT NULL
                            GROUP BY b.biblio_id, b.title, b.image, b.publish_year
                            ORDER BY score DESC, b.publish_year DESC LIMIT {$n}";
                        $q = $dbs->query($sql);
                        if ($q) while ($x = $q->fetch_assoc()) $rows[] = [
                            'id' => (int)$x['biblio_id'], 'title' => (string)$x['title'],
                            'cover' => sonic_disc_cover($x['image'] ?? ''), 'author' => (string)($x['author'] ?? ''),
                            'year' => (string)($x['publish_year'] ?? ''), 'score' => (int)$x['score'],
                        ];
                    } catch (Throwable $e) {}
                    return $rows;
                });
            }
        }
    }
} catch (Throwable $e) { $out = []; }

$json = json_encode($out, JSON_UNESCAPED_UNICODE);
if (function_exists('sonic_etag_send')) { sonic_etag_send($json, 'application/json; charset=utf-8', 300); } else { echo $json; }