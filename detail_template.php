<?php
/* @Filename: detail_template.php (SONIC v6 — Detail Record Ultimate FINAL, scope-safe) */

/* ===== 1. DB BOOTSTRAP ===== */
if (!isset($dbs) || !is_object($dbs)) {
    $dbs = $GLOBALS['dbs'] ?? null;
    if ((!$dbs || !is_object($dbs)) && class_exists('\SLiMS\DB') && method_exists('\SLiMS\DB', 'getInstance')) {
        try { $dbs = \SLiMS\DB::getInstance(); } catch (Throwable $e) { $dbs = null; }
    }
}
$sonic_has_db = is_object($dbs);

/* ===== HELPERS (load-order safe) ===== */
if (!function_exists('sonic_kses')) {
    function sonic_kses($html) {
        $html = (string)$html;
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1=$2#$2', $html);
        return $html;
    }
}
if (!function_exists('sonic_is_bookmarked')) {
    function sonic_is_bookmarked($biblio_id) {
        global $dbs; static $set = null;
        if (!class_exists('utility') || !utility::isMemberLogin()) return false;
        if ($set === null) {
            $set = [];
            $mid = isset($_SESSION['mid']) ? $dbs->real_escape_string($_SESSION['mid']) : '';
            if ($mid !== '' && isset($dbs) && is_object($dbs)) {
                try { $q = $dbs->query("SELECT biblio_id FROM biblio_mark WHERE member_id='{$mid}'"); if ($q) while ($r = $q->fetch_row()) $set[(int)$r[0]] = true; } catch (Throwable $e) {}
            }
        }
        return isset($set[(int)$biblio_id]);
    }
}
if (!function_exists('sonic_trim')) {
    function sonic_trim($s, $n) { $s = strip_tags((string)$s); if (function_exists('mb_strimwidth')) return mb_strimwidth($s, 0, $n, '...'); return (strlen($s) > $n) ? substr($s, 0, $n) . '...' : $s; }
}
if (!function_exists('sonic_format_authors')) {
    function sonic_format_authors($authors_arr) {
        if (!is_array($authors_arr)) return '';
        $list = []; $total = count($authors_arr);
        foreach ($authors_arr as $order => $data) {
            $name = is_array($data) ? ($data['author_name'] ?? '') : (string)$data; $name = trim($name);
            if ($name === '') continue;
            $chunk = explode(' ', $name); $last_idx = array_key_last($chunk); $last = $chunk[$last_idx]; $first = $chunk[0] ?? '';
            if (!str_ends_with(trim($first), ',')) { unset($chunk[$last_idx]); if ($order > 0 && $total > 2) continue; $list[] = $last . ', ' . implode(' ', array_map(fn($n) => ucfirst(substr($n, 0, 1)) . '.', $chunk)); }
            else { if ($order > 0 && $total > 2) continue; unset($chunk[0]); $list[] = $first . ' ' . implode(' ', array_map(fn($n) => ucfirst(substr($n, 0, 1)) . '.', $chunk)); }
        }
        return implode(', ', $list) . ($total > 2 ? ' et al.' : '');
    }
}
if (!function_exists('sonic_similar_cover')) {
    function sonic_similar_cover($img) {
        if (!empty($img)) return './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . $img) . '&width=240';
        return './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=240';
    }
}

/* ===== 3. COVER ===== */
$sonic_bookmarked = function_exists('sonic_is_bookmarked') ? sonic_is_bookmarked($biblio_id) : isset($_SESSION['bookmark'][$biblio_id]);
$setBookmarked = $sonic_bookmarked ? 'active' : '';
$sonic_cover_raw = '';
if ($sonic_has_db) { try { $_cq = $dbs->query('SELECT image FROM biblio WHERE biblio_id=' . (int)$biblio_id); if ($_cq && ($_cr = $_cq->fetch_row())) $sonic_cover_raw = (string)($_cr[0] ?? ''); } catch (Throwable $e) {} }
if ($sonic_cover_raw === '' && isset($image) && is_string($image) && $image !== '') $sonic_cover_raw = $image;
if ($sonic_cover_raw !== '' && !file_exists('./images/docs/' . $sonic_cover_raw)) $sonic_cover_raw = '';
$sonic_cover_url = !empty($sonic_cover_raw) ? './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . $sonic_cover_raw) . '&width=240' : './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=240';
$sonic_cover_large = !empty($sonic_cover_raw) ? './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . $sonic_cover_raw) . '&width=600' : './images/default/image.png';
$sonic_scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$sonic_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$sonic_cover_abs = $sonic_scheme . '://' . $sonic_host . SWB . 'images/docs/' . rawurlencode($sonic_cover_raw);
$sonic_detail_abs = $sonic_scheme . '://' . $sonic_host . SWB . 'index.php?p=show_detail&id=' . (int)$biblio_id;

/* ===== 4. AVAILABILITY + SHELF ===== */
$sonic_total_items = 0; $sonic_borrowed_items = 0; $sonic_available_items = 0; $sonic_locations = [];
if ($sonic_has_db) {
    try {
        $_q = $dbs->query('SELECT COUNT(*) FROM item WHERE biblio_id=' . (int)$biblio_id); $sonic_total_items = (int)($_q->fetch_row()[0] ?? 0);
        $_q2 = $dbs->query('SELECT COUNT(*) FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code WHERE l.is_lent=1 AND l.is_return=0 AND i.biblio_id=' . (int)$biblio_id); $sonic_borrowed_items = (int)($_q2->fetch_row()[0] ?? 0);
        $sonic_available_items = max(0, $sonic_total_items - $sonic_borrowed_items);
        $_q3 = $dbs->query('SELECT l.location_name, COUNT(*) c FROM item i JOIN mst_location l ON i.location_id=l.location_id WHERE i.biblio_id=' . (int)$biblio_id . ' GROUP BY l.location_name');
        if ($_q3) while ($r = $_q3->fetch_assoc()) $sonic_locations[] = $r;
    } catch (Throwable $e) {}
}

/* ===== 5. AUTHORS ===== */
$sonic_author_list = [];
if ($sonic_has_db) { try { $_aq = $dbs->query('SELECT a.author_name FROM biblio_author ba JOIN mst_author a ON ba.author_id=a.author_id WHERE ba.biblio_id=' . (int)$biblio_id . ' ORDER BY ba.level ASC'); if ($_aq) while ($_r = $_aq->fetch_assoc()) $sonic_author_list[] = ['author_name' => $_r['author_name']]; } catch (Throwable $e) {} }
if (count($sonic_author_list) === 0 && isset($authors) && is_string($authors) && trim(strip_tags($authors)) !== '') {
    foreach (preg_split('/(<br\s*\/?>|;|\n)/i', strip_tags($authors)) as $_nm) { $_nm = trim(html_entity_decode($_nm)); if ($_nm !== '') $sonic_author_list[] = ['author_name' => $_nm]; }
}
$sonic_authors_string = sonic_format_authors($sonic_author_list);

/* ===== 6. CITATIONS ===== */
$sonic_year = $publish_year ?? date('Y');
$sonic_title_clean = strip_tags(stripslashes($title ?? ''));
$sonic_edition = htmlspecialchars($edition ?? '', ENT_QUOTES);
$sonic_publisher = htmlspecialchars($publisher_name ?? '', ENT_QUOTES);
$sonic_place = htmlspecialchars($publish_place ?? '', ENT_QUOTES);
$sonic_gmd = (($gmd_name ?? '') == 'Text') ? 'Print' : ($gmd_name ?? '');
$sonic_citations = [];
$sonic_citations['APA'] = $sonic_authors_string ? "{$sonic_authors_string} ({$sonic_year}). <em>{$sonic_title_clean}</em>" . ($sonic_edition ? " ({$sonic_edition})" : "") . ". {$sonic_publisher}." : "<em>{$sonic_title_clean}</em> ({$sonic_year}). {$sonic_publisher}.";
$sonic_citations['Chicago'] = $sonic_authors_string ? "{$sonic_authors_string}. <em>{$sonic_title_clean}</em>. {$sonic_edition} {$sonic_place}: {$sonic_publisher}, {$sonic_year}. {$sonic_gmd}." : "<em>{$sonic_title_clean}</em>. {$sonic_edition} {$sonic_place}: {$sonic_publisher}, {$sonic_year}. {$sonic_gmd}.";
$sonic_citations['MLA'] = $sonic_authors_string ? "{$sonic_authors_string}. \"{$sonic_title_clean}\". <em>{$sonic_edition}</em>, {$sonic_publisher}, {$sonic_year}." : "<em>{$sonic_title_clean}</em>. {$sonic_edition}, {$sonic_publisher}, {$sonic_year}.";
$sonic_citations['Turabian'] = $sonic_authors_string ? "{$sonic_authors_string}. <em>{$sonic_title_clean}</em>. {$sonic_edition} {$sonic_place}: {$sonic_publisher}, {$sonic_year}. {$sonic_gmd}." : "<em>{$sonic_title_clean}</em>. {$sonic_place}: {$sonic_publisher}, {$sonic_year}. {$sonic_gmd}.";

/* ===== 7. SIMILAR (DDC dulu, lalu pengarang/subjek) ===== */
$sonic_similar_books = []; $sonic_class = '';
if ($sonic_has_db) { try { $cq = $dbs->query('SELECT classification FROM biblio WHERE biblio_id=' . (int)$biblio_id); if ($cq && ($cr = $cq->fetch_row())) $sonic_class = trim((string)$cr[0]); } catch (Throwable $e) {} }
if ($sonic_has_db) {
    try {
        if ($sonic_class !== '') {
            $ddc = $dbs->real_escape_string(substr($sonic_class, 0, 3));
            $q = $dbs->query("SELECT b.biblio_id, b.title, b.image, GROUP_CONCAT(ma.author_name SEPARATOR ' - ') AS author FROM biblio b LEFT JOIN biblio_author ba ON b.biblio_id=ba.biblio_id LEFT JOIN mst_author ma ON ba.author_id=ma.author_id WHERE b.classification LIKE '{$ddc}%' AND b.biblio_id != " . (int)$biblio_id . " AND b.title IS NOT NULL GROUP BY b.biblio_id ORDER BY b.last_update DESC LIMIT 6");
            if ($q) while ($r = $q->fetch_assoc()) $sonic_similar_books[] = $r;
        }
        if (count($sonic_similar_books) < 4) {
            $names = []; foreach (array_slice($sonic_author_list, 0, 2) as $a) $names[] = $dbs->real_escape_string($a['author_name']);
            if ($names) {
                $conds = array_map(fn($n) => "mst_author.author_name LIKE '%{$n}%'", $names);
                $q = $dbs->query("SELECT DISTINCT b.biblio_id, b.title, b.image, GROUP_CONCAT(mst_author.author_name SEPARATOR ' - ') AS author FROM biblio b LEFT JOIN biblio_author ON b.biblio_id=biblio_author.biblio_id LEFT JOIN mst_author ON biblio_author.author_id=mst_author.author_id WHERE (" . implode(' OR ', $conds) . ") AND b.biblio_id != " . (int)$biblio_id . " AND b.title IS NOT NULL GROUP BY b.biblio_id ORDER BY b.last_update DESC LIMIT 6");
                if ($q) while ($r = $q->fetch_assoc()) { if (!in_array($r['biblio_id'], array_column($sonic_similar_books, 'biblio_id'))) $sonic_similar_books[] = $r; }
            }
        }
        if (count($sonic_similar_books) < 4) {
            $topics = []; $tq = $dbs->query("SELECT mt.topic FROM biblio_topic bt JOIN mst_topic mt ON bt.topic_id=mt.topic_id WHERE bt.biblio_id=" . (int)$biblio_id . " LIMIT 3");
            if ($tq) while ($r = $tq->fetch_row()) $topics[] = $dbs->real_escape_string($r[0]);
            if ($topics) {
                $tconds = array_map(fn($t) => "mt.topic LIKE '%{$t}%'", $topics);
                $q2 = $dbs->query("SELECT DISTINCT b.biblio_id, b.title, b.image, GROUP_CONCAT(mst_author.author_name SEPARATOR ' - ') AS author FROM biblio b LEFT JOIN biblio_topic bt ON b.biblio_id=bt.biblio_id LEFT JOIN mst_topic mt ON bt.topic_id=mt.topic_id LEFT JOIN biblio_author ba ON b.biblio_id=ba.biblio_id LEFT JOIN mst_author ON ba.author_id=mst_author.author_id WHERE (" . implode(' OR ', $tconds) . ") AND b.biblio_id != " . (int)$biblio_id . " AND b.title IS NOT NULL GROUP BY b.biblio_id ORDER BY b.last_update DESC LIMIT " . (6 - count($sonic_similar_books)));
                if ($q2) while ($r = $q2->fetch_assoc()) { if (!in_array($r['biblio_id'], array_column($sonic_similar_books, 'biblio_id'))) $sonic_similar_books[] = $r; }
            }
        }
        $sonic_similar_books = array_slice($sonic_similar_books, 0, 6);
    } catch (Throwable $e) {}
}
$sonic_logged = class_exists('utility') && utility::isMemberLogin();
$sonic_csrf = class_exists('\Volnix\CSRF\CSRF') ? \Volnix\CSRF\CSRF::getToken() : '';
$sonic_csrf_name = class_exists('\Volnix\CSRF\CSRF') ? \Volnix\CSRF\CSRF::getTokenName() : 'csrf_token';
?>

<section class="sonic-detail-revolution" data-biblio-id="<?= (int)$biblio_id ?>" data-logged="<?= $sonic_logged ? 1 : 0 ?>">
    <div class="sonic-detail-sidebar">
        <div class="sonic-book-3d-container">
            <div class="sonic-book-3d" id="sonicBook3D">
                <div class="sonic-book-3d-inner">
                    <div class="sonic-book-face front">
                        <img src="<?= $sonic_cover_url ?>" alt="<?= htmlspecialchars($title ?? '') ?>" onerror="this.src='./images/default/image.png'">
                        <div class="sonic-book-shine"></div>
                    </div>
                    <div class="sonic-book-face back"></div>
                    <div class="sonic-book-face spine">
                        <span class="sonic-spine-title"><?= htmlspecialchars(sonic_trim($title ?? '', 40)) ?></span>
                        <span class="sonic-spine-author"><?= htmlspecialchars(sonic_trim($sonic_author_list[0]['author_name'] ?? '', 25)) ?></span>
                    </div>
                    <div class="sonic-book-face top"></div>
                    <div class="sonic-book-face bottom"></div>
                </div>
                <div class="sonic-book-shadow"></div>
            </div>
            <p class="sonic-book-3d-hint"><i class="fas fa-hand-pointer"></i> Gerakkan mouse untuk rotate</p>
        </div>

        <div class="sonic-availability-card">
            <h5 class="sonic-avail-title"><i class="fas fa-boxes"></i> Ketersediaan</h5>
            <div class="sonic-avail-stats">
                <div class="sonic-avail-stat"><span class="sonic-avail-number sonic-success"><?= $sonic_available_items ?></span><span class="sonic-avail-label">Tersedia</span></div>
                <div class="sonic-avail-stat"><span class="sonic-avail-number sonic-danger"><?= $sonic_borrowed_items ?></span><span class="sonic-avail-label">Dipinjam</span></div>
                <div class="sonic-avail-stat"><span class="sonic-avail-number"><?= $sonic_total_items ?></span><span class="sonic-avail-label">Total</span></div>
            </div>
            <?php if ($sonic_total_items > 0): ?>
            <div class="sonic-avail-bar"><div class="sonic-avail-bar-fill" style="width: <?= round(($sonic_available_items / max(1, $sonic_total_items)) * 100) ?>%"></div></div>
            <?php endif; ?>
            <?php if (!empty($call_number)): ?>
            <div class="sonic-call-number">
                <i class="fas fa-map-marker-alt"></i>
                <span>Call Number: <strong><?= htmlspecialchars($call_number) ?></strong></span>
                <button type="button" class="sonic-copy-call" id="sonicCopyCall" data-call="<?= htmlspecialchars($call_number) ?>" title="Salin Call Number"><i class="far fa-copy"></i></button>
            </div>
            <?php endif; ?>
            <?php if (count($sonic_locations) > 0): ?>
            <div class="sonic-shelf">
                <h6><i class="fas fa-warehouse"></i> Lokasi Rak</h6>
                <ul><?php foreach ($sonic_locations as $loc): ?><li><span><?= htmlspecialchars($loc['location_name']) ?></span><em><?= (int)$loc['c'] ?> eks</em></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>
        </div>

        <div class="sonic-detail-actions">
            <a href="javascript:void(0)" data-id="<?= $biblio_id ?>" data-label-on="<?= htmlspecialchars(__('Bookmarked'), ENT_QUOTES) ?>" data-label-off="<?= htmlspecialchars(__('Bookmark'), ENT_QUOTES) ?>" class="sonic-action-btn bookMarkBook <?= $setBookmarked ?>" id="bookmarkBtn"><i class="far fa-heart"></i> <span id="label-<?= $biblio_id ?>"><?= ($sonic_bookmarked ? __('Bookmarked') : __('Bookmark')) ?></span></a>
            <a href="javascript:void(0)" data-id="<?= $biblio_id ?>" data-title="<?= htmlspecialchars($title ?? '') ?>" data-target="#mediaSocialModal" class="sonic-action-btn"><i class="fas fa-share-alt"></i> <?= __('Share') ?></a>
            <a href="javascript:void(0)" class="sonic-action-btn add-to-chart-button" data-biblio="<?= $biblio_id ?>"><i class="fas fa-shopping-basket"></i> <?= __('Add to Basket') ?></a>
            <a href="javascript:void(0)" class="sonic-action-btn" id="sonicZoomBtn" data-large="<?= htmlspecialchars($sonic_cover_large) ?>"><i class="fas fa-search-plus"></i> Perbesar Sampul</a>
            <a href="javascript:void(0)" class="sonic-action-btn" id="sonicQrBtn" data-url="<?= htmlspecialchars($sonic_detail_abs) ?>"><i class="fas fa-qrcode"></i> QR Buku</a>
            <a href="index.php?p=show_detail&id=<?= $biblio_id ?>&MARC=true" class="sonic-action-btn sonic-marc-trigger"><i class="fas fa-terminal"></i> MARC View</a>
        </div>
    </div>

    <div class="sonic-detail-main">
        <div class="sonic-detail-header">
            <p class="sonic-detail-category"><i class="fas fa-book"></i> <?= htmlspecialchars($gmd_name ?? '') ?></p>
            <h1 class="sonic-detail-title"><?= htmlspecialchars(stripslashes($title ?? '')) ?></h1>
            <div class="sonic-detail-authors">
                <?php if (is_array($authors ?? null)): ?>
                    <?php foreach ($authors as $a): ?><a href="index.php?author=<?= urlencode(is_array($a) ? ($a['author_name'] ?? '') : $a) ?>&search=search"><?= htmlspecialchars(is_array($a) ? ($a['author_name'] ?? '') : $a) ?></a><?php endforeach; ?>
                <?php else: ?>
                    <?= function_exists('sonic_kses') ? sonic_kses(str_replace("<br />", ' ', $authors ?? '')) : str_replace("<br />", ' ', $authors ?? '') ?>
                <?php endif; ?>
            </div>
            <div class="sonic-rating-summary" id="sonicRatingSummary" data-biblio="<?= (int)$biblio_id ?>"></div>
        </div>

        <div class="sonic-detail-tabs">
            <button class="sonic-tab active" data-tab="description"><i class="fas fa-align-left"></i> Deskripsi</button>
            <button class="sonic-tab" data-tab="details"><i class="fas fa-info-circle"></i> Detail</button>
            <button class="sonic-tab" data-tab="citations"><i class="fas fa-quote-right"></i> Sitasi</button>
            <button class="sonic-tab" data-tab="availability"><i class="fas fa-box"></i> Eksemplar</button>
            <?php if (!empty($file_att)): ?><button class="sonic-tab" data-tab="digital"><i class="fas fa-file-download"></i> Digital</button><?php endif; ?>
            <?php if (!empty($related)): ?><button class="sonic-tab" data-tab="editions"><i class="fas fa-code-branch"></i> Editions</button><?php endif; ?>
            <button class="sonic-tab" data-tab="reviews"><i class="fas fa-star"></i> Ulasan</button>
            <button class="sonic-tab" data-tab="comments"><i class="fas fa-comments"></i> Komentar</button>
            <?php if (count($sonic_similar_books) > 0): ?><button class="sonic-tab" data-tab="similar"><i class="fas fa-layer-group"></i> Serupa (<?= count($sonic_similar_books) ?>)</button><?php endif; ?>
        </div>

        <div class="sonic-tab-content">
            <div class="sonic-tab-panel active" id="tab-description">
                <div class="sonic-info-block"><h5><?= __('Abstract') ?></h5>
                    <div class="sonic-abstract-text"><?= $notes ? sonic_kses($notes) : '<p style="color:var(--sonic-muted);font-style:italic;">' . __('Description Not Available') . '</p>' ?></div>
                </div>
            </div>

            <div class="sonic-tab-panel" id="tab-details">
                <div class="sonic-info-block"><h5><?= __('Detail Information') ?></h5>
                    <dl class="sonic-detail-dl">
                        <dt><?= __('Series Title') ?></dt><dd><?= htmlspecialchars($series_title ?: '-') ?></dd>
                        <dt><?= __('Call Number') ?></dt><dd><span class="sonic-highlight"><?= htmlspecialchars($call_number ?: '-') ?></span></dd>
                        <dt><?= __('Publisher') ?></dt><dd><?= htmlspecialchars($publish_place) ?> : <?= htmlspecialchars($publisher_name) ?>., <?= htmlspecialchars($publish_year) ?></dd>
                        <dt><?= __('Specific Detail Info') ?></dt><dd><?= htmlspecialchars($spec_detail_info ?: '-') ?></dd>
                        <dt><?= __('Statement of Responsibility') ?></dt><dd><?= htmlspecialchars($sor ?: '-') ?></dd>
                    </dl>
                </div>
                <?php if (count($biblio_custom ?? []) > 0): ?>
                <div class="sonic-info-block"><h5><?= __('Other Information') ?></h5>
                    <dl class="sonic-detail-dl"><?php foreach ($biblio_custom as $item): ?><dt><?= htmlspecialchars($item['label']) ?></dt><dd><?= htmlspecialchars($item['value'] ?: '-') ?></dd><?php endforeach; ?></dl>
                </div>
                <?php endif; ?>
            </div>

            <div class="sonic-tab-panel" id="tab-citations">
                <div class="sonic-citation-panel">
                    <h5><i class="fas fa-quote-right"></i> Format Sitasi</h5>
                    <p class="sonic-citation-sub">Pilih format sitasi yang Anda butuhkan untuk referensi akademis</p>
                    <div class="sonic-citation-grid">
                        <?php foreach ($sonic_citations as $style => $citation): ?>
                        <div class="sonic-citation-card">
                            <div class="sonic-citation-head"><span class="sonic-citation-style"><?= $style ?></span><button class="sonic-cite-copy" data-style="<?= $style ?>" title="Copy to clipboard"><i class="far fa-copy"></i> Copy</button></div>
                            <div class="sonic-citation-body" id="citation-<?= strtolower($style) ?>"><?= $citation ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="sonic-cite-all" id="copyAllCitations"><i class="fas fa-clipboard-list"></i> Copy Semua Sitasi</button>
                    <div class="sonic-cite-export">
                        <span class="sonic-cite-export-label"><i class="fas fa-file-export"></i> Export:</span>
                        <button type="button" class="sonic-mini-btn" data-export="ris">RIS (.ris)</button>
                        <button type="button" class="sonic-mini-btn" data-export="bibtex">BibTeX (.bib)</button>
                        <button type="button" class="sonic-mini-btn" data-export="endnote">EndNote (.enw)</button>
                    </div>
                </div>
            </div>

            <div class="sonic-tab-panel" id="tab-availability">
                <div class="sonic-info-block"><h5><?= __('Availability') ?></h5>
                    <div class="sonic-availability-table"><?= ($availability) ? $availability : '<p style="color:var(--sonic-danger);">' . __('No copy data') . '</p>' ?></div>
                </div>
            </div>

            <?php if (!empty($file_att)): ?>
            <div class="sonic-tab-panel" id="tab-digital">
                <div class="sonic-info-block" id="attachment"><h5><i class="fas fa-file-download"></i> Digital Files / E-Book</h5>
                    <div class="sonic-attach-deck"><?= $file_att ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($related)): ?>
            <div class="sonic-tab-panel" id="tab-editions">
                <div class="sonic-info-block"><h5><i class="fas fa-code-branch"></i> Other Editions / Related</h5>
                    <div class="sonic-related-deck"><?= $related ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="sonic-tab-panel" id="tab-reviews">
                <div class="sonic-info-block">
                    <h5><i class="fas fa-star"></i> Rating & Ulasan Pemustaka</h5>
                    <div class="sonic-review-list" id="sonicReviewList"></div>
                    <?php if ($sonic_logged): ?>
                    <form class="sonic-review-form" id="sonicReviewForm">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="biblio_id" value="<?= (int)$biblio_id ?>">
                        <input type="hidden" name="<?= htmlspecialchars($sonic_csrf_name) ?>" value="<?= htmlspecialchars($sonic_csrf) ?>">
                        <div class="sonic-review-stars" id="sonicReviewStars">
                            <?php for ($i = 1; $i <= 5; $i++): ?><button type="button" data-star="<?= $i ?>" aria-label="<?= $i ?> bintang"><i class="far fa-star"></i></button><?php endfor; ?>
                            <input type="hidden" name="rating" id="sonicRatingInput" value="5">
                        </div>
                        <textarea name="review" rows="3" maxlength="1000" placeholder="Tulis ulasan Anda..."></textarea>
                        <button type="submit" class="sonic-btn"><i class="fas fa-paper-plane"></i> Kirim Ulasan</button>
                    </form>
                    <?php else: ?>
                    <p class="sonic-review-login"><a href="index.php?p=member&destination=<?= urlencode($sonic_detail_abs) ?>">Login</a> untuk menulis ulasan.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sonic-tab-panel" id="tab-comments">
                <div class="sonic-info-block" id="comment"><h5><?= __('Comments') ?></h5>
                    <div class="sonic-comments-section"><?= function_exists('showComment') ? showComment($biblio_id) : '<p style="color:var(--sonic-muted);font-style:italic;">' . __('Comments are disabled') . '</p>' ?></div>
                </div>
            </div>

            <?php if (count($sonic_similar_books) > 0): ?>
            <div class="sonic-tab-panel" id="tab-similar">
                <div class="sonic-similar-section"><h5><i class="fas fa-layer-group"></i> Buku Serupa</h5>
                    <p class="sonic-similar-sub">Buku lain dengan klasifikasi, pengarang, atau subjek yang mirip</p>
                    <div class="sonic-biblio-grid">
                        <?php foreach ($sonic_similar_books as $book): ?>
                        <a class="sonic-book" href="index.php?p=show_detail&id=<?= (int)$book['biblio_id'] ?>">
                            <div class="sonic-book-cover"><img loading="lazy" src="<?= sonic_similar_cover($book['image'] ?? '') ?>" alt="<?= htmlspecialchars($book['title']) ?>" onerror="this.src='./images/default/image.png'"></div>
                            <h4 class="sonic-book-title"><?= htmlspecialchars($book['title']) ?></h4>
                            <p class="sonic-book-author"><?= htmlspecialchars($book['author'] ?? '') ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div id="heartBurst" class="sonic-heart-burst"></div>
<div id="copyToast" class="sonic-copy-toast"><i class="fas fa-check-circle"></i><span id="copyToastText">Disalin!</span></div>

<script type="application/json" id="sonicCiteData"><?= json_encode([
    'id' => (int)$biblio_id, 'title' => $sonic_title_clean,
    'authors' => array_values(array_column($sonic_author_list, 'author_name')),
    'year' => (string)$sonic_year, 'publisher' => $sonic_publisher, 'place' => $sonic_place,
    'edition' => $sonic_edition, 'isbn' => $isbn_issn ?? '', 'gmd' => $sonic_gmd, 'url' => $sonic_detail_abs,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>

<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org', '@type' => 'Book',
    'name' => $sonic_title_clean,
    'author' => array_map(fn($a) => ['@type' => 'Person', 'name' => $a['author_name']], $sonic_author_list),
    'isbn' => $isbn_issn ?? '', 'publisher' => ['@type' => 'Organization', 'name' => $publisher_name ?? ''],
    'datePublished' => (string)$sonic_year, 'image' => $sonic_cover_abs, 'url' => $sonic_detail_abs,
    'inLanguage' => $language ?? 'id',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>