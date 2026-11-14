<?php
/**
 * SONIC BUILD TOOLS — unduh PDF.js lokal (offline-first) untuk mode Flip Buku.
 * CLI only. Menulis HANYA di assets/plugin/pdfjs/build/; tidak menyentuh core.
 * Pemakaian: php tools/fetch_pdfjs.php
 * Sumber: pdfjs-dist 3.11.174 (kompatibel API canvasContext+viewport).
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only. Jalankan: php tools/fetch_pdfjs.php\n"); }

$root = dirname(__DIR__);
$dest = $root . '/assets/plugin/pdfjs/build';
if (!is_dir($dest) && !@mkdir($dest, 0755, true)) { exit("ERROR: tidak bisa membuat folder: $dest\n"); }
if (!is_writable($dest)) { exit("ERROR: folder tidak writable: $dest\n"); }

$files = [
    'pdf.js'        => ['https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js', 'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js'],
    'pdf.worker.js' => ['https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js', 'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js'],
];

function grab($urls) {
    foreach ($urls as $u) {
        $ctx = stream_context_create(['http' => ['timeout' => 60, 'header' => "User-Agent: sonic-build\r\n"], 'ssl' => ['verify_peer' => true]]);
        $data = @file_get_contents($u, false, $ctx);
        if ($data !== false && strlen($data) > 50000) return $data;
        if (function_exists('curl_init')) {
            $ch = curl_init($u);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'sonic-build']);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($data !== false && $code === 200 && strlen($data) > 50000) return $data;
        }
    }
    return false;
}

foreach ($files as $name => $urls) {
    $target = $dest . '/' . $name;
    if (is_file($target) && filesize($target) > 50000) { echo "= sudah ada: $name\n"; continue; }
    echo "- mengunduh $name ...\n";
    $data = grab($urls);
    if ($data === false) { echo "  ! GAGAL unduh $name (periksa koneksi). Flip akan fallback ke Standar.\n"; continue; }
    $tmp = $target . '.tmp';
    if (@file_put_contents($tmp, $data) === false || !@rename($tmp, $target)) { echo "  ! GAGAL tulis $target\n"; @unlink($tmp); continue; }
    echo "  + $name (" . round(strlen($data) / 1024) . " KB)\n";
}
echo "Selesai. Jalankan: php tools/build_bundles.php --js  lalu Ctrl+F5.\n";