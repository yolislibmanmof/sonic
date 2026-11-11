<?php
# @Filename: _result-search.php (SONIC v5 — FASE 1: facet aman + toggle view + sort)
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

$sonic_filter_keys = ['keywords','title','author','subject','isbn','callnumber','location','gmd','colltype','publishyear'];
$sonic_filters = [];
foreach ($sonic_filter_keys as $fk) {
    if (isset($_GET[$fk]) && trim((string)$_GET[$fk]) !== '') $sonic_filters[$fk] = (string)$_GET[$fk];
}
$sonic_view = $_SESSION['LIST_VIEW'] ?? 'list';
?>

<section class="sonic-container sonic-result-layout" style="padding:2.5rem 1.5rem 4rem;">

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
                <select name="gmd" class="sonic-facet-select"><?= sonic_common_list('gmd') ?></select>
            </div>
            <div class="sonic-facet-group">
                <label><i class="fas fa-map-marker-alt"></i> <?= __('Location') ?></label>
                <select name="location" class="sonic-facet-select"><?= sonic_common_list('location') ?></select>
            </div>
            <div class="sonic-facet-group">
                <label><i class="fas fa-layer-group"></i> <?= __('Collection Type') ?></label>
                <select name="colltype" class="sonic-facet-select"><?= sonic_common_list('collection') ?></select>
            </div>
            <div class="sonic-facet-group">
                <label><i class="far fa-calendar-alt"></i> <?= __('Publish Year') ?></label>
                <input type="text" name="publishyear" class="sonic-facet-input" placeholder="cth: 2024" value="<?= htmlspecialchars($_GET['publishyear'] ?? '') ?>">
            </div>

            <div class="sonic-facet-actions">
                <button type="submit" class="sonic-btn" style="justify-content:center;"><i class="fas fa-bolt"></i> <?= __('Apply') ?></button>
                <a href="index.php?keywords=<?= urlencode($sonic_filters['keywords'] ?? '') ?>&search=search" class="sonic-btn-outline" style="justify-content:center;"><i class="fas fa-undo"></i> <?= __('Reset') ?></a>
            </div>
        </form>
    </aside>

    <div class="sonic-result-main">
        <!-- Toolbar: toggle list/grid + sort -->
        <div class="sonic-result-toolbar" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1.2rem;flex-wrap:wrap;">
            <form method="post" action="" class="sonic-view-toggle" style="display:flex;gap:.4rem;">
                <button type="submit" name="view" value="list" class="sonic-mini-btn <?= $sonic_view === 'list' ? 'active' : '' ?>" title="<?= __('List view') ?>" style="<?= $sonic_view === 'list' ? 'background:var(--sonic-neon);color:#000;border-color:var(--sonic-neon);' : '' ?>"><i class="fas fa-list"></i></button>
                <button type="submit" name="view" value="grid" class="sonic-mini-btn <?= $sonic_view === 'grid' ? 'active' : '' ?>" title="<?= __('Grid view') ?>" style="<?= $sonic_view === 'grid' ? 'background:var(--sonic-neon);color:#000;border-color:var(--sonic-neon);' : '' ?>"><i class="fas fa-th"></i></button>
            </form>
            <select id="sonicSort" class="sonic-facet-select" style="max-width:240px;">
                <option value=""><?= __('Sort: Most relevant') ?></option>
                <option value="recently-added"><?= __('Recently Added') ?></option>
                <option value="last-update"><?= __('Last Update') ?></option>
                <option value="most-loaned"><?= __('Most Loaned') ?></option>
                <option value="publish-year-newest"><?= __('Publication Year [newest]') ?></option>
                <option value="publish-year-oldest"><?= __('Publication Year [oldest]') ?></option>
                <option value="title-asc"><?= __('Title Ascending') ?></option>
                <option value="title-desc"><?= __('Title Descending') ?></option>
            </select>
        </div>

        <?php
        if (isset($main_content) && $main_content !== '') {
            echo $main_content;
        } elseif (isset($biblio_list)) {
            echo $biblio_list;
        }
        ?>
    </div>
</section>