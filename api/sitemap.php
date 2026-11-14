<?php
/** SONIC BATCH 8 — sitemap.xml dinamis (biblio + halaman publik). Cache 1 jam + ETag. */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';
require_once __DIR__ . '/../parts/_platform.php';
sonic_api_rate('sitemap', 10, 60);

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$base = $scheme . '://' . $host . SWB;

$body = sonic_cache('sitemap_v1', 3600, function () use ($dbs, $base) {
    $urls = [];
    $urls[] = ['loc' => $base . 'index.php', 'pri' => '1.0', 'freq' => 'daily'];
    foreach (['news', 'libinfo', 'librarian', 'help'] as $p) $urls[] = ['loc' => $base . 'index.php?p=' . $p, 'pri' => '0.6', 'freq' => 'weekly'];
    $urls[] = ['loc' => $base . 'index.php?stats=1', 'pri' => '0.5', 'freq' => 'daily'];
    try {
        $r = $dbs->query('SELECT biblio_id, last_update FROM biblio ORDER BY last_update DESC LIMIT 50000');
        if ($r) while ($row = $r->fetch_assoc()) {
            $urls[] = ['loc' => $base . 'index.php?p=show_detail&id=' . (int)$row['biblio_id'], 'pri' => '0.8', 'freq' => 'monthly', 'last' => substr((string)$row['last_update'], 0, 10)];
        }
    } catch (Throwable $e) {}
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
        if (!empty($u['last'])) $xml .= "    <lastmod>" . $u['last'] . "</lastmod>\n";
        $xml .= "    <changefreq>" . $u['freq'] . "</changefreq>\n    <priority>" . $u['pri'] . "</priority>\n  </url>\n";
    }
    $xml .= '</urlset>';
    return $xml;
});
sonic_etag_send($body, 'application/xml; charset=utf-8', 3600);