<?php
function news_list_tpl($title, $path, $date, $summary) {
    global $sysconf;
    // Locale hanya boleh kode valid (cookie user-controlled)
    $lang = $sysconf['default_lang'] ?? 'en_US';
    if (isset($_COOKIE['select_lang']) && preg_match('/^[a-z]{2}_[A-Z]{2}$/', trim($_COOKIE['select_lang']))) {
        $lang = trim($_COOKIE['select_lang']);
    }
    try {
        $dateFmt = \Carbon\Carbon::parse($date)->locale($lang)->isoFormat('dddd, LL');
    } catch (Throwable $e) {
        $ts = strtotime((string)$date);
        $dateFmt = $ts ? date('d M Y', $ts) : htmlspecialchars((string)$date);
    }
    $safeSummary = function_exists('sonic_kses') ? sonic_kses($summary) : htmlspecialchars(strip_tags((string)$summary), ENT_QUOTES);
?>
<div class="sonic-info-block" style="margin-bottom: 1.5rem;">
    <div style="color: var(--sonic-muted); font-size: 0.8rem; margin-bottom: 0.5rem;">
        🕐 <?= $dateFmt ?>
    </div>
    <h3 class="sonic-section-title" style="margin-bottom: 1rem;"><?= htmlspecialchars((string)$title, ENT_QUOTES) ?></h3>
    <p style="color: var(--sonic-text); line-height: 1.7; margin-bottom: 1rem;"><?= $safeSummary ?>...</p>
    <a class="sonic-btn" href="<?= SWB.'index.php?p='.htmlspecialchars((string)$path, ENT_QUOTES) ?>">⚡ <?= _('Read More') ?></a>
</div>
<?php
}