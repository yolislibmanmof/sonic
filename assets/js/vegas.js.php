<?php
/**
 * vegas.js.php — SONIC Background Slideshow (safe version)
 * File ini WAJIB ada karena dipanggil oleh footer.php & login_template.inc.php
 */
?>
<script>
(function(){
    'use strict';
    // SONIC memakai background CSS animasi (orbs + grid) secara default.
    // Jika Anda ingin slideshow Vegas, isi array sonicSlides di bawah.
    var sonicSlides = [];
    <?php
    // Opsional: ambil slide dari konfigurasi jika ada
    if (isset($sysconf['template']['sonic_bg_images']) && is_array($sysconf['template']['sonic_bg_images'])) {
        foreach ($sysconf['template']['sonic_bg_images'] as $img) {
            echo "sonicSlides.push({ src: '" . addslashes($img) . "' });\n";
        }
    }
    ?>
    if (sonicSlides.length > 0 && typeof jQuery !== 'undefined' && jQuery.fn.vegas) {
        jQuery('.sonic-hero, .c-header').vegas({
            slides: sonicSlides,
            overlay: true,
            transition: 'fade',
            delay: 5000
        });
    }
})();
</script>