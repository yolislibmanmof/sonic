<?php
function news_list_tpl($title, $path, $date, $summary) {
    global $sysconf;
    if (isset($_COOKIE['select_lang'])) $sysconf['default_lang'] = trim(strip_tags($_COOKIE['select_lang']));
?>
<div class="sonic-info-block" style="margin-bottom: 1.5rem;">
    <div style="color: var(--sonic-muted); font-size: 0.8rem; margin-bottom: 0.5rem;">
        🕐 <?= \Carbon\Carbon::parse($date)->locale($sysconf['default_lang'])->isoFormat('dddd, LL') ?>
    </div>
    <h3 class="sonic-section-title" style="margin-bottom: 1rem;"><?= $title ?></h3>
    <p style="color: var(--sonic-text); line-height: 1.7; margin-bottom: 1rem;"><?= $summary ?>...</p>
    <a class="sonic-btn" href="<?= SWB.'index.php?p='.$path ?>">⚡ <?= _('Read More') ?></a>
</div>
<?php
}