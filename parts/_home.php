<?php
# @Filename: _home.php (SONIC v4 FINAL — i18n ready, Dewey images, map included)
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
        <h3 class="sonic-section-title"><?= sonic_t('Select the topic you are interested in') ?></h3>
        <p class="sonic-section-sub"><?= sonic_t('Navigate through knowledge domains') ?></p>
        <ul class="sonic-topics">
            <li><a href="index.php?callnumber=8&search=search" class="sonic-topic">
                <div class="sonic-topic-icon"><img src="<?= assets('images/8-books.png') ?>" alt="" onerror="this.style.display='none'"></div>
                <span class="sonic-topic-label"><?= sonic_t('Literature') ?></span></a></li>
            <li><a href="index.php?callnumber=3&search=search" class="sonic-topic">
                <div class="sonic-topic-icon"><img src="<?= assets('images/3-diploma.png') ?>" alt="" onerror="this.style.display='none'"></div>
                <span class="sonic-topic-label"><?= sonic_t('Social Sciences') ?></span></a></li>
            <li><a href="index.php?callnumber=6&search=search" class="sonic-topic">
                <div class="sonic-topic-icon"><img src="<?= assets('images/6-blackboard.png') ?>" alt="" onerror="this.style.display='none'"></div>
                <span class="sonic-topic-label"><?= sonic_t('Applied Sciences') ?></span></a></li>
            <li><a href="index.php?callnumber=7&search=search" class="sonic-topic">
                <div class="sonic-topic-icon"><img src="<?= assets('images/7-quill.png') ?>" alt="" onerror="this.style.display='none'"></div>
                <span class="sonic-topic-label"><?= sonic_t('Art & Recreation') ?></span></a></li>
            <li><a href="javascript:void(0)" class="sonic-topic" onclick="document.getElementById('exampleModal').classList.add('active');return false;">
                <div class="sonic-topic-icon"><i class="fas fa-th"></i></div>
                <span class="sonic-topic-label"><?= sonic_t('see more..') ?></span></a></li>
        </ul>
    </section>

    <!-- POPULER (server-side) -->
    <?php if (count($sonic_popular) > 0): ?>
    <section class="sonic-section">
        <h3 class="sonic-section-title"><i class="fas fa-fire" style="color:var(--sonic-plasma);margin-right:.5rem;"></i><?= sonic_t('Popular among our collection') ?></h3>
        <p class="sonic-section-sub"><?= sonic_t("Our library's top-borrowed titles") ?></p>
        <div class="sonic-biblio-grid">
            <?php foreach ($sonic_popular as $b): ?>
            <a class="sonic-book" href="index.php?p=show_detail&id=<?= (int)$b['biblio_id'] ?>">
                <div class="sonic-book-cover">
                    <img loading="lazy" src="<?= sonic_cover($b['image'] ?? '') ?>" alt="<?= htmlspecialchars($b['title']) ?>" onerror="this.src='./images/default/image.png'">
                </div>
                <h4 class="sonic-book-title"><?= htmlspecialchars($b['title']) ?></h4>
                <p class="sonic-book-author"><i class="fas fa-book-reader"></i> <?= (int)($b['total'] ?? 0) ?>x <?= sonic_t('borrowed') ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- TERBARU (server-side) -->
    <?php if (count($sonic_latest) > 0): ?>
    <section class="sonic-section">
        <h3 class="sonic-section-title"><i class="fas fa-bolt" style="color:var(--sonic-neon);margin-right:.5rem;"></i><?= sonic_t('New collections + updated') ?></h3>
        <p class="sonic-section-sub"><?= sonic_t('Fresh additions to our database') ?></p>
        <div class="sonic-biblio-grid">
            <?php foreach ($sonic_latest as $b): ?>
            <a class="sonic-book" href="index.php?p=show_detail&id=<?= (int)$b['biblio_id'] ?>">
                <div class="sonic-book-cover">
                    <img loading="lazy" src="<?= sonic_cover($b['image'] ?? '') ?>" alt="<?= htmlspecialchars($b['title']) ?>" onerror="this.src='./images/default/image.png'">
                </div>
                <h4 class="sonic-book-title"><?= htmlspecialchars($b['title']) ?></h4>
                <p class="sonic-book-author"><i class="fas fa-clock"></i> <?= sonic_t('updated') ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- MAP (dari Kustomisasi) -->
    <?php include __DIR__ . '/_map.php'; ?>

    <!-- TOP READER (server-side) -->
    <?php if (count($sonic_readers) > 0): ?>
    <section class="sonic-section">
        <h3 class="sonic-section-title"><i class="fas fa-trophy" style="color:#ffd700;margin-right:.5rem;"></i><?= sonic_t('Top reader of the year') ?></h3>
        <p class="sonic-section-sub"><?= sonic_t('Our most active scholars') ?></p>
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

</div>