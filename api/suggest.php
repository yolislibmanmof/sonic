<?php
/**
 * SONIC OPAC — Suggest Endpoint (Smart Autocomplete)
 * Bootstrap SLiMS minimal, output JSON murni.
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, must-revalidate');

$out = [];
try {
    $q = trim((string)($_GET['q'] ?? ''));
    if (isset($dbs) && is_object($dbs) && mb_strlen($q) >= 2) {
        $q = mb_substr($q, 0, 60);
        $like = '%' . $dbs->real_escape_string($q) . '%';
        $pref = $dbs->real_escape_string($q) . '%';

        // Judul (prefix diprioritaskan, lalu yang terpendek)
        $r = $dbs->query("SELECT title FROM biblio WHERE title LIKE '{$like}'
                          ORDER BY (title LIKE '{$pref}') DESC, CHAR_LENGTH(title) ASC LIMIT 5");
        if ($r) { while ($row = $r->fetch_assoc()) { $out[] = ['t' => 'title', 'v' => $row['title']]; } }

        // Pengarang
        $r = $dbs->query("SELECT author_name FROM mst_author WHERE author_name LIKE '{$like}'
                          ORDER BY (author_name LIKE '{$pref}') DESC, author_name ASC LIMIT 3");
        if ($r) { while ($row = $r->fetch_assoc()) { $out[] = ['t' => 'author', 'v' => $row['author_name']]; } }

        // Subjek
        $r = $dbs->query("SELECT topic FROM mst_topic WHERE topic LIKE '{$like}'
                          ORDER BY (topic LIKE '{$pref}') DESC, topic ASC LIMIT 3");
        if ($r) { while ($row = $r->fetch_assoc()) { $out[] = ['t' => 'subject', 'v' => $row['topic']]; } }
    }
} catch (Throwable $e) { $out = []; }

echo json_encode(array_slice($out, 0, 9));