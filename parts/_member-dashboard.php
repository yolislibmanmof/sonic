<?php
# @Filename: _member-dashboard.php (SONIC Mission 3 + BATCH 6: denda real-time, kartu/CSV, wishlist→reservasi, gamifikasi)
if (empty($is_login)) return;
$m_id = $_SESSION['mid'] ?? ($_SESSION['m_id'] ?? ($_SESSION['member_id'] ?? null));
if (!$m_id || !isset($dbs) || !is_object($dbs)) return;
$mid = $dbs->escape_string($m_id);
$today = date('Y-m-d');

$dash = ['current' => [], 'history' => [], 'genres' => []];
$total_loans = 0; $ontime = 0; $returned = 0; $genre_distinct = 0; $wish = []; $reserves = [];

try {
    $q = $dbs->query("SELECT l.loan_date, l.due_date, b.biblio_id, b.title, b.image
                      FROM loan l JOIN item i ON l.item_code=i.item_code JOIN biblio b ON i.biblio_id=b.biblio_id
                      WHERE l.member_id='{$mid}' AND l.is_lent=1 AND l.is_return=0 ORDER BY l.due_date ASC LIMIT 10");
    if ($q) while ($r = $q->fetch_assoc()) $dash['current'][] = $r;

    $q = $dbs->query("SELECT l.loan_date, l.due_date, l.return_date, b.biblio_id, b.title, b.image
                      FROM loan l JOIN item i ON l.item_code=i.item_code JOIN biblio b ON i.biblio_id=b.biblio_id
                      WHERE l.member_id='{$mid}' ORDER BY l.loan_date DESC LIMIT 8");
    if ($q) while ($r = $q->fetch_assoc()) $dash['history'][] = $r;

    $q = $dbs->query("SELECT mt.topic, COUNT(*) AS c FROM loan l
                      JOIN item i ON l.item_code=i.item_code
                      JOIN biblio_topic bt ON i.biblio_id=bt.biblio_id
                      JOIN mst_topic mt ON bt.topic_id=mt.topic_id
                      WHERE l.member_id='{$mid}' GROUP BY mt.topic ORDER BY c DESC LIMIT 5");
    if ($q) while ($r = $q->fetch_assoc()) $dash['genres'][] = $r;

    $q = $dbs->query("SELECT COUNT(*) FROM loan WHERE member_id='{$mid}'");
    if ($q) $total_loans = (int)($q->fetch_row()[0] ?? 0);

    $q = $dbs->query("SELECT COUNT(DISTINCT mt.topic) FROM loan l
                      JOIN item i ON l.item_code=i.item_code
                      JOIN biblio_topic bt ON i.biblio_id=bt.biblio_id
                      JOIN mst_topic mt ON bt.topic_id=mt.topic_id
                      WHERE l.member_id='{$mid}'");
    if ($q) $genre_distinct = (int)($q->fetch_row()[0] ?? 0);

    $q = $dbs->query("SELECT SUM(CASE WHEN l.return_date IS NOT NULL AND l.return_date <> '' AND l.return_date <= l.due_date THEN 1 ELSE 0 END) AS ot,
                             SUM(CASE WHEN l.return_date IS NOT NULL AND l.return_date <> '' THEN 1 ELSE 0 END) AS rt
                      FROM loan l WHERE l.member_id='{$mid}'");
    if ($q && ($r = $q->fetch_assoc())) { $ontime = (int)$r['ot']; $returned = (int)$r['rt']; }

    $q = $dbs->query("SELECT b.biblio_id, b.title, b.image FROM biblio_mark bm JOIN biblio b ON bm.biblio_id=b.biblio_id WHERE bm.member_id='{$mid}' ORDER BY bm.created_at DESC LIMIT 6");
    if ($q) while ($r = $q->fetch_assoc()) $wish[] = $r;

    // Reservasi aktif (tabel reserve)
    $reserves = [];
    try {
        $q = $dbs->query("SELECT r.reserve_date, b.biblio_id, b.title FROM reserve r JOIN biblio b ON r.biblio_id=b.biblio_id WHERE r.member_id='{$mid}' ORDER BY r.reserve_date DESC LIMIT 6");
        if ($q) while ($r = $q->fetch_assoc()) $reserves[] = $r;
    } catch (Throwable $e) {}
} catch (Throwable $e) { /* silent */ }

/* ===== BATCH 6: DENDA real-time (rumus core: fine_each_day x hari telat setelah grace) ===== */
$sonic_fines_on = (int)($sysconf['template']['sonic_fines'] ?? 1) === 1;
$fine_each_day = 0; $grace = 0; $total_fine = 0;
if ($sonic_fines_on) {
    try {
        $q = $dbs->query("SELECT mt.fine_each_day, mt.grace_periode FROM member m LEFT JOIN mst_member_type mt ON m.member_type_id=mt.member_type_id WHERE m.member_id='{$mid}'");
        if ($q && ($r = $q->fetch_assoc())) { $fine_each_day = (int)($r['fine_each_day'] ?? 0); $grace = (int)($r['grace_periode'] ?? 0); }
    } catch (Throwable $e) {}
    foreach ($dash['current'] as &$c) {
        $days_late = 0; $fine = 0;
        if (!empty($c['due_date']) && $c['due_date'] < $today) {
            $days_late = (int)floor((strtotime($today) - strtotime($c['due_date'])) / 86400);
            $fine = max(0, $days_late - $grace) * $fine_each_day;
        }
        $c['days_late'] = $days_late; $c['fine'] = $fine; $total_fine += $fine;
    }
    unset($c);
} else {
    foreach ($dash['current'] as &$c) { $c['days_late'] = 0; $c['fine'] = 0; }
    unset($c);
}

/* ===== BATCH 6: GAMIFIKASI — streak bulanan & pinjaman tahun ini ===== */
$sonic_quest_on = (int)($sysconf['template']['sonic_reading_quest'] ?? 1) === 1;
$streak = 0; $year_loans = 0;
if ($sonic_quest_on) {
    try {
        $months = [];
        $q = $dbs->query("SELECT DISTINCT DATE_FORMAT(loan_date,'%Y-%m') ym FROM loan WHERE member_id='{$mid}' ORDER BY ym DESC");
        if ($q) while ($r = $q->fetch_row()) $months[] = $r[0];
        if ($months) {
            $nums = array_map(fn($ym) => (int)substr($ym, 0, 4) * 12 + (int)substr($ym, 5, 2), $months);
            $nowNum = (int)date('Y') * 12 + (int)date('n');
            if ($nowNum - $nums[0] <= 1) { // streak hidup bila bulan terakhir = sekarang / sebelumnya
                $set = array_flip($nums); $cnum = $nums[0];
                while (isset($set[$cnum])) { $streak++; $cnum--; }
            }
        }
        $q = $dbs->query("SELECT COUNT(*) FROM loan WHERE member_id='{$mid}' AND YEAR(loan_date)=YEAR(CURDATE())");
        if ($q) $year_loans = (int)($q->fetch_row()[0] ?? 0);
    } catch (Throwable $e) {}
}

$sonic_wish_reserve_on = (int)($sysconf['template']['sonic_wish_reserve'] ?? 1) === 1;

$ontime_pct = $returned > 0 ? (int)round($ontime / $returned * 100) : 0;
$overdue = array_values(array_filter($dash['current'], fn($c) => !empty($c['due_date']) && $c['due_date'] < $today));
$soon    = array_values(array_filter($dash['current'], fn($c) => !empty($c['due_date']) && $c['due_date'] >= $today && $c['due_date'] <= date('Y-m-d', strtotime('+3 days'))));

/* Donut genre (conic-gradient murni CSS) */
$donut_colors = ['#00f0ff', '#ff00aa', '#7a5cff', '#00ff88'];
$genre_total = array_sum(array_column($dash['genres'], 'c'));
$grad_parts = []; $acc = 0;
foreach ($dash['genres'] as $gi => $g) {
    $deg = $genre_total > 0 ? (int)round($g['c'] / $genre_total * 360) : 0;
    $grad_parts[] = $donut_colors[$gi % 4] . " {$acc}deg " . ($acc + $deg) . "deg";
    $acc += $deg;
}
if ($acc < 360) $grad_parts[] = "rgba(255,255,255,.12) {$acc}deg 360deg";
$donut_bg = 'conic-gradient(' . implode(',', $grad_parts) . ')';
$top_pct = ($genre_total > 0 && count($dash['genres'])) ? (int)round($dash['genres'][0]['c'] / $genre_total * 100) : 0;

/* Achievements (BATCH 6: +2 badge gamifikasi) */
$ach = [
    ['fa-rocket', 'First Borrow',  'First loan unlocked',        $total_loans >= 1],
    ['fa-book',   'Bookworm 10+',  '10+ total loans',            $total_loans >= 10],
    ['fa-globe',  'Genre Explorer','3+ different subjects',      $genre_distinct >= 3],
    ['fa-clock',  'On-Time Hero',  '100% on-time (3+ returned)', $returned >= 3 && $ontime === $returned],
    ['fa-fire',   'Active Reader', 'Currently borrowing',        count($dash['current']) >= 1],
    ['fa-heart',  'Curator',       '3+ bookmarked wishlist',     count($wish) >= 3],
    ['fa-fire',   'Streak 3+',     '3+ month reading streak',    $streak >= 3],
    ['fa-calendar-check', 'Yearly 12+', '12+ loans this year',   $year_loans >= 12],
];

if (!function_exists('sonic_dash_thumb')) {
    function sonic_dash_thumb($img) {
        return !empty($img)
            ? './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . $img) . '&width=120'
            : './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=120';
    }
}
if (!function_exists('sonic_dash_status')) {
    function sonic_dash_status($row, $today) {
        if (empty($row['return_date'])) {
            return (!empty($row['due_date']) && $row['due_date'] < $today) ? ['st-overdue', 'Overdue'] : ['st-borrowed', 'Borrowed'];
        }
        return ($row['return_date'] <= $row['due_date']) ? ['st-ontime', 'On Time'] : ['st-late', 'Late'];
    }
}
?>

<section class="sonic-dash">

    <?php if (count($overdue) > 0): ?>
    <div class="sonic-dash-notice danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div><b><?= count($overdue) ?> loan overdue!</b> Please return: <?= htmlspecialchars(implode(', ', array_map(fn($o) => $o['title'], array_slice($overdue, 0, 3)))) ?><?php if ($sonic_fines_on && $total_fine > 0): ?> · <?= __('Denda') ?>: <b>Rp <?= number_format($total_fine, 0, ',', '.') ?></b><?php endif; ?></div>
    </div>
    <?php elseif (count($soon) > 0): ?>
    <div class="sonic-dash-notice warn">
        <i class="far fa-clock"></i>
        <div><b>Due soon:</b> <?= htmlspecialchars(implode(', ', array_map(fn($o) => $o['title'], array_slice($soon, 0, 3)))) ?> — due by <?= htmlspecialchars($soon[0]['due_date']) ?></div>
    </div>
    <?php endif; ?>

    <div class="sonic-dash-stats">
        <div class="sonic-dash-stat" style="--d:0ms"><i class="fas fa-book-reader"></i><span class="v"><?= count($dash['current']) ?></span><label>Borrowed</label></div>
        <div class="sonic-dash-stat" style="--d:80ms"><i class="fas fa-history"></i><span class="v"><?= $total_loans ?></span><label>Total Loans</label></div>
        <div class="sonic-dash-stat" style="--d:160ms"><i class="fas fa-bullseye"></i><span class="v"><?= $ontime_pct ?><small>%</small></span><label>On-Time Rate</label></div>
        <div class="sonic-dash-stat" style="--d:240ms"><i class="fas fa-heart"></i><span class="v"><?= count($wish) ?></span><label>Wishlist</label></div>
        <?php if ($sonic_fines_on): ?>
        <div class="sonic-dash-stat <?= $total_fine > 0 ? 'fine' : '' ?>" style="--d:320ms"><i class="fas fa-coins"></i><span class="v"><?= number_format($total_fine, 0, ',', '.') ?></span><label><?= __('Denda (Rp)') ?></label></div>
        <?php endif; ?>
    </div>

    <?php if ($sonic_fines_on && count($dash['current']) > 0): ?>
    <!-- BATCH 6: Pinjaman aktif + jatuh tempo + denda per item -->
    <div class="sonic-dash-card" style="--d:180ms">
        <h5><i class="fas fa-book-open"></i> <?= __('Pinjaman Aktif & Denda') ?></h5>
        <div class="sonic-loan-list">
            <?php foreach ($dash['current'] as $c):
                $isOver = !empty($c['due_date']) && $c['due_date'] < $today;
                $daysLeft = !empty($c['due_date']) ? (int)floor((strtotime($c['due_date']) - strtotime($today)) / 86400) : 0; ?>
            <div class="sonic-loan-row <?= $isOver ? 'over' : '' ?>">
                <div class="sonic-loan-cover"><img loading="lazy" src="<?= sonic_dash_thumb($c['image'] ?? '') ?>" alt="" onerror="this.src='./images/default/image.png'"></div>
                <div class="sonic-loan-info">
                    <a class="sonic-loan-title" href="index.php?p=show_detail&id=<?= (int)$c['biblio_id'] ?>"><?= htmlspecialchars($c['title']) ?></a>
                    <div class="sonic-loan-due"><?= __('Jatuh tempo') ?>: <?= htmlspecialchars($c['due_date']) ?>
                        <?php if ($isOver): ?> · <span class="late"><?= (int)$c['days_late'] ?> <?= __('hari telat') ?></span>
                        <?php else: ?> · <span class="ok"><?= $daysLeft ?> <?= __('hari lagi') ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="sonic-loan-fine"><?= (int)$c['fine'] > 0 ? 'Rp ' . number_format((int)$c['fine'], 0, ',', '.') : '—' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($total_fine > 0): ?><div class="sonic-fine-total"><?= __('Total denda berjalan') ?>: <b>Rp <?= number_format($total_fine, 0, ',', '.') ?></b></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="sonic-dash-grid">
        <div class="sonic-dash-card" style="--d:120ms">
            <h5><i class="fas fa-dna"></i> Genre DNA</h5>
            <?php if (count($dash['genres']) > 0): ?>
            <div class="sonic-donut-wrap">
                <div class="sonic-donut" style="background:<?= $donut_bg ?>">
                    <div class="sonic-donut-hole">
                        <span class="pct"><?= $top_pct ?>%</span>
                        <label><?= htmlspecialchars($dash['genres'][0]['topic']) ?></label>
                    </div>
                </div>
                <ul class="sonic-donut-legend">
                    <?php foreach ($dash['genres'] as $gi => $g): ?>
                    <li><span class="dot" style="background:<?= $donut_colors[$gi % 4] ?>"></span><?= htmlspecialchars($g['topic']) ?><span class="c"><?= (int)$g['c'] ?>x</span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
            <p class="sonic-donut-empty">Borrow a book to reveal your Genre DNA.</p>
            <?php endif; ?>
        </div>

        <div class="sonic-dash-card" style="--d:200ms">
            <h5><i class="fas fa-trophy"></i> Achievements</h5>
            <div class="sonic-badges">
                <?php foreach ($ach as $a): ?>
                <div class="sonic-badge-ach <?= $a[3] ? 'on' : 'off' ?>" title="<?= $a[3] ? 'Unlocked' : 'Locked' ?>">
                    <i class="fas <?= $a[0] ?>"></i>
                    <span><?= $a[1] ?></span>
                    <small><?= $a[2] ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($sonic_quest_on): ?>
    <!-- BATCH 6: Reading Quest — streak bulanan + target baca tahunan (target disimpan di perangkat) -->
    <div class="sonic-dash-card sonic-quest" id="sonicReadingQuest" data-year-count="<?= (int)$year_loans ?>" data-member-id="<?= htmlspecialchars($m_id) ?>" style="--d:240ms">
        <h5><i class="fas fa-fire"></i> Reading Quest</h5>
        <div class="sonic-quest-streak"><i class="fas fa-fire"></i> <b><?= (int)$streak ?></b> <?= __('bulan berturut-turut') ?></div>
        <div class="sonic-quest-goal">
            <div class="sonic-quest-goal-head">
                <span><?= __('Target baca tahunan') ?></span>
                <span class="sonic-quest-count"><b id="sonicQuestDone"><?= (int)$year_loans ?></b> / <span id="sonicQuestTarget">12</span></span>
            </div>
            <div class="sonic-quest-bar"><span id="sonicQuestFill" style="width:0%"></span></div>
            <label class="sonic-quest-set"><?= __('Atur target') ?>: <input type="number" id="sonicQuestInput" min="1" max="365" value="12"></label>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($dash['history']) > 0): ?>
    <div class="sonic-dash-card" style="--d:260ms">
        <h5><i class="fas fa-stream"></i> Reading Timeline</h5>
        <div class="sonic-timeline">
            <?php foreach ($dash['history'] as $hi => $h): $st = sonic_dash_status($h, $today); ?>
            <div class="sonic-tl-item <?= $st[0] === 'st-late' || $st[0] === 'st-overdue' ? 'late' : '' ?>" style="--d:<?= $hi * 70 ?>ms">
                <div class="sonic-tl-head">
                    <div class="sonic-tl-cover"><img loading="lazy" src="<?= sonic_dash_thumb($h['image'] ?? '') ?>" alt="cover" onerror="this.src='./images/default/image.png'"></div>
                    <div>
                        <a class="sonic-tl-title" href="index.php?p=show_detail&id=<?= (int)$h['biblio_id'] ?>"><?= htmlspecialchars($h['title']) ?></a>
                        <div class="sonic-tl-date"><?= htmlspecialchars($h['loan_date']) ?> → <?= htmlspecialchars($h['due_date']) ?><?php if (!empty($h['return_date'])): ?> · returned <?= htmlspecialchars($h['return_date']) ?><?php endif; ?></div>
                    </div>
                    <span class="sonic-tl-status <?= $st[0] ?>"><?= $st[1] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($reserves) > 0): ?>
    <div class="sonic-dash-card" style="--d:300ms">
        <h5><i class="fas fa-bookmark"></i> <?= __('My Reservations') ?></h5>
        <div class="sonic-timeline">
            <?php foreach ($reserves as $hi => $rv): ?>
            <div class="sonic-tl-item" style="--d:<?= $hi * 60 ?>ms">
                <div class="sonic-tl-head">
                    <div>
                        <a class="sonic-tl-title" href="index.php?p=show_detail&id=<?= (int)$rv['biblio_id'] ?>"><?= htmlspecialchars($rv['title']) ?></a>
                        <div class="sonic-tl-date"><?= __('Reserved') ?>: <?= htmlspecialchars($rv['reserve_date']) ?></div>
                    </div>
                    <span class="sonic-tl-status st-borrowed"><?= __('Reserved') ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($wish) > 0): ?>
    <div class="sonic-dash-card" style="--d:320ms">
        <h5><i class="fas fa-heart"></i> Your Wishlist</h5>
        <div class="sonic-wish-row">
            <?php foreach ($wish as $w): ?>
            <div class="sonic-wish-item">
                <a href="index.php?p=show_detail&id=<?= (int)$w['biblio_id'] ?>" title="<?= htmlspecialchars($w['title']) ?>">
                    <img loading="lazy" src="<?= sonic_dash_thumb($w['image'] ?? '') ?>" alt="cover" onerror="this.src='./images/default/image.png'">
                    <span><?= htmlspecialchars($w['title']) ?></span>
                </a>
                <?php if ($sonic_wish_reserve_on): ?>
                <button type="button" class="sonic-mini-btn sonic-wish-reserve" data-biblio="<?= (int)$w['biblio_id'] ?>"><i class="fas fa-bookmark"></i> <?= __('Reserve') ?></button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- BATCH 6: Ekspor CSV (endpoint core, tanpa modifikasi core) -->
    <div class="sonic-dash-card sonic-export" style="--d:340ms">
        <h5><i class="fas fa-file-csv"></i> <?= __('Ekspor Data') ?></h5>
        <div class="sonic-export-btns">
            <a class="sonic-mini-btn" href="index.php?p=download_loan_history"><i class="fas fa-download"></i> <?= __('Riwayat Pinjaman (CSV)') ?></a>
            <a class="sonic-mini-btn" href="index.php?p=download_current_loan"><i class="fas fa-download"></i> <?= __('Pinjaman Aktif (CSV)') ?></a>
        </div>
    </div>

</section>