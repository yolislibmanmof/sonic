<?php
# @Filename: _stats.php (SONIC BATCH 8 — statistik publik, cache 5 menit, i18n sonic_t)
if ((int)($sysconf['template']['sonic_public_stats'] ?? 1) !== 1) {
    echo '<section class="sonic-container" style="padding:3rem 1.5rem;"><div class="sonic-info-block"><h5>' . sonic_t('Statistics') . '</h5><p style="color:var(--sonic-muted)">' . sonic_t('Public statistics are disabled') . '</p></div></section>';
    return;
}
$S = sonic_cache('public_stats_v1', 300, function () use ($dbs) {
    $s = ['biblio'=>0,'members'=>0,'items'=>0,'loans'=>0,'loans_month'=>0,'visits'=>0,'visits_today'=>0,'monthly'=>[],'gmd'=>[],'popular'=>[],'trending'=>[],'visitors'=>[]];
    if (!isset($dbs) || !is_object($dbs)) return $s;
    $one = function ($sql) use ($dbs) { try { $r = $dbs->query($sql); return $r ? (int)($r->fetch_row()[0] ?? 0) : 0; } catch (Throwable $e) { return 0; } };
    $s['biblio'] = $one('SELECT COUNT(*) FROM biblio');
    $s['members'] = $one("SELECT COUNT(*) FROM member WHERE expire_date >= CURDATE()");
    $s['items'] = $one('SELECT COUNT(*) FROM item');
    $s['loans'] = $one('SELECT COUNT(*) FROM loan');
    $s['loans_month'] = $one("SELECT COUNT(*) FROM loan WHERE YEAR(loan_date)=YEAR(CURDATE()) AND MONTH(loan_date)=MONTH(CURDATE())");
    $vt = null;
    foreach (['visitor_count','visitor'] as $c) { try { $r = $dbs->query("SHOW TABLES LIKE '" . $c . "'"); if ($r && $r->num_rows) { $vt = $c; break; } } catch (Throwable $e) {} }
    if ($vt) { $s['visits'] = $one("SELECT COUNT(*) FROM `$vt`"); $s['visits_today'] = $one("SELECT COUNT(*) FROM `$vt` WHERE DATE(checkin_date)=CURDATE()"); }
    for ($i = 5; $i >= 0; $i--) $s['monthly'][date('Y-m', strtotime("-$i month"))] = 0;
    try { $r = $dbs->query("SELECT DATE_FORMAT(loan_date,'%Y-%m') ym, COUNT(*) c FROM loan WHERE loan_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym"); if ($r) while ($row = $r->fetch_assoc()) { if (isset($s['monthly'][$row['ym']])) $s['monthly'][$row['ym']] = (int)$row['c']; } } catch (Throwable $e) {}
    try { $r = $dbs->query('SELECT g.gmd_name n, COUNT(b.biblio_id) c FROM mst_gmd g LEFT JOIN biblio b ON b.gmd_id=g.gmd_id GROUP BY g.gmd_name ORDER BY c DESC LIMIT 8'); if ($r) while ($row = $r->fetch_assoc()) $s['gmd'][] = ['n'=>$row['n'],'c'=>(int)$row['c']]; } catch (Throwable $e) {}
    $s['popular'] = function_exists('getPopularBiblio') ? getPopularBiblio($dbs, 6) : [];
    $s['trending'] = function_exists('getTrendingBiblio') ? getTrendingBiblio($dbs, 6) : [];
    $s['visitors'] = function_exists('getTopVisitors') ? getTopVisitors($dbs, 6) : [];
    return $s;
});
$mmax = max(1, max($S['monthly'] ?: [0]));
$gmax = max(1, max(array_column($S['gmd'], 'c') ?: [0]));
$sonic_stats_thumb = function ($img) { return !empty($img) ? './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . $img) . '&width=120' : './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=120'; };
?>
<section class="sonic-container sonic-stats-page" style="padding:2.5rem 1.5rem 4rem;">
    <h1 class="sonic-section-title"><i class="fas fa-chart-line"></i> <?= sonic_t('Library Statistics') ?></h1>
    <p class="sonic-section-sub"><?= sonic_t('Live overview of our collection and community') ?></p>

    <div class="sonic-stats-cards">
        <div class="sonic-stat-card"><i class="fas fa-book"></i><span class="v"><?= number_format($S['biblio']) ?></span><label><?= sonic_t('Titles') ?></label></div>
        <div class="sonic-stat-card"><i class="fas fa-copy"></i><span class="v"><?= number_format($S['items']) ?></span><label><?= sonic_t('Items') ?></label></div>
        <div class="sonic-stat-card"><i class="fas fa-users"></i><span class="v"><?= number_format($S['members']) ?></span><label><?= sonic_t('Active Members') ?></label></div>
        <div class="sonic-stat-card"><i class="fas fa-exchange-alt"></i><span class="v"><?= number_format($S['loans']) ?></span><label><?= sonic_t('Total Loans') ?></label></div>
        <div class="sonic-stat-card"><i class="fas fa-calendar-check"></i><span class="v"><?= number_format($S['loans_month']) ?></span><label><?= sonic_t('Loans This Month') ?></label></div>
        <div class="sonic-stat-card"><i class="fas fa-door-open"></i><span class="v"><?= number_format($S['visits']) ?></span><label><?= sonic_t('Visits') ?></label></div>
    </div>

    <div class="sonic-stats-grid">
        <div class="sonic-info-block">
            <h5><i class="fas fa-chart-bar"></i> <?= sonic_t('Loans — last 6 months') ?></h5>
            <div class="sonic-bars">
                <?php foreach ($S['monthly'] as $ym => $c): ?>
                <div class="sonic-bar-row"><span class="lbl"><?= htmlspecialchars($ym) ?></span><span class="track"><span class="fill" style="width:<?= round($c / $mmax * 100) ?>%"></span></span><span class="val"><?= (int)$c ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="sonic-info-block">
            <h5><i class="fas fa-layer-group"></i> <?= sonic_t('Collection by GMD') ?></h5>
            <div class="sonic-bars">
                <?php foreach ($S['gmd'] as $g): ?>
                <div class="sonic-bar-row"><span class="lbl"><?= htmlspecialchars($g['n']) ?></span><span class="track"><span class="fill alt" style="width:<?= round($g['c'] / $gmax * 100) ?>%"></span></span><span class="val"><?= (int)$g['c'] ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="sonic-stats-grid three">
        <div class="sonic-info-block"><h5><i class="fas fa-fire"></i> <?= sonic_t('Popular') ?></h5>
            <ul class="sonic-stats-list"><?php foreach ($S['popular'] as $b): ?><li><a href="index.php?p=show_detail&amp;id=<?= (int)$b['biblio_id'] ?>"><img loading="lazy" src="<?= $sonic_stats_thumb($b['image'] ?? '') ?>" alt="" onerror="this.src='./images/default/image.png'"><span><?= htmlspecialchars($b['title']) ?></span></a></li><?php endforeach; ?></ul>
        </div>
        <div class="sonic-info-block"><h5><i class="fas fa-bolt"></i> <?= sonic_t('Trending 30 days') ?></h5>
            <ul class="sonic-stats-list"><?php foreach ($S['trending'] as $b): ?><li><a href="index.php?p=show_detail&amp;id=<?= (int)$b['biblio_id'] ?>"><img loading="lazy" src="<?= $sonic_stats_thumb($b['image'] ?? '') ?>" alt="" onerror="this.src='./images/default/image.png'"><span><?= htmlspecialchars($b['title']) ?></span></a></li><?php endforeach; ?></ul>
        </div>
        <div class="sonic-info-block"><h5><i class="fas fa-medal"></i> <?= sonic_t('Top Visitors') ?></h5>
            <ul class="sonic-stats-list visitors"><?php foreach ($S['visitors'] as $i => $v): ?><li><em class="rank"><?= $i + 1 ?></em><span><?= htmlspecialchars($v['member_name'] ?? '') ?></span><b><?= (int)($v['total'] ?? 0) ?></b></li><?php endforeach; ?></ul>
        </div>
    </div>
</section>