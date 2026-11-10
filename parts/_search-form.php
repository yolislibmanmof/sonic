<?php
# @Filename: _search-form.php (SONIC v2)
$sonic_compact = !empty($sonic_compact_hero);

// Scan folder slider: assets/images/slider/
$slider_dir = __DIR__ . '/../assets/images/slider';
$slider_imgs = [];
if (is_dir($slider_dir)) {
    foreach (scandir($slider_dir) as $sf) {
        if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $sf)) {
            $slider_imgs[] = assets('images/slider/' . $sf);
        }
    }
}
?>

<section class="sonic-hero<?= $sonic_compact ? ' compact' : '' ?>" id="sonicHero">

    <?php if (!$sonic_compact && count($slider_imgs) > 0): ?>
    <!-- ===== PHOTO SLIDER ===== -->
    <div class="sonic-slider" id="sonicSlider">
        <?php foreach ($slider_imgs as $si => $src): ?>
            <div class="sonic-slide<?= $si === 0 ? ' active' : '' ?>" style="background-image:url('<?= $src ?>')"></div>
        <?php endforeach; ?>
        <div class="sonic-slider-overlay"></div>
        <div class="sonic-hud hud-tl"></div>
        <div class="sonic-hud hud-tr"></div>
        <div class="sonic-hud hud-bl"></div>
        <div class="sonic-hud hud-br"></div>
        <button type="button" class="sonic-slider-arrow prev" id="sliderPrev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
        <button type="button" class="sonic-slider-arrow next" id="sliderNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
        <div class="sonic-slider-dots" id="sliderDots"></div>
    </div>
    <?php endif; ?>

    <div class="sonic-hero-content">
        <h1 class="sonic-hero-title">
            <i class="fas fa-bolt sonic-bolt"></i>
            <span class="glow" id="sonicTitle" data-text="Discover Knowledge">Discover Knowledge</span>
        </h1>
        <p class="sonic-hero-sub"><?= __('Scan our digital library at the speed of light') ?></p>

        <div class="sonic-search-card" id="search-wraper">
            <form action="index.php" method="get" id="sonicSearchForm" autocomplete="off">
                <input type="hidden" name="search" value="search">
                <div class="sonic-input-wrap">
                    <i class="fas fa-search sonic-input-icon"></i>
                    <input type="text" id="sonic-keywords" name="keywords"
                           value="<?= htmlentities(getQuery('keywords')) ?>"
                           class="sonic-search-input"
                           placeholder="<?= __('Enter keyword to search collection...') ?>"/>
                    <div class="sonic-scanline"></div>
                    <button type="submit" class="sonic-search-go" title="<?= __('Search') ?>"><i class="fas fa-bolt"></i></button>
                </div>
            </form>

            <div class="sonic-quick-tags" id="sonicQuickTags">
                <button type="button" class="sonic-tag active" data-search-mode="keywords"><i class="fas fa-globe"></i><?= __('ALL') ?></button>
                <button type="button" class="sonic-tag" data-search-mode="author"><i class="fas fa-user-edit"></i><?= __('Author') ?></button>
                <button type="button" class="sonic-tag" data-search-mode="subject"><i class="fas fa-tags"></i><?= __('Subject') ?></button>
                <button type="button" class="sonic-tag" data-search-mode="isbn"><i class="fas fa-barcode"></i><?= __('ISBN/ISSN') ?></button>
                <button type="button" class="sonic-tag" id="sonicAdvBtn"><i class="fas fa-sliders-h"></i><?= __('Advanced Search') ?></button>
            </div>

            <div class="sonic-recent" id="sonicRecent" style="display:none;">
                <p class="sonic-recent-label"><i class="fas fa-history"></i><?= __('Last search:') ?></p>
                <div class="sonic-recent-list" id="sonicRecentList"></div>
            </div>
        </div>
    </div>
</section>