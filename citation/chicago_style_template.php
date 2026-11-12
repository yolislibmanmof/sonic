<?php
/**
 * Chicago Style citation (SONIC Terminal — BATCH 2)
 * Fix F-6: $authors_string dihitung lokal (sebelumnya undefined → pengarang kosong).
 */
$sonic_e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
$sonic_src = (isset($authors) && is_array($authors)) ? $authors : [];
$sonic_total = count($sonic_src);
$sonic_list = [];
foreach ($sonic_src as $sonic_order => $sonic_data) {
    $sonic_name = trim(is_array($sonic_data) ? ($sonic_data['author_name'] ?? '') : (string)$sonic_data);
    if ($sonic_name === '') continue;
    if ($sonic_order > 0 && $sonic_total > 2) continue;
    $sonic_chunk = explode(' ', $sonic_name);
    $sonic_li = array_key_last($sonic_chunk);
    $sonic_last = $sonic_chunk[$sonic_li];
    $sonic_first = $sonic_chunk[0] ?? '';
    if (substr(trim($sonic_first), -1) === ',') {
        unset($sonic_chunk[0]);
        $sonic_list[] = $sonic_first . ' ' . implode(' ', array_map('ucfirst', $sonic_chunk));
    } else {
        unset($sonic_chunk[$sonic_li]);
        $sonic_list[] = $sonic_last . ', ' . implode(' ', array_map('ucfirst', $sonic_chunk));
    }
}
$authors_string = implode(', ', $sonic_list) . ($sonic_total > 2 ? ' et al.' : '');
$sonic_css = SWB . ($GLOBALS['sysconf']['template']['dir'] ?? 'template') . '/' . basename(dirname(__DIR__)) . '/assets/css/';
$sonic_gmd = (($gmd_name ?? '') == 'Text') ? 'Print' : ($gmd_name ?? '');
?>
<link rel="stylesheet" href="<?= $sonic_css ?>sonic-base.css">
<link rel="stylesheet" href="<?= $sonic_css ?>sonic-modal.css">
<div class="sonic-theme" style="padding:1rem;">
<div class="sonic-terminal" style="max-width: 700px; margin: 0 auto;">
    <div class="sonic-terminal-head">
        <span class="sonic-dot r"></span><span class="sonic-dot y"></span><span class="sonic-dot g"></span>
        <span class="sonic-terminal-title">chicago_citation.exe</span>
    </div>
    <div class="sonic-terminal-body">
        <h3>Chicago Style Citation</h3>
        <p class="citation">
            <?php if ($authors_string !== ''): ?><span class="authors"><?= $sonic_e($authors_string) ?>.</span><?php endif; ?>
            <span class="title"><em><?= $sonic_e($title ?? '') ?></em>.</span>
            <?php if (!empty($edition)): ?><span class="edition"><?= $sonic_e($edition) ?>.</span><?php endif; ?>
            <span class="publish_place"><?= $sonic_e($publish_place ?? '') ?>:</span>
            <span class="publisher"><?= $sonic_e($publisher_name ?? '') ?>,</span>
            <span class="year"><?= $sonic_e($publish_year ?? '') ?>.</span>
            <span class="gmd_name"><?= $sonic_e($sonic_gmd) ?>.</span>
        </p>
    </div>
</div>
</div>