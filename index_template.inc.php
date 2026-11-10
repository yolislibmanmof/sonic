<?php
# @Filename: index_template.inc.php (SONIC v2)

$imagesDisk = \SLiMS\Filesystems\Storage::images();
$_SESSION['LIST_VIEW'] = $_POST['view'] ?? $_SESSION['LIST_VIEW'] ?? 'list';

include_once 'classic.php';
include 'parts/header.php';

// Deteksi halaman
if (isset($_GET['p']) && $_GET['p'] === 'login') {
    // LOGIN ADMIN — Command Center SONIC
    include 'parts/_navbar.php';
    include 'parts/_login.php';
} elseif (isset($_GET['p']) || isset($_GET['search'])) {
    if (isset($_GET['search'])) {
        include 'parts/_result-search.php';
    } else {
        if ($_GET['p'] == 'member') {
            include 'parts/_member.php';
        } else {
            include 'parts/_other.php';
        }
    }
} else {
    include 'parts/_home.php';
}

include 'parts/footer.php';