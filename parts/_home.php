<?php
# @Filename: _home.php (SONIC v3 — server-side, anti-gagal)
include '_navbar.php';
include '_search-form.php';

if (!function_exists('sonic_cover')) {
    function sonic_cover($image, $width = 240, $folder = 'docs') {
        if (!empty($image)) {
            return './lib/minigalnano/createthumb.php?filename=' . urlencode('images/' . $folder . '/' . $image) . '&width=' . $width;
        }
        return './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=' . $width;
    }
}

$sonic_popular = []; $sonic_latest = []; $sonic_readers = [];
try {
    if (!empty($sysconf['template']['sonic_popular_collection']))
        $sonic_popular = getPopularBiblio($dbs, (int)($sysconf['template']['sonic_popular_collection_limit'] ?? 10));
    if (!empty($sysconf['template']['sonic_new_collection']))
        $sonic_latest = getLatestBiblio($dbs, (int)($sysconf['template']['sonic_new_collection_limit'] ?? 10));
    if (!empty($sysconf['template']['sonic_top_reader']))
        $sonic_readers = getActiveMembers($dbs, date('Y'), 3);
} catch (Throwable $e) { /* tetap aman */ }
?>

<div id="slims-home" class="sonic-container">

    <!-- TOPIK -->
    <section class="sonic-section">
        <h3 class="sonic-section-title"><?= __('Select the topic you are interested in') ?></h3>
        <p class="sonic-section-sub"><?= __('Navigate through knowledge domains') ?></p>
        <ul class="sonic-topics">
            <li><a href="index.php?callnumber=8&search=search" class="sonic-topic">
                <div class="sonic-topic-icon"><i class="fas fa-book-open"></i></div>
                <span class="sonic-topic-label"><?= __('Literature') ?></span></a></li>
            <li><a href="index.php?callnumber=3&search=search" class="sonic-topic">
                <div class="sonic-topic-icon"><i class="fas fa-graduation-cap"></i></div>
                <span class="sonic-topic-label"><?= __('Social Sciences') ?></span></a></li>
            <li><a href="index.php?callnumber=6&search=search" class="sonic-topic">
                <div class="sonic-topic-icon"><i class="fas fa-flask"></i></div>
                <span class="sonic-topic-label"><?= __('Applied Sciences') ?></span></a></li>
            <li><a href="index.php?callnumber=7&search=search" class="sonic-topic">
                <div class="sonic-topic-icon"><i class="fas fa-palette"></i></div>
                <span class="sonic-topic-label"><?= __('Art & Recreation') ?></span></a></li>
            <li><a href="javascript:void(0)" class="sonic-topic" data-toggle="modal" data-target="#exampleModal">
                <div class="sonic-topic-icon"><i class="fas fa-border-all"></i></div>
                <span class="sonic-topic-label"><?= __('see more..') ?></span></a></li>
        </ul>
    </section>

    <!-- POPULER (server-side) -->
    <?php if (count($sonic_popular) > 0): ?>
    <section class="sonic-section">
        <h3 class="sonic-section-title"><i class="fas fa-fire" style="color:var(--sonic-plasma);margin-right:.5rem;"></i><?= __('Popular among our collection') ?></h3>
        <p class="sonic-section-sub"><?= __("Our library's top-borrowed titles") ?></p>
        <div class="sonic-biblio-grid">
            <?php foreach ($sonic_popular as $b): ?>
            <a class="sonic-book" href="index.php?p=show_detail&id=<?= (int)$b['biblio_id'] ?>">
                <div class="sonic-book-cover">
                    <img loading="lazy" src="<?= sonic_cover($b['image'] ?? '') ?>" alt="<?= htmlspecialchars($b['title']) ?>" onerror="this.src='./images/default/image.png'">
                </div>
                <h4 class="sonic-book-title"><?= htmlspecialchars($b['title']) ?></h4>
                <p class="sonic-book-author"><i class="fas fa-book-reader"></i> <?= (int)($b['total'] ?? 0) ?>x <?= __('borrowed') ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- TERBARU (server-side) -->
    <?php if (count($sonic_latest) > 0): ?>
    <section class="sonic-section">
        <h3 class="sonic-section-title"><i class="fas fa-bolt" style="color:var(--sonic-neon);margin-right:.5rem;"></i><?= __('New collections + updated') ?></h3>
        <p class="sonic-section-sub"><?= __('Fresh additions to our database') ?></p>
        <div class="sonic-biblio-grid">
            <?php foreach ($sonic_latest as $b): ?>
            <a class="sonic-book" href="index.php?p=show_detail&id=<?= (int)$b['biblio_id'] ?>">
                <div class="sonic-book-cover">
                    <img loading="lazy" src="<?= sonic_cover($b['image'] ?? '') ?>" alt="<?= htmlspecialchars($b['title']) ?>" onerror="this.src='./images/default/image.png'">
                </div>
                <h4 class="sonic-book-title"><?= htmlspecialchars($b['title']) ?></h4>
                <p class="sonic-book-author"><i class="fas fa-clock"></i> <?= __('updated') ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- TOP READER (server-side) -->
    <?php if (count($sonic_readers) > 0): ?>
    <section class="sonic-section">
        <h3 class="sonic-section-title"><i class="fas fa-trophy" style="color:#ffd700;margin-right:.5rem;"></i><?= __('Top reader of the year') ?></h3>
        <p class="sonic-section-sub"><?= __('Our most active scholars') ?></p>
        <ul class="sonic-topics">
            <?php foreach ($sonic_readers as $m): ?>
            <li>
                <div class="sonic-topic" style="cursor:default;">
                    <div class="sonic-topic-icon" style="border-radius:50%;overflow:hidden;">
                        <img src="<?= sonic_cover($m['image'] ?? 'person.png', 120, 'persons') ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='./images/persons/person.png'">
                    </div>
                    <span class="sonic-topic-label"><?= htmlspecialchars($m['name']) ?></span>
                    <span class="sonic-tag" style="cursor:default;"><i class="fas fa-book-reader"></i> <?= (int)$m['total'] ?> · <?= (int)$m['total_title'] ?></span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- PETA (opsional) -->
    <?php if (!empty($sysconf['template']['sonic_map'])): ?>
    <section class="sonic-section">
        <div class="sonic-info-block" style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:center;">
            <iframe src="<?= $sysconf['template']['sonic_map_link'] ?>" height="420" frameborder="0" allowfullscreen></iframe>
            <div>
                <h3 class="sonic-section-title"><?= $sysconf['library_name'] ?></h3>
                <p style="margin-bottom:1.5rem;"><?= $sysconf['template']['sonic_map_desc'] ?></p>
                <div class="sonic-book-actions">
                    <?php if (!empty($sysconf['template']['sonic_fb_link'])): ?><a target="_blank" href="<?= $sysconf['template']['sonic_fb_link'] ?>" class="sonic-mini-btn"><i class="fab fa-facebook-square"></i> Facebook</a><?php endif; ?>
                    <?php if (!empty($sysconf['template']['sonic_twitter_link'])): ?><a target="_blank" href="<?= $sysconf['template']['sonic_twitter_link'] ?>" class="sonic-mini-btn"><i class="fab fa-twitter"></i> Twitter</a><?php endif; ?>
                    <?php if (!empty($sysconf['template']['sonic_youtube_link'])): ?><a target="_blank" href="<?= $sysconf['template']['sonic_youtube_link'] ?>" class="sonic-mini-btn"><i class="fab fa-youtube"></i> YouTube</a><?php endif; ?>
                    <?php if (!empty($sysconf['template']['sonic_instagram_link'])): ?><a target="_blank" href="<?= $sysconf['template']['sonic_instagram_link'] ?>" class="sonic-mini-btn"><i class="fab fa-instagram"></i> Instagram</a><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

</div>