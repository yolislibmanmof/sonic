<?php if ($gmd_name == 'Text') $gmd_name = 'Print'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Citation - Turabian</title>
    <link rel="stylesheet" href="<?= SWB ?>template/sonic/assets/css/sonic.css">
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
        <span class="sonic-terminal-title">turabian_citation.exe</span>
    </div>
    <div class="sonic-terminal-body">
        <h3>Turabian Style Citation</h3>
        <p class="citation">
            <?php if ($authors_string): ?>
                <span class="authors"><?= $authors_string ?>.</span>
                <span class="title"><em><?= $title ?></em>.</span>
                <span class="edition"><?= $edition ?></span>
            <?php else: ?>
                <span class="title"><em><?= $title ?></em>.</span>
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