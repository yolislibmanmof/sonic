<?php
# @Filename: _result-search.php (SONIC v4 — Facet Sidebar)
$sonic_compact_hero = true;
include '_navbar.php';
include '_search-form.php';

if (!function_exists('sonic_filter_remove_url')) {
    function sonic_filter_remove_url($remove) {
        $params = $_GET;
        unset($params[$remove]);
        if (!isset($params['search'])) $params['search'] = 'search';
        return 'index.php?' . http_build_query($params);
    }
}

// Filter aktif dari URL
$sonic_filter_keys = ['keywords','title','author','subject','isbn','callnumber','location','gmd','colltype','publishyear'];
$sonic_filters = [];
foreach ($sonic_filter_keys as $fk) {
    if (isset($_GET[$fk]) && trim((string)$_GET[$fk]) !== '') $sonic_filters[$fk] = (string)$_GET[$fk];
}
?>

<section class="sonic-container sonic-result-layout" style="padding:2.5rem 1.5rem 4rem;">

    <!-- ===== FACET SIDEBAR NEON ===== -->
    <aside class="sonic-facet sonic-facet-neon" id="sonicFacet">
        <div class="sonic-facet-head">
            <i class="fas fa-sliders-h"></i> <?= __('Filter') ?>
            <button type="button" class="sonic-facet-toggle" id="sonicFacetToggle" aria-label="Toggle filter"><i class="fas fa-chevron-down"></i></button>
        </div>
        <form action="index.php" method="get" class="sonic-facet-body" id="sonicFacetForm">
            <input type="hidden" name="search" value="search">
            <?php foreach ($sonic_filters as $fk => $fv): ?>
                <?php if (!in_array($fk, ['gmd','location','colltype','publishyear'], true)): ?>
                    <input type="hidden" name="<?= htmlspecialchars($fk) ?>" value="<?= htmlspecialchars($fv) ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (count($sonic_filters) > 0): ?>
            <div class="sonic-facet-chips">
                <?php foreach ($sonic_filters as $fk => $fv): ?>
                    <a class="sonic-tag sonic-chip" href="<?= sonic_filter_remove_url($fk) ?>" title="<?= __('Remove filter') ?>">
                        <i class="fas fa-times"></i><?= htmlspecialchars($fk) ?>: <?= htmlspecialchars(strlen($fv) > 16 ? substr($fv, 0, 16) . '…' : $fv) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="sonic-facet-group">
                <label><i class="fas fa-book"></i> <?= __('GMD') ?></label>
                <select name="gmd" class="sonic-facet-select"><?= commonList('gmd') ?></select>
            </div>
            <div class="sonic-facet-group">
                <label><i class="fas fa-map-marker-alt"></i> <?= __('Location') ?></label>
                <select name="location" class="sonic-facet-select"><?= commonList('location') ?></select>
            </div>
            <div class="sonic-facet-group">
                <label><i class="fas fa-layer-group"></i> <?= __('Collection Type') ?></label>
                <select name="colltype" class="sonic-facet-select"><?= commonList('collection') ?></select>
            </div>
            <div class="sonic-facet-group">
                <label><i class="far fa-calendar-alt"></i> <?= __('Publish Year') ?></label>
                <input type="text" name="publishyear" class="sonic-facet-input" placeholder="cth: 2024">
            </div>

            <div class="sonic-facet-actions">
                <button type="submit" class="sonic-btn" style="justify-content:center;"><i class="fas fa-bolt"></i> <?= __('Apply') ?></button>
                <a href="index.php?keywords=<?= urlencode($sonic_filters['keywords'] ?? '') ?>&search=search" class="sonic-btn-outline" style="justify-content:center;"><i class="fas fa-undo"></i> <?= __('Reset') ?></a>
            </div>
        </form>
    </aside>

    <!-- ===== HASIL ===== -->
    <div class="sonic-result-main">
        <?php
        if (isset($main_content) && $main_content !== '') {
            echo $main_content;
        } elseif (isset($biblio_list)) {
            echo $biblio_list;
        }
        if (isset($search_paging)) {
            echo '<div class="sonic-paging">' . $search_paging . '</div>';
        }
        ?>
    </div>
</section>