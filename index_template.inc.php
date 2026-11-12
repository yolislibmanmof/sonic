<?php
# @Filename: index_template.inc.php (SONIC — BATCH 1)

$imagesDisk = \SLiMS\Filesystems\Storage::images();
$_view = $_POST['view'] ?? ($_SESSION['LIST_VIEW'] ?? 'list');
$_SESSION['LIST_VIEW'] = in_array($_view, ['list', 'grid'], true) ? $_view : 'list';

include_once __DIR__ . '/classic.php';
include __DIR__ . '/parts/header.php';

if (isset($_GET['p']) && $_GET['p'] === 'login') {
    include __DIR__ . '/parts/_navbar.php';
    include __DIR__ . '/parts/_login.php';
} elseif (isset($_GET['p']) || isset($_GET['search'])) {
    if (isset($_GET['search'])) {
        include __DIR__ . '/parts/_result-search.php';
    } else {
        if ($_GET['p'] == 'member') {
            include __DIR__ . '/parts/_member.php';
        } else {
            include __DIR__ . '/parts/_other.php';
        }
    }
} else {
    include __DIR__ . '/parts/_home.php';
}

include __DIR__ . '/parts/footer.php';