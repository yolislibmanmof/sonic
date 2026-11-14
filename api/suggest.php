<?php
/**
 * SONIC OPAC — Suggest Endpoint (BATCH 5 + BATCH 8: rate-limit + ETag)
 * Item judul membawa id + cover + author + year untuk dropdown rich.
 * Bentuk lama {t,v} tetap ada → JS lama kompatibel.
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('X-Content-Type-Options: nosniff');

/* BATCH 8: rate-limit + ETag (graceful bila _platform.php belum ada) */
if (is_file(__DIR__ . '/../parts/_platform.php')) {
    require_once __DIR__ . '/../parts/_platform.php';
    if (function_exists('sonic_api_rate')) sonic_api_rate('suggest', 120, 60);
}

function sonic_sug_cover($img) {
    $p = (!empty($img)) ? 'images/docs/' . $img : 'images/default/image.png';
    return SWB . 'lib/minigalnano/createthumb.php?filename=' . urlencode($p) . '&width=96';
}

$out = [];
try {
    $q = trim((string)($_GET['q'] ?? ''));
    if (isset($dbs) && is_object($dbs) && mb_strlen($q) >= 2) {
        $q = mb_substr($q, 0, 60);
        $esc = $dbs->real_escape_string($q);
        $esc = str_replace(['%', '_'], ['\\%', '\\_'], $esc); // BATCH 4: netralkan wildcard LIKE
        $pref = $esc . '%';
        $like = '%' . $esc . '%';

        $cols = "b.biblio_id, b.title, b.image, b.publish_year,
            (SELECT GROUP_CONCAT(ma.author_name SEPARATOR ' · ') FROM biblio_author ba JOIN mst_author ma ON ba.author_id=ma.author_id WHERE ba.biblio_id=b.biblio_id) AS author";

        $words = trim(preg_replace('/[+\-><()~*\"@]+/', ' ', $q));
        $rows = false;
        if ($words !== '') {
            $bool = '+' . preg_replace('/\s+/', '* +', $words) . '*';
            $bool = $dbs->real_escape_string($bool);
            try {
                $rows = $dbs->query("SELECT DISTINCT {$cols} FROM biblio b WHERE MATCH(b.title) AGAINST('{$bool}' IN BOOLEAN MODE) LIMIT 5");
            } catch (Throwable $e) { $rows = false; }
        }
        if (!$rows || $rows->num_rows === 0) {
            $rows = $dbs->query("SELECT DISTINCT {$cols} FROM biblio b WHERE b.title LIKE '{$pref}' ORDER BY CHAR_LENGTH(b.title) ASC LIMIT 5");
        }
        if (!$rows || $rows->num_rows === 0) {
            $rows = $dbs->query("SELECT DISTINCT {$cols} FROM biblio b WHERE b.title LIKE '{$like}' ORDER BY (b.title LIKE '{$pref}') DESC, CHAR_LENGTH(b.title) ASC LIMIT 5");
        }
        if ($rows) { while ($r = $rows->fetch_assoc()) {
            $out[] = [
                't' => 'title', 'v' => (string)$r['title'],
                'id' => (int)$r['biblio_id'],
                'cover' => sonic_sug_cover($r['image'] ?? ''),
                'author' => (string)($r['author'] ?? ''),
                'year' => (string)($r['publish_year'] ?? ''),
            ];
        } }

        $r = $dbs->query("SELECT author_name FROM mst_author WHERE author_name LIKE '{$pref}' OR author_name LIKE '{$like}' ORDER BY (author_name LIKE '{$pref}') DESC, author_name ASC LIMIT 3");
        if ($r) { while ($row = $r->fetch_assoc()) { $out[] = ['t' => 'author', 'v' => $row['author_name']]; } }

        $r = $dbs->query("SELECT topic FROM mst_topic WHERE topic LIKE '{$pref}' OR topic LIKE '{$like}' ORDER BY (topic LIKE '{$pref}') DESC, topic ASC LIMIT 3");
        if ($r) { while ($row = $r->fetch_assoc()) { $out[] = ['t' => 'subject', 'v' => $row['topic']]; } }
    }
} catch (Throwable $e) { $out = []; }

$json = json_encode(array_slice($out, 0, 9), JSON_UNESCAPED_UNICODE);
if (function_exists('sonic_etag_send')) { sonic_etag_send($json, 'application/json; charset=utf-8', 60); } else { echo $json; }