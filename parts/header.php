<?php
# @Filename: header.php (SONIC — BATCH 1: escaping meta, header keamanan, cache waras, bundle-ready)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // private+no-cache (bukan no-store) agar back/forward cache hidup → navigasi lebih smooth
    header('Cache-Control: private, no-cache, must-revalidate');
}
$sonic_host = sonic_host();
$sonic_scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$sonic_req_uri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES);
$sonic_pt = htmlspecialchars($page_title ?? '', ENT_QUOTES);
$sonic_lib = htmlspecialchars($sysconf['library_name'] ?? '', ENT_QUOTES);
$sonic_sub = htmlspecialchars($sysconf['library_subname'] ?? '', ENT_QUOTES);
$sonic_img_src = htmlspecialchars(htmlspecialchars_decode($image_src ?? '', ENT_QUOTES), ENT_QUOTES);
$sonic_notes_meta = htmlspecialchars(mb_substr(strip_tags($notes ?? ''), 0, 152), ENT_QUOTES);

/**
 * SONIC VENDOR FALLBACK — pinjam aset vendor dari template lain bila tidak ada di sonic.
 */
if (!function_exists('sonic_vendor')) {
    function sonic_vendor($path = '') {
        global $sysconf;
        static $cache = [];
        if (isset($cache[$path])) return $cache[$path];
        $sonicFile = dirname(__DIR__) . '/assets/' . $path;
        if (file_exists($sonicFile)) return $cache[$path] = assets($path);
        $tplBase = dirname(__DIR__, 2);
        foreach (glob($tplBase . '/*', GLOB_ONLYDIR) as $dir) {
            if (file_exists($dir . '/assets/' . $path)) {
                return $cache[$path] = $sysconf['template']['dir'] . '/' . basename($dir) . '/assets/' . $path;
            }
        }
        return $cache[$path] = assets($path);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(substr($sysconf['default_lang'] ?? 'en', 0, 2)) ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $sonic_pt; ?> | SONIC OPAC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php echo $metadata; ?>

    <?php if (isset($_GET['p']) && ($_GET['p'] == 'show_detail')): ?>
        <meta name="description" content="<?php echo $sonic_notes_meta; ?>...">
        <meta name="keywords" content="<?php echo htmlspecialchars($subject ?? '', ENT_QUOTES); ?>">
    <?php else: ?>
        <meta name="description" content="<?php echo $sonic_pt; ?>">
        <meta name="keywords" content="<?php echo $sonic_sub; ?>">
    <?php endif; ?>

    <meta name="generator" content="<?php echo SENAYAN_VERSION ?>">
    <meta name="theme-color" content="#00f0ff">
    <meta property="og:locale" content="<?php echo htmlspecialchars(str_replace('-', '_', $sysconf['default_lang'] ?? 'en')); ?>"/>
    <meta property="og:type" content="book"/>
    <meta property="og:title" content="<?php echo $sonic_pt; ?>"/>
    <?php if (isset($_GET['p']) && ($_GET['p'] == 'show_detail')): ?>
        <meta property="og:description" content="<?php echo $sonic_notes_meta; ?>..."/>
        <meta property="og:image" content="//<?php echo $sonic_host . SWB . $sonic_img_src ?>"/>
    <?php else: ?>
        <meta property="og:description" content="<?php echo $sonic_sub; ?>"/>
        <meta property="og:image" content="//<?php echo $sonic_host . SWB . CURRENT_TEMPLATE_DIR ?>assets/images/logo-big.png"/>
    <?php endif; ?>
    <meta property="og:url" content="//<?php echo $sonic_host . $sonic_req_uri; ?>"/>
    <meta property="og:site_name" content="<?php echo $sonic_lib; ?>"/>
    <meta name="twitter:card" content="summary_large_image">
    <?php if (isset($_GET['p']) && ($_GET['p'] == 'show_detail')): ?>
    <meta name="twitter:image" content="//<?php echo $sonic_host . SWB . $sonic_img_src ?>"/>
    <meta property="og:image:width" content="600"/>
    <meta property="og:image:height" content="800"/>
    <?php endif; ?>
    <meta name="twitter:url" content="//<?php echo $sonic_host . $sonic_req_uri; ?>"/>
    <meta name="twitter:title" content="<?php echo $sonic_pt; ?>"/>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Orbitron:wght@700;800;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS (FALLBACK AMAN) -->
    <link rel="stylesheet" href="<?php echo sonic_vendor('css/bootstrap.min.css'); ?>">
    <?php $sonic_fa = is_file(__DIR__ . '/../assets/plugin/font-awesome-5.15.4/css/all.min.css') ? 'plugin/font-awesome-5.15.4/css/all.min.css' : 'plugin/font-awesome/css/fontawesome-all.min.css'; ?>
    <link rel="stylesheet" href="<?php echo sonic_vendor($sonic_fa); ?>">
    <link rel="stylesheet" href="<?php echo sonic_vendor('css/tailwind.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo sonic_vendor('css/flag-icon.min.css'); ?>">

    <!-- SLiMS core CSS (FIX: versi stabil, bukan date('this')) -->
    <link href="<?php echo JWB; ?>toastr/toastr.min.css?v=<?php echo SENAYAN_VERSION_TAG ?>" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="<?= JWB; ?>ckeditor5/ckeditor5.css">
    <link rel="stylesheet" href="<?= JWB; ?>colorbox/colorbox.css">
    <link rel="stylesheet" href="<?= JWB; ?>ion.rangeSlider/css/ion.rangeSlider.min.css">

    <!-- SONIC THEME: bundle bila ada (Batch 2), fallback modular -->
    <?php
    if (is_file(__DIR__ . '/../assets/css/sonic.bundle.css')) {
        echo '<link rel="stylesheet" href="' . assets('css/sonic.bundle.css') . '?v=' . sonic_asset_ver('css/sonic.bundle.css') . '">' . "\n";
    } else {
        foreach (['sonic-base','sonic-navbar','sonic-hero','sonic-cards','sonic-detail','sonic-modal','sonic-footer','sonic-components','sonic-facet','sonic-marc','sonic-member','sonic-auth','sonic-admin','sonic-librarian','sonic-info','sonic-particles','sonic-suggest','sonic-result','sonic-member-dash','sonic-premium'] as $scf) {
            echo '<link rel="stylesheet" href="' . assets('css/' . $scf . '.css') . '?v=' . sonic_asset_ver('css/' . $scf . '.css') . '">' . "\n";
        }
    }
    ?>

    <?php
    $icon = SWB . 'webicon.ico';
    if (isset($sysconf['webicon']) && !empty($sysconf['webicon']) && $imagesDisk->isExists($path = 'default/' . $sysconf['webicon'])) {
        $icon = SWB . 'lib/minigalnano/createthumb.php?filename=images/' . $path . '&width=130';
    }
    ?>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($icon, ENT_QUOTES) ?>" type="image/x-icon"/>

    <!-- Vendor JS (jquery dulu; bootstrap defer) -->
    <script src="<?php echo sonic_vendor('js/jquery.min.js'); ?>"></script>
    <script defer src="<?php echo sonic_vendor('js/bootstrap.bundle.min.js'); ?>"></script>
    <script>
    window.SONIC_CFG = <?php echo json_encode([
        'particle' => (string)($sysconf['template']['sonic_particle'] ?? 'books'),
        'walker'   => (string)($sysconf['template']['sonic_walker'] ?? 'person'),
        'lang'     => substr($sysconf['default_lang'] ?? 'en', 0, 2),
        'base'     => CURRENT_TEMPLATE_DIR,
        'logged'   => !empty($is_login),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
    </script>

    <!-- SLiMS core JS -->
    <script src="<?php echo JWB; ?>toastr/toastr.min.js"></script>
    <script src="<?php echo JWB; ?>colorbox/jquery.colorbox-min.js"></script>
    <script src="<?php echo JWB . v('gui.js'); ?>"></script>
    <script src="<?php echo JWB; ?>fancywebsocket.js"></script>
    <script defer src="<?php echo JWB; ?>ion.rangeSlider/js/ion.rangeSlider.min.js"></script>

    <?php if (isset($js)) echo $js; ?>
</head>
<body class="sonic-theme">

<!-- SONIC BOOT SCREEN -->
<div id="sonicBoot">
    <div class="sonic-boot-core"><i class="fas fa-bolt"></i></div>
    <div class="sonic-boot-text">SONIC</div>
    <div class="sonic-boot-bar"><span></span></div>
</div>
<script>
/* FIX flash boot: buang overlay SEGERA bila sesi ini sudah pernah boot */
(function(){try{if(sessionStorage.getItem('sonicBooted')){var b=document.getElementById('sonicBoot');if(b&&b.parentNode)b.parentNode.removeChild(b);}}catch(e){}})();
</script>