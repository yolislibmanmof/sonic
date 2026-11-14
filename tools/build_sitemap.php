<?php
/** SONIC BATCH 8 — generate sitemap.xml statis ke root SLiMS (SEO). CLI only.
 *  Pakai: php tools/build_sitemap.php   (jadwalkan ulang berkala / cron)
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
define('INDEX_AUTH', '1');
require_once __DIR__ . '/../../../sysconfig.inc.php';

$scheme = 'https'; // sesuaikan bila produksi http
$host = $argv[1] ?? 'localhost';
$base = $scheme . '://' . $host . SWB;

$urls = [];
$urls[] = ['loc' => $base . 'index.php', 'pri' => '1.0', 'freq' => 'daily'];
foreach (['news', 'libinfo', 'librarian', 'help'] as $p) $urls[] = ['loc' => $base . 'index.php?p=' . $p, 'pri' => '0.6', 'freq' => 'weekly'];
$urls[] = ['loc' => $base . 'index.php?stats=1', 'pri' => '0.5', 'freq' => 'daily'];
$n = 0;
try {
    $r = $dbs->query('SELECT biblio_id, last_update FROM biblio ORDER BY last_update DESC LIMIT 50000');
    if ($r) while ($row = $r->fetch_assoc()) { $n++; $urls[] = ['loc' => $base . 'index.php?p=show_detail&id=' . (int)$row['biblio_id'], 'pri' => '0.8', 'freq' => 'monthly', 'last' => substr((string)$row['last_update'], 0, 10)]; }
} catch (Throwable $e) {}
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    $xml .= "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    if (!empty($u['last'])) $xml .= "    <lastmod>" . $u['last'] . "</lastmod>\n";
    $xml .= "    <changefreq>" . $u['freq'] . "</changefreq>\n    <priority>" . $u['pri'] . "</priority>\n  </url>\n";
}
$xml .= '</urlset>';
$dest = SB . 'sitemap.xml';
if (file_put_contents($dest, $xml, LOCK_EX) === false) { fwrite(STDERR, "Gagal tulis $dest (permission?)\n"); exit(1); }
echo "OK: $dest (" . count($urls) . " URL, $n biblio)\n";
echo "Submit ke Google Search Console: $base/sitemap.xml\n";