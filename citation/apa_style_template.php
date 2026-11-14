<?php
/**
 * APA Style citation (SONIC Terminal — BATCH 4)
 * Fix: escaping penuh (anti-XSS), path rename-safe, guard $authors,
 *      nama 1 kata aman, "et al." benar, tahun n.d. bila kosong.
 * Catatan APA 7: tempat terbit tidak dicantumkan (hanya penerbit).
 */
$sonic_e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
$sonic_src = (isset($authors) && is_array($authors)) ? $authors : [];
$sonic_total = count($sonic_src);
$sonic_list = [];
foreach ($sonic_src as $sonic_order => $sonic_data) {
    $sonic_name = trim(is_array($sonic_data) ? ($sonic_data['author_name'] ?? '') : (string)$sonic_data);
    if ($sonic_name === '') continue;
    if ($sonic_order > 0 && $sonic_total > 2) continue; // APA: pengarang pertama + et al.
    $sonic_parts = preg_split('/\s+/', $sonic_name, -1, PREG_SPLIT_NO_EMPTY);
    if (count($sonic_parts) === 1) { $sonic_list[] = $sonic_parts[0]; continue; }
    $sonic_first = $sonic_parts[0];
    $sonic_init = fn($n) => ucfirst(mb_substr($n, 0, 1)) . '.';
    if (str_ends_with($sonic_first, ',')) {
        $sonic_list[] = $sonic_first . ' ' . implode(' ', array_map($sonic_init, array_slice($sonic_parts, 1)));
    } else {
        $sonic_last = array_pop($sonic_parts);
        $sonic_list[] = $sonic_last . ', ' . implode(' ', array_map($sonic_init, $sonic_parts));
    }
}
$authors_string = implode(', ', $sonic_list) . ($sonic_total > 2 ? ' et al.' : '');
$sonic_year = trim((string)($publish_year ?? ''));
$sonic_year_disp = $sonic_year !== '' ? $sonic_year : 'n.d.';
$sonic_css = SWB . ($GLOBALS['sysconf']['template']['dir'] ?? 'template') . '/' . basename(dirname(__DIR__)) . '/assets/css/';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Citation - APA</title>
    <link rel="stylesheet" href="<?= $sonic_css ?>sonic-base.css">
    <link rel="stylesheet" href="<?= $sonic_css ?>sonic-modal.css">
    <style>body { padding: 2rem; }</style>
</head>
<body class="sonic-theme">
<div class="sonic-terminal" style="max-width: 700px; margin: 0 auto;">
    <div class="sonic-terminal-head">
        <span class="sonic-dot r"></span>
        <span class="sonic-dot y"></span>
        <span class="sonic-dot g"></span>
        <span class="sonic-terminal-title">apa_citation.exe</span>
    </div>
    <div class="sonic-terminal-body">
        <h3>APA Style Citation</h3>
        <p class="citation">
            <?php if ($authors_string !== ''): ?><span class="authors"><?= $sonic_e($authors_string) ?></span> <?php endif; ?>
            <span class="year">(<?= $sonic_e($sonic_year_disp) ?>).</span>
            <span class="title"><em><?= $sonic_e($title ?? '') ?></em><?php if (!empty($edition)): ?> (<span class="edition"><?= $sonic_e($edition) ?></span>)<?php endif; ?>.</span>
            <?php if (!empty($publisher_name)): ?><span class="publisher"><?= $sonic_e($publisher_name) ?>.</span><?php endif; ?>
        </p>
    </div>
</div>
</body>
</html>