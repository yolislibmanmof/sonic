<?php
/**
 * tools/fetch_html5qrcode.php — SONIC BATCH 6 (CLI only)
 * Unduh html5-qrcode (mebjas, MIT) ke assets/plugin/html5-qrcode/html5-qrcode.min.js
 * agar kiosk bisa SCAN QR kartu anggota secara LOKAL/offline.
 * Tanpa file ini, tombol scan tidak muncul & input manual tetap jalan (aman).
 * Pakai:  php tools/fetch_html5qrcode.php
 * Catatan: kamera browser hanya aktif di HTTPS atau localhost.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$dir = __DIR__ . '/../assets/plugin/html5-qrcode';
if (!is_dir($dir)) { mkdir($dir, 0755, true); }
$url = 'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js';
$ctx = stream_context_create(['http' => ['timeout' => 25, 'user_agent' => 'SONIC-build'], 'ssl' => ['verify_peer' => true]]);
$data = @file_get_contents($url, false, $ctx);
if ($data === false || strlen($data) < 10000) {
    fwrite(STDERR, "Gagal unduh html5-qrcode. Coba lagi saat online. Input manual kiosk tetap aman.\n");
    exit(1);
}
file_put_contents($dir . '/html5-qrcode.min.js', $data);
echo "OK: assets/plugin/html5-qrcode/html5-qrcode.min.js (" . strlen($data) . " bytes)\n";
echo "Ctrl+F5 di kiosk → tombol 'Scan Kartu Anggota' muncul (HTTPS/localhost).\n";