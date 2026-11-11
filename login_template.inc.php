<?php
# @File name: login_template.inc.php (SONIC — visitor boot fix)
if (isset($_GET['p']) && $_GET['p'] === 'visitor') {
    $imagesDisk = \SLiMS\Filesystems\Storage::images();
    include "classic.php";
    include "parts/header.php";
    echo $main_content;
    include __DIR__ . "/assets/js/vegas.js.php";
    echo '<script>document.addEventListener("DOMContentLoaded",function(){var b=document.getElementById("sonicBoot");if(b){b.classList.add("done");setTimeout(function(){if(b.parentNode)b.parentNode.removeChild(b);},700);}});</script>';
    echo '</body></html>';
} else {
    include "index_template.inc.php";
}