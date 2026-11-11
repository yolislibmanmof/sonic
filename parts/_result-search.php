<?php
# @Filename: _result-search.php (SONIC v6 — FASE 2: multi-facet + sort + export + saved search)
$sonic_compact_hero = true;
include '_navbar.php';
include '_search-form.php';

/* ===== Normalisasi state pencarian (filter JSON + legacy GET) ===== */
$sonic_filter = [];
if (!empty($_GET['filter']) && is_string($_GET['filter'])) {
    $dec = json_decode($_GET['filter'], true);
    if (is_array($dec)) $sonic_filter = $dec;
}
$sonic_state = [
    'keywords'    => (string)($_GET['keywords'] ?? ''),
    'title'       => (string)($sonic_filter['title']       ?? ($_GET['title'] ?? '')),
    'author'      => (string)($sonic_filter['author']      ?? ($_GET['author'] ?? '')),
    'subject'     => (string)($sonic_filter['subject']     ?? ($_GET['subject'] ?? '')),
    'isbn'        => (string)($sonic_filter['isbn']        ?? ($_GET['isbn'] ?? '')),
    'callnumber'  => (string)($sonic_filter['callnumber']  ?? ($_GET['callnumber'] ?? '')),
    'publishyear' => (string)($sonic_filter['publishyear'] ?? ($_GET['publishyear'] ?? '')),
    'sort'        => (string)($sonic_filter['sort']        ?? ''),
];
foreach (['gmd', 'location', 'colltype'] as $mk) {
    $v = $sonic_filter[$mk] ?? ($_GET[$mk] ?? []);
    if (is_string($v)) $v = ($v === '' || $v === '0') ? [] : [$v];
    if (!is_array($v)) $v = [$v];
    $sonic_state[$mk] = array_values(array_filter(array_map('strval', $v), fn($x) => $x !== '' && $x !== '0'));
}

if (!function_exists('sonic_search_url')) {
    function sonic_search_url($state, $overrides = []) {
        $s = array_merge($state, $overrides);
        $filter = [];
        foreach (['title','author','subject','isbn','callnumber','publishyear','sort'] as $k) if (!empty($s[$k])) $filter[$k] = $s[$k];
        foreach (['gmd','location','colltype'] as $k) if (!empty($s[$k])) $filter[$k] = array_values($s[$k]);
        $params = ['search' => 'search'];
        if ($s['keywords'] !== '') $params['keywords'] = $s['keywords'];
        if ($filter) $params['filter'] = json_encode($filter, JSON_UNESCAPED_UNICODE);
        return 'index.php?' . http_build_query($params);
    }
}
if (!function_exists('sonic_facet_values')) {
    function sonic_facet_values($type) {
        global $dbs;
        static $cache = [];
        if (isset($cache[$type])) return $cache[$type];
        $sql = [
            'gmd' => 'SELECT gmd_name FROM mst_gmd ORDER BY gmd_name LIMIT 50',
            'location' => 'SELECT location_name FROM mst_location ORDER BY location_name LIMIT 50',
            'colltype' => 'SELECT coll_type_name FROM mst_coll_type ORDER BY coll_type_name LIMIT 50',
        ][$type] ?? '';
        $out = [];
        if ($sql && isset($dbs) && is_object($dbs)) {
            try { $q = $dbs->query($sql); if ($q) while ($r = $q->fetch_row()) $out[] = $r[0]; } catch (Throwable $e) {}
        }
        return $cache[$type] = $out;
    }
}
$sonic_view = $_SESSION['LIST_VIEW'] ?? 'list';
$sonic_has_filter = !empty(array_filter($sonic_state, fn($v) => $v !== '' && $v !== []));
?>

<section class="sonic-container sonic-result-layout" style="padding:2.5rem 1.5rem 4rem;">

    <aside class="sonic-facet sonic-facet-neon" id="sonicFacet">
        <div class="sonic-facet-head">
            <i class="fas fa-sliders-h"></i> <?= __('Filter') ?>
            <button type="button" class="sonic-facet-toggle" id="sonicFacetToggle" aria-label="Toggle filter"><i class="fas fa-chevron-down"></i></button>
        </div>
        <form action="index.php" method="get" class="sonic-facet-body" id="sonicFacetForm">
            <input type="hidden" name="search" value="search">

            <?php if ($sonic_has_filter): ?>
            <div class="sonic-facet-chips">
                <?php if ($sonic_state['keywords'] !== ''): ?>
                    <a class="sonic-tag sonic-chip" href="<?= htmlspecialchars(sonic_search_url($sonic_state, ['keywords' => ''])) ?>"><i class="fas fa-times"></i><?= htmlspecialchars($sonic_state['keywords']) ?></a>
                <?php endif; ?>
                <?php foreach (['title','author','subject','isbn','callnumber','publishyear'] as $sk): if ($sonic_state[$sk] === '') continue; ?>
                    <a class="sonic-tag sonic-chip" href="<?= htmlspecialchars(sonic_search_url($sonic_state, [$sk => ''])) ?>"><i class="fas fa-times"></i><?= $sk ?>: <?= htmlspecialchars($sonic_state[$sk]) ?></a>
                <?php endforeach; ?>
                <?php foreach (['gmd','location','colltype'] as $mk): foreach ($sonic_state[$mk] as $val): ?>
                    <a class="sonic-tag sonic-chip" href="<?= htmlspecialchars(sonic_search_url($sonic_state, [$mk => array_values(array_filter($sonic_state[$mk], fn($x) => $x !== $val))])) ?>"><i class="fas fa-times"></i><?= htmlspecialchars($val) ?></a>
                <?php endforeach; endforeach; ?>
            </div>
            <?php endif; ?>

            <?php foreach (['gmd' => ['fas fa-book', __('GMD')], 'location' => ['fas fa-map-marker-alt', __('Location')], 'colltype' => ['fas fa-layer-group', __('Collection Type')]] as $fk => $meta): ?>
            <div class="sonic-facet-group" data-facet-group="<?= $fk ?>">
                <label><i class="<?= $meta[0] ?>"></i> <?= $meta[1] ?></label>
                <div class="sonic-facet-options">
                    <?php foreach (sonic_facet_values($fk) as $opt): $ck = in_array($opt, $sonic_state[$fk], true); ?>
                    <label class="sonic-facet-check<?= $ck ? ' on' : '' ?>">
                        <input type="checkbox" name="<?= $fk ?>[]" value="<?= htmlspecialchars($opt) ?>" <?= $ck ? 'checked' : '' ?>>
                        <span class="sonic-facet-name"><?= htmlspecialchars($opt) ?></span>
                        <em class="sonic-facet-count" data-facet="<?= $fk ?>" data-value="<?= htmlspecialchars($opt) ?>"></em>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="sonic-facet-group">
                <label><i class="far fa-calendar-alt"></i> <?= __('Publish Year') ?></label>
                <input type="text" name="publishyear" class="sonic-facet-input" placeholder="cth: 2024" value="<?= htmlspecialchars($sonic_state['publishyear']) ?>">
            </div>

            <div class="sonic-facet-actions">
                <button type="submit" class="sonic-btn" style="justify-content:center;"><i class="fas fa-bolt"></i> <?= __('Apply') ?></button>
                <a href="index.php?keywords=<?= urlencode($sonic_state['keywords']) ?>&search=search" class="sonic-btn-outline" style="justify-content:center;"><i class="fas fa-undo"></i> <?= __('Reset') ?></a>
            </div>
        </form>
    </aside>

    <div class="sonic-result-main">
        <div class="sonic-result-toolbar">
            <form method="post" action="" class="sonic-view-toggle">
                <button type="submit" name="view" value="list" class="sonic-mini-btn <?= $sonic_view === 'list' ? 'active' : '' ?>" title="<?= __('List view') ?>"><i class="fas fa-list"></i></button>
                <button type="submit" name="view" value="grid" class="sonic-mini-btn <?= $sonic_view === 'grid' ? 'active' : '' ?>" title="<?= __('Grid view') ?>"><i class="fas fa-th"></i></button>
            </form>

            <select id="sonicSort" class="sonic-facet-select sonic-sort-select">
                <option value=""><?= __('Sort: Most relevant') ?></option>
                <option value="recently-added" <?= $sonic_state['sort'] === 'recently-added' ? 'selected' : '' ?>><?= __('Recently Added') ?></option>
                <option value="last-update" <?= $sonic_state['sort'] === 'last-update' ? 'selected' : '' ?>><?= __('Last Update') ?></option>
                <option value="most-loaned" <?= $sonic_state['sort'] === 'most-loaned' ? 'selected' : '' ?>><?= __('Most Loaned') ?></option>
                <option value="publish-year-newest" <?= $sonic_state['sort'] === 'publish-year-newest' ? 'selected' : '' ?>><?= __('Publication Year [newest]') ?></option>
                <option value="publish-year-oldest" <?= $sonic_state['sort'] === 'publish-year-oldest' ? 'selected' : '' ?>><?= __('Publication Year [oldest]') ?></option>
                <option value="title-asc" <?= $sonic_state['sort'] === 'title-asc' ? 'selected' : '' ?>><?= __('Title Ascending') ?></option>
                <option value="title-desc" <?= $sonic_state['sort'] === 'title-desc' ? 'selected' : '' ?>><?= __('Title Descending') ?></option>
            </select>

            <div class="sonic-result-tools">
                <button type="button" class="sonic-mini-btn" id="sonicSaveSearch" title="<?= __('Save this search') ?>"><i class="fas fa-bookmark"></i> <?= __('Save') ?></button>
                <a class="sonic-mini-btn" id="sonicRss" href="#" target="_blank" rel="noopener" title="RSS"><i class="fas fa-rss"></i></a>
                <a class="sonic-mini-btn" id="sonicJsonLd" href="#" target="_blank" rel="noopener" title="JSON-LD"><i class="fas fa-code"></i></a>
                <a class="sonic-mini-btn" id="sonicXml" href="#" target="_blank" rel="noopener" title="XML"><i class="fas fa-file-code"></i></a>
            </div>
        </div>

        <div class="sonic-saved-panel" id="sonicSavedPanel" style="display:none;">
            <div class="sonic-saved-head"><i class="fas fa-bookmark"></i> <?= __('Saved searches') ?>
                <button type="button" class="sonic-facet-clear" id="sonicSavedClose" title="<?= __('Close') ?>">&times;</button>
            </div>
            <div class="sonic-saved-list" id="sonicSavedList"></div>
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