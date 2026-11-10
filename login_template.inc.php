<?php
# @File name: login_template.inc.php (SONIC Edition)

if (isset($_GET['p']) && $_GET['p'] === 'visitor') {
    $imagesDisk = \SLiMS\Filesystems\Storage::images();
    include "classic.php";
    include "parts/header.php";
    echo $main_content;
    include __DIR__ . "/assets/js/vegas.js.php";
    echo '</body></html>';
} else {
    include "index_template.inc.php";
}