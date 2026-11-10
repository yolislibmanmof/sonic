<?php
# @Filename: header.php (SONIC v3 — Vendor Rescue)
$request_uri = urlencode(strip_tags(urldecode($_SERVER['REQUEST_URI'])));

/**
 * SONIC VENDOR FALLBACK
 * Jika file vendor tidak ada di sonic/assets, otomatis pakai milik template lain (default).
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
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $page_title; ?> | SONIC OPAC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, post-check=0, pre-check=0"/>
    <meta http-equiv="Expires" content="Sat, 26 Jul 1997 05:00:00 GMT"/>
    <?php echo $metadata; ?>

    <?php if (isset($_GET['p']) && ($_GET['p'] == 'show_detail')): ?>
        <meta name="description" content="<?php echo substr($notes, 0, 152) . '...'; ?>">
        <meta name="keywords" content="<?php echo $subject; ?>">
    <?php else: ?>
        <meta name="description" content="<?php echo $page_title; ?>">
        <meta name="keywords" content="<?php echo $sysconf['library_subname']; ?>">
    <?php endif; ?>

    <meta name="generator" content="<?php echo SENAYAN_VERSION ?>">
    <meta name="theme-color" content="#00f0ff">
    <meta property="og:locale" content="<?php echo str_replace('-', '_', $sysconf['default_lang']); ?>"/>
    <meta property="og:type" content="book"/>
    <meta property="og:title" content="<?php echo $page_title; ?>"/>
    <?php if (isset($_GET['p']) && ($_GET['p'] == 'show_detail')): ?>
        <meta property="og:description" content="<?php echo substr($notes, 0, 152) . '...'; ?>"/>
        <meta property="og:image" content="//<?php echo $_SERVER["SERVER_NAME"] . SWB . $image_src ?>"/>
    <?php else: ?>
        <meta property="og:description" content="<?php echo $sysconf['library_subname']; ?>"/>
        <meta property="og:image" content="//<?php echo $_SERVER["SERVER_NAME"] . SWB . $sysconf['template']['dir']; ?>/sonic/assets/images/logo.png"/>
    <?php endif; ?>
    <meta property="og:url" content="//<?php echo $_SERVER["SERVER_NAME"] . $request_uri; ?>"/>
    <meta property="og:site_name" content="<?php echo $sysconf['library_name']; ?>"/>
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="//<?php echo $_SERVER["SERVER_NAME"] . $request_uri; ?>"/>
    <meta name="twitter:title" content="<?php echo $page_title; ?>"/>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Orbitron:wght@700;800;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS (FALLBACK AMAN) -->
    <link rel="stylesheet" href="<?php echo sonic_vendor('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo sonic_vendor('plugin/font-awesome/css/fontawesome-all.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo sonic_vendor('css/tailwind.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo sonic_vendor('css/flag-icon.min.css'); ?>">

    <!-- SLiMS core CSS -->
    <link href="<?php echo JWB; ?>toastr/toastr.min.css?<?php echo date('this') ?>" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="<?= JWB; ?>ckeditor5/ckeditor5.css">
    <link rel="stylesheet" href="<?= JWB; ?>colorbox/colorbox.css">
    <link rel="stylesheet" href="<?= JWB; ?>ion.rangeSlider/css/ion.rangeSlider.min.css">

    <!-- SONIC THEME (modular, versi cache-bust) -->
    <?php
    $sonicCssVer = date('Ymd-his');
    foreach (['sonic-base','sonic-navbar','sonic-hero','sonic-cards','sonic-detail','sonic-modal','sonic-footer','sonic-components','sonic-facet','sonic-marc','sonic-member','sonic-auth','sonic-admin','sonic-librarian','sonic-info'] as $scf) {
        echo '<link rel="stylesheet" href="' . assets('css/' . $scf . '.css?v=' . $sonicCssVer) . '">' . "\n";
    }
    ?>

    <?php
    $icon = SWB . 'webicon.ico';
    if (isset($sysconf['webicon']) && !empty($sysconf['webicon']) && $imagesDisk->isExists($path = 'default/' . $sysconf['webicon'])) {
        $icon = SWB . 'lib/minigalnano/createthumb.php?filename=images/' . $path . '&width=130';
    }
    ?>
    <link rel="shortcut icon" href="<?php echo $icon ?>" type="image/x-icon"/>

    <!-- Vendor JS (FALLBACK AMAN — urutan WAJIB: jquery dulu) -->
    <script src="<?php echo sonic_vendor('js/vue.min.js'); ?>"></script>
    <script src="<?php echo sonic_vendor('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo sonic_vendor('js/masonry.pkgd.min.js'); ?>"></script>
    <script src="<?php echo sonic_vendor('js/bootstrap.bundle.min.js'); ?>"></script>

    <!-- SLiMS core JS -->
    <script src="<?php echo JWB; ?>toastr/toastr.min.js"></script>
    <script src="<?php echo JWB; ?>colorbox/jquery.colorbox-min.js"></script>
    <script src="<?php echo JWB . v('gui.js'); ?>"></script>
    <script src="<?php echo JWB; ?>fancywebsocket.js"></script>
    <script src="<?php echo JWB; ?>ion.rangeSlider/js/ion.rangeSlider.min.js"></script>

    <?php if (isset($js)) echo $js; ?>
</head>
<body class="sonic-theme">

<!-- SONIC BOOT SCREEN -->
<div id="sonicBoot">
    <div class="sonic-boot-core"><i class="fas fa-bolt"></i></div>
    <div class="sonic-boot-text">SONIC</div>
    <div class="sonic-boot-bar"><span></span></div>
</div>