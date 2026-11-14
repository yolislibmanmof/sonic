<?php
/**
 * SONIC BUILD TOOLS — CLI only (tidak bisa dijalankan lewat browser demi keamanan).
 *
 * Pemakaian:
 *   php tools/build_bundles.php           → build semua (bundle CSS+JS, WebP, flag-icon)
 *   php tools/build_bundles.php --clean   → hapus bundle (kembali ke mode modular)
 *   php tools/build_bundles.php --css     → hanya bundle CSS
 *   php tools/build_bundles.php --js      → hanya bundle JS
 *   php tools/build_bundles.php --webp    → hanya konversi WebP
 *   php tools/build_bundles.php --flags   → hanya flag-icon.min.css
 *
 * Aman: hanya menulis di dalam folder template ini; tidak menyentuh core SLiMS;
 * file asli tidak pernah dihapus.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only. Jalankan lewat terminal: php tools/build_bundles.php\n");
}

$root   = dirname(__DIR__);
$assets = $root . '/assets';
if (!is_dir($assets)) { exit("ERROR: folder assets tidak ditemukan di: $assets\n"); }
if (!is_writable($assets . '/css') || !is_writable($assets . '/js')) {
    exit("ERROR: folder assets/css atau assets/js tidak writable. Perbaiki permission dulu.\n");
}

$argvv = $argv ?? [];
$opt   = function ($f) use ($argvv) { return in_array($f, $argvv, true); };
$doAll = !$opt('--css') && !$opt('--js') && !$opt('--webp') && !$opt('--flags') && !$opt('--fa');

function out($s) { echo $s . PHP_EOL; }
function put($path, $content) {
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $content, LOCK_EX) === false) { out("  ! gagal tulis: $path"); return false; }
    if (is_file($path)) @unlink($path);
    if (!rename($tmp, $path)) { @unlink($tmp); out("  ! gagal rename: $path"); return false; }
    return true;
}

out("SONIC BUILD — PHP " . PHP_VERSION . " | GD: " . (extension_loaded('gd') ? 'ada' : 'TIDAK ADA') . " | mode: " . ($doAll ? 'semua' : implode(' ', array_slice($argvv, 1))));

/* ===== --clean : kembali ke modular ===== */
if ($opt('--clean')) {
    foreach ([$assets . '/css/sonic.bundle.css', $assets . '/js/sonic.bundle.js'] as $f) {
        if (is_file($f)) { @unlink($f); out("dihapus: $f"); }
    }
    out("Mode modular aktif kembali (WebP & flag-icon tetap dipertahankan).");
    exit(0);
}

/* ===== 1) CSS bundle ===== */
if ($doAll || $opt('--css')) {
    out("== CSS bundle ==");
    $cssFiles = ['sonic-base','sonic-navbar','sonic-hero','sonic-cards','sonic-detail','sonic-modal','sonic-footer','sonic-components','sonic-facet','sonic-marc','sonic-member','sonic-auth','sonic-admin','sonic-librarian','sonic-info','sonic-particles','sonic-suggest','sonic-result','sonic-member-dash','sonic-premium','sonic-discovery','sonic-platform'];
    $css = "/* SONIC bundle — generated " . date('c') . " — jangan edit manual; edit file modular lalu build ulang */\n";
    $n = 0;
    foreach ($cssFiles as $name) {
        $f = $assets . '/css/' . $name . '.css';
        if (!is_file($f)) { out("  ! lewati (tidak ada): $name.css"); continue; }
        $c = preg_replace('#/\*.*?\*/#s', '', file_get_contents($f));
        $lines = array_filter(array_map('trim', explode("\n", $c)), fn($l) => $l !== '');
        $css .= "\n/* --- $name.css --- */\n" . implode("\n", $lines) . "\n";
        $n++;
    }
    if (put($assets . '/css/sonic.bundle.css', $css)) out("  sonic.bundle.css: $n file, " . round(strlen($css) / 1024, 1) . " KB");
}

/* ===== 2) JS bundle (konkatenasi aman — tiap file IIFE/jQuery-ready) ===== */
if ($doAll || $opt('--js')) {
    out("== JS bundle ==");
    $jsFiles = ['app_jquery','sonic','sonic-marc','sonic-facet','sonic-member','sonic-footer','sonic-librarian','sonic-info','sonic-particles','sonic-detail','sonic-suggest','sonic-result','sonic-premium','sonic-discovery','sonic-platform'];
    $js = "/* SONIC bundle — generated " . date('c') . " */\n";
    $n = 0;
    foreach ($jsFiles as $name) {
        $f = $assets . '/js/' . $name . '.js';
        if (!is_file($f)) { out("  ! lewati (tidak ada): $name.js"); continue; }
        $js .= "\n;/* --- $name.js --- */\n" . file_get_contents($f) . "\n";
        $n++;
    }
    if (put($assets . '/js/sonic.bundle.js', $js)) out("  sonic.bundle.js: $n file, " . round(strlen($js) / 1024, 1) . " KB");
}

/* ===== 3) WebP slide (file asli TIDAK dihapus) ===== */
if ($doAll || $opt('--webp')) {
    out("== WebP slider ==");
    if (!function_exists('imagewebp')) {
        out("  ! GD/imagewebp tidak tersedia — lewati (slider tetap pakai JPG).");
    } else {
        $targets = [];
        foreach ([$assets . '/images/slider', $assets . '/images'] as $dir) {
            if (!is_dir($dir)) continue;
            foreach (scandir($dir) as $sf) {
                if (!preg_match('/\.(jpg|jpeg|png|gif)$/i', $sf)) continue;
                if (basename($dir) === 'images' && !preg_match('/^slide/i', $sf)) continue;
                $targets[] = $dir . '/' . $sf;
            }
        }
        foreach ($targets as $src) {
            $dst = preg_replace('/\.[^.]+$/', '.webp', $src);
            if (is_file($dst) && filemtime($dst) >= filemtime($src)) { out("  = terbaru: " . basename($dst)); continue; }
            $info = @getimagesize($src);
            if (!$info) { out("  ! bukan gambar: $src"); continue; }
            switch ($info[2]) {
                case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($src); break;
                case IMAGETYPE_PNG:  $im = @imagecreatefrompng($src); break;
                case IMAGETYPE_GIF:  $im = @imagecreatefromgif($src); break;
                default: $im = false;
            }
            if (!$im) { out("  ! gagal baca: $src"); continue; }
            $w = imagesx($im); $h = imagesy($im);
            if ($w > 1920) {
                $rs = imagescale($im, 1920, (int)round($h * 1920 / $w));
                if ($rs) { imagedestroy($im); $im = $rs; }
            }
            imagepalettetotruecolor($im);
            imagealphablending($im, true);
            imagesavealpha($im, true);
            imagewebp($im, $dst, 82);
            imagedestroy($im);
            out("  + " . basename($dst) . " (" . round(filesize($dst) / 1024) . " KB, dari " . round(filesize($src) / 1024) . " KB)");
        }
    }
}

/* ===== 4) flag-icon.min.css (aktifkan bendera sonic) ===== */
if ($doAll || $opt('--flags')) {
    out("== flag-icon.min.css ==");
    $flagDir = $assets . '/flags/4x3';
    if (is_dir($flagDir)) {
        $codes = [];
        foreach (scandir($flagDir) as $sf) { if (preg_match('/^([a-z\-]+)\.svg$/i', $sf, $m)) $codes[] = strtolower($m[1]); }
        sort($codes);
        $fc  = ".flag-icon{display:inline-block;width:1.3333em;height:1em;background-size:contain;background-position:50%;background-repeat:no-repeat;vertical-align:middle}";
        $fc .= ".flag-icon.flag-icon-squared{width:1em}";
        foreach ($codes as $c) {
            $fc .= ".flag-icon-$c{background-image:url(../flags/4x3/$c.svg)}";
            if (is_file($assets . '/flags/1x1/' . $c . '.svg')) $fc .= ".flag-icon-$c.flag-icon-squared{background-image:url(../flags/1x1/$c.svg)}";
        }
        if (put($assets . '/css/flag-icon.min.css', $fc)) out("  flag-icon.min.css: " . count($codes) . " bendera, " . round(strlen($fc) / 1024, 1) . " KB");
    } else {
        out("  ! folder flags/4x3 tidak ada — lewati.");
    }
}

/* ===== 5) Font Awesome 5.15.4 (folder BARU; file lama TIDAK dihapus) ===== */
if ($doAll || $opt('--fa')) {
    out("== Font Awesome 5.15.4 ==");
    $faDir = $assets . '/plugin/font-awesome-5.15.4';
    if (!is_dir($faDir . '/css')) @mkdir($faDir . '/css', 0755, true);
    if (!is_dir($faDir . '/webfonts')) @mkdir($faDir . '/webfonts', 0755, true);
    $ctx = stream_context_create(['http' => ['timeout' => 25, 'ignore_errors' => true]]);
    $faBase = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/';
    $faFiles = ['css/all.min.css',
        'webfonts/fa-solid-900.woff2','webfonts/fa-solid-900.woff','webfonts/fa-solid-900.ttf',
        'webfonts/fa-regular-400.woff2','webfonts/fa-regular-400.woff','webfonts/fa-regular-400.ttf',
        'webfonts/fa-brands-400.woff2','webfonts/fa-brands-400.woff','webfonts/fa-brands-400.ttf'];
    $ok = 0; $fail = 0;
    foreach ($faFiles as $rel) {
        $dst = $faDir . '/' . $rel;
        if (is_file($dst) && filesize($dst) > 0) { $ok++; continue; }
        $data = @file_get_contents($faBase . $rel, false, $ctx);
        if ($data === false || strlen($data) < 100) { $fail++; out("  ! gagal unduh: $rel"); continue; }
        if (put($dst, $data)) $ok++; else $fail++;
    }
    out("  FA 5.15.4: $ok file siap, $fail gagal (file lama tetap dipakai bila gagal).");
    if ($fail > 0) out("  ! butuh internet; ulangi: php tools/build_bundles.php --fa");
}
out("\nSelesai. Header/footer otomatis memakai bundle.");
out("Edit modular lagi? Build ulang: php tools/build_bundles.php (atau --js / --css saja).");
out("Balik ke modular: php tools/build_bundles.php --clean");