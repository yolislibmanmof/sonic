<?php
$author_list = [];
$authors_string = '';
foreach ($authors as $order => $data) {
    $chunk_name = explode(' ', $data['author_name']);
    $last_chunkname_order = array_key_last($chunk_name);
    $last_name = $chunk_name[$last_chunkname_order];
    $first_name = $chunk_name[0] ?? '';
    if (!str_ends_with(trim($first_name), ',')) {
        unset($chunk_name[$last_chunkname_order]);
        if ($order > 0 & count($authors) > 2) continue;
        $author_list[] = $last_name . ', ' . implode(', ', array_map(fn($name) => ucfirst($name) . '.', $chunk_name));
    } else {
        if ($order > 0 & count($authors) > 2) continue;
        unset($chunk_name[0]);
        $author_list[] = $first_name . ' ' . implode(', ', array_map(fn($name) => ucfirst($name) . '.', $chunk_name));
    }
}
$authors_string = implode(', ', $author_list) . (count($authors) > 2 ? ' et al.' : '');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Citation - MLA</title>
    <link rel="stylesheet" href="<?= SWB ?>template/sonic/assets/css/sonic-base.css">
    <link rel="stylesheet" href="<?= SWB ?>template/sonic/assets/css/sonic-modal.css">
    <style>body { padding: 2rem; }</style>
</head>
<body class="sonic-theme">
<div class="sonic-terminal" style="max-width: 700px; margin: 0 auto;">
    <div class="sonic-terminal-head">
        <span class="sonic-dot r"></span>
        <span class="sonic-dot y"></span>
        <span class="sonic-dot g"></span>
        <span class="sonic-terminal-title">mla_citation.exe</span>
    </div>
    <div class="sonic-terminal-body">
        <h3>MLA Style Citation</h3>
        <p class="citation">
            <?php if ($authors_string): ?>
                <span class="authors"><?= $authors_string ?></span>
                <span class="title">"<?= $title ?>".</span>
                <span class="edition"><em><?= $edition ?></em></span>
            <?php else: ?>
                <span class="title"><em><?= $title ?></em>.</span>
                <span class="edition"><?= $edition ?>.</span>
            <?php endif; ?>
            <span class="publish_place"><?= $publish_place ?>:</span>
            <span class="publisher"><?= $publisher_name ?>,</span>
            <span class="year"><?= $publish_year ?>.</span>
            <span class="gmd_name"><?= $gmd_name ?>.</span>
        </p>
    </div>
</div>
</body>
</html>