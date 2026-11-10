<?php
# @Filename: _member-dashboard.php (SONIC Mission 3 — Member Dashboard Modern)
if (empty($is_login)) return;
$m_id = $_SESSION['m_id'] ?? ($_SESSION['member_id'] ?? null);
if (!$m_id || !isset($dbs) || !is_object($dbs)) return;
$mid = $dbs->escape_string($m_id);
$today = date('Y-m-d');

$dash = ['current' => [], 'history' => [], 'genres' => []];
$total_loans = 0; $ontime = 0; $returned = 0; $genre_distinct = 0; $wish = [];

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

    $bm = array_keys($_SESSION['bookmark'] ?? []);
    if (count($bm)) {
        $ids = implode(',', array_map('intval', $bm));
        $q = $dbs->query("SELECT biblio_id, title, image FROM biblio WHERE biblio_id IN ({$ids}) LIMIT 6");
        if ($q) while ($r = $q->fetch_assoc()) $wish[] = $r;
    }
} catch (Throwable $e) { /* silent */ }

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

/* Achievements */
$ach = [
    ['fa-rocket', 'First Borrow',  'First loan unlocked',        $total_loans >= 1],
    ['fa-book',   'Bookworm 10+',  '10+ total loans',            $total_loans >= 10],
    ['fa-globe',  'Genre Explorer','3+ different subjects',      $genre_distinct >= 3],
    ['fa-clock',  'On-Time Hero',  '100% on-time (3+ returned)', $returned >= 3 && $ontime === $returned],
    ['fa-fire',   'Active Reader', 'Currently borrowing',        count($dash['current']) >= 1],
    ['fa-heart',  'Curator',       '3+ bookmarked wishlist',     count($wish) >= 3],
];

function sonic_dash_thumb($img) {
    return !empty($img)
        ? './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . $img) . '&width=120'
        : './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=120';
}
function sonic_dash_status($row, $today) {
    if (empty($row['return_date'])) {
        return (!empty($row['due_date']) && $row['due_date'] < $today) ? ['st-overdue', 'Overdue'] : ['st-borrowed', 'Borrowed'];
    }
    return ($row['return_date'] <= $row['due_date']) ? ['st-ontime', 'On Time'] : ['st-late', 'Late'];
}
?>

<section class="sonic-dash">

    <?php if (count($overdue) > 0): ?>
    <div class="sonic-dash-notice danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div><b><?= count($overdue) ?> loan overdue!</b> Please return: <?= htmlspecialchars(implode(', ', array_map(fn($o) => $o['title'], array_slice($overdue, 0, 3)))) ?></div>
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
    </div>

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

    <?php if (count($wish) > 0): ?>
    <div class="sonic-dash-card" style="--d:320ms">
        <h5><i class="fas fa-heart"></i> Your Wishlist</h5>
        <div class="sonic-wish-row">
            <?php foreach ($wish as $w): ?>
            <a class="sonic-wish-item" href="index.php?p=show_detail&id=<?= (int)$w['biblio_id'] ?>" title="<?= htmlspecialchars($w['title']) ?>">
                <img loading="lazy" src="<?= sonic_dash_thumb($w['image'] ?? '') ?>" alt="cover" onerror="this.src='./images/default/image.png'">
                <span><?= htmlspecialchars($w['title']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</section>