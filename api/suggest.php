<?php
/**
 * SONIC OPAC — Suggest Endpoint (Fase 2: FULLTEXT bila ada, fallback prefix/LIKE).
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

$out = [];
try {
    $q = trim((string)($_GET['q'] ?? ''));
    if (isset($dbs) && is_object($dbs) && mb_strlen($q) >= 2) {
        $q = mb_substr($q, 0, 60);
        $esc = $dbs->real_escape_string($q);
        $pref = $esc . '%';
        $like = '%' . $esc . '%';

        // Judul: coba FULLTEXT (boolean) dulu; gagal → prefix; gagal → LIKE.
        $rows = false;
        try {
            $bool = '+' . preg_replace('/\s+/', '* +', trim($esc)) . '*';
            $rows = $dbs->query("SELECT title FROM biblio WHERE MATCH(title) AGAINST('{$bool}' IN BOOLEAN MODE) LIMIT 5");
        } catch (Throwable $e) { $rows = false; }
        if (!$rows || $rows->num_rows === 0) {
            $rows = $dbs->query("SELECT title FROM biblio WHERE title LIKE '{$pref}' ORDER BY CHAR_LENGTH(title) ASC LIMIT 5");
        }
        if (!$rows || $rows->num_rows === 0) {
            $rows = $dbs->query("SELECT title FROM biblio WHERE title LIKE '{$like}' ORDER BY (title LIKE '{$pref}') DESC, CHAR_LENGTH(title) ASC LIMIT 5");
        }
        if ($rows) { while ($r = $rows->fetch_assoc()) { $out[] = ['t' => 'title', 'v' => $r['title']]; } }

        $r = $dbs->query("SELECT author_name FROM mst_author WHERE author_name LIKE '{$pref}' OR author_name LIKE '{$like}' ORDER BY (author_name LIKE '{$pref}') DESC, author_name ASC LIMIT 3");
        if ($r) { while ($row = $r->fetch_assoc()) { $out[] = ['t' => 'author', 'v' => $row['author_name']]; } }

        $r = $dbs->query("SELECT topic FROM mst_topic WHERE topic LIKE '{$pref}' OR topic LIKE '{$like}' ORDER BY (topic LIKE '{$pref}') DESC, topic ASC LIMIT 3");
        if ($r) { while ($row = $r->fetch_assoc()) { $out[] = ['t' => 'subject', 'v' => $row['topic']]; } }
    }
} catch (Throwable $e) { $out = []; }

echo json_encode(array_slice($out, 0, 9));