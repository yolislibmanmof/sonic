<?php
/** tools/fetch_qr.php — SONIC BATCH 4 (CLI only). Unduh qrcode-generator (MIT) ke lokal. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$dir = __DIR__ . '/../assets/plugin/qrcode';
if (!is_dir($dir)) { mkdir($dir, 0755, true); }
$url = 'https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js';
$ctx = stream_context_create(['http' => ['timeout' => 20, 'user_agent' => 'SONIC-build'], 'ssl' => ['verify_peer' => true]]);
$data = @file_get_contents($url, false, $ctx);
if ($data === false || strlen($data) < 1000) { fwrite(STDERR, "Gagal unduh QR. Fallback online SONIC tetap aman.\n"); exit(1); }
file_put_contents($dir . '/qrcode.min.js', $data);
echo "OK: assets/plugin/qrcode/qrcode.min.js (" . strlen($data) . " bytes). Ctrl+F5 → QR Buku kini lokal.\n";