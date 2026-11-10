<?php
/* @Filename: detail_template.php (SONIC v4 — Detail Record Revolution, scope-safe) */

/* ===== 1. DB BOOTSTRAP (template di-include dalam scope function => $dbs tidak otomatis ada) ===== */
if (!isset($dbs) || !is_object($dbs)) {
    $dbs = $GLOBALS['dbs'] ?? null;
    if ((!$dbs || !is_object($dbs)) && class_exists('\SLiMS\DB') && method_exists('\SLiMS\DB', 'getInstance')) {
        try { $dbs = \SLiMS\DB::getInstance(); } catch (Throwable $e) { $dbs = null; }
    }
}
$sonic_has_db = is_object($dbs);

/* ===== 2. HELPERS ===== */
if (!function_exists('sonic_trim')) {
    function sonic_trim($s, $n) {
        $s = strip_tags((string)$s);
        if (function_exists('mb_strimwidth')) return mb_strimwidth($s, 0, $n, '...');
        return (strlen($s) > $n) ? substr($s, 0, $n) . '...' : $s;
    }
}
if (!function_exists('sonic_format_authors')) {
    function sonic_format_authors($authors_arr) {
        if (!is_array($authors_arr)) return '';
        $list = [];
        $total = count($authors_arr);
        foreach ($authors_arr as $order => $data) {
            $name = is_array($data) ? ($data['author_name'] ?? '') : (string)$data;
            $name = trim($name);
            if ($name === '') continue;
            $chunk = explode(' ', $name);
            $last_idx = array_key_last($chunk);
            $last = $chunk[$last_idx];
            $first = $chunk[0] ?? '';
            if (!str_ends_with(trim($first), ',')) {
                unset($chunk[$last_idx]);
                if ($order > 0 && $total > 2) continue;
                $list[] = $last . ', ' . implode(' ', array_map(fn($n) => ucfirst(substr($n, 0, 1)) . '.', $chunk));
            } else {
                if ($order > 0 && $total > 2) continue;
                unset($chunk[0]);
                $list[] = $first . ' ' . implode(' ', array_map(fn($n) => ucfirst(substr($n, 0, 1)) . '.', $chunk));
            }
        }
        return implode(', ', $list) . ($total > 2 ? ' et al.' : '');
    }
}
if (!function_exists('sonic_similar_cover')) {
    function sonic_similar_cover($img) {
        if (!empty($img)) {
            return './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . $img) . '&width=240';
        }
        return './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=240';
    }
}

/* ===== 3. COVER (filename mentah dari DB — $image_src sudah berupa URL thumbnail, tidak boleh dipakai) ===== */
$setBookmarked = trim(isset($_SESSION['bookmark'][$biblio_id]) ? 'active' : '');
$sonic_cover_raw = '';
if ($sonic_has_db) {
    try {
        $_cq = $dbs->query('SELECT image FROM biblio WHERE biblio_id=' . (int)$biblio_id);
        if ($_cq && ($_cr = $_cq->fetch_row())) { $sonic_cover_raw = (string)($_cr[0] ?? ''); }
    } catch (Throwable $e) { /* silent */ }
}
if ($sonic_cover_raw === '' && isset($image) && is_string($image) && $image !== '') {
    $sonic_cover_raw = $image;
}
/* Pengaman: jika file fisiknya tidak ada di images/docs, pakai default (anti placeholder WRONG CONTENT TYPE) */
if ($sonic_cover_raw !== '' && !file_exists('./images/docs/' . $sonic_cover_raw)) {
    $sonic_cover_raw = '';
}
$sonic_cover_url = !empty($sonic_cover_raw)
    ? './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . $sonic_cover_raw) . '&width=600'
    : './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=600';

/* ===== 4. AVAILABILITY (DB-first, akurat) ===== */
$sonic_total_items = 0; $sonic_borrowed_items = 0; $sonic_available_items = 0;
if ($sonic_has_db) {
    try {
        $_q = $dbs->query('SELECT COUNT(*) FROM item WHERE biblio_id=' . (int)$biblio_id);
        $sonic_total_items = (int)($_q->fetch_row()[0] ?? 0);
        $_q2 = $dbs->query('SELECT COUNT(*) FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code WHERE l.is_lent=1 AND l.is_return=0 AND i.biblio_id=' . (int)$biblio_id);
        $sonic_borrowed_items = (int)($_q2->fetch_row()[0] ?? 0);
        $sonic_available_items = max(0, $sonic_total_items - $sonic_borrowed_items);
    } catch (Throwable $e) { /* silent */ }
}

/* ===== 5. AUTHORS (DB-first, fallback parse string) ===== */
$sonic_author_list = [];
if ($sonic_has_db) {
    try {
        $_aq = $dbs->query('SELECT a.author_name FROM biblio_author ba JOIN mst_author a ON ba.author_id=a.author_id WHERE ba.biblio_id=' . (int)$biblio_id . ' ORDER BY ba.level ASC');
        if ($_aq) { while ($_r = $_aq->fetch_assoc()) { $sonic_author_list[] = ['author_name' => $_r['author_name']]; } }
    } catch (Throwable $e) { /* silent */ }
}
if (count($sonic_author_list) === 0 && isset($authors) && is_string($authors) && trim(strip_tags($authors)) !== '') {
    foreach (preg_split('/(<br\s*\/?>|;|\n)/i', strip_tags($authors)) as $_nm) {
        $_nm = trim(html_entity_decode($_nm));
        if ($_nm !== '') $sonic_author_list[] = ['author_name' => $_nm];
    }
}
$sonic_authors_string = sonic_format_authors($sonic_author_list);

/* ===== 6. CITATIONS (INLINE) ===== */
$sonic_year = $publish_year ?? date('Y');
$sonic_title_clean = strip_tags(stripslashes($title ?? ''));
$sonic_edition = $edition ?? '';
$sonic_publisher = $publisher_name ?? '';
$sonic_place = $publish_place ?? '';
$sonic_gmd = (($gmd_name ?? '') == 'Text') ? 'Print' : ($gmd_name ?? '');

$sonic_citations = [];
$sonic_citations['APA'] = $sonic_authors_string
    ? "{$sonic_authors_string} ({$sonic_year}). <em>{$sonic_title_clean}</em>" . ($sonic_edition ? " ({$sonic_edition})" : "") . ". {$sonic_publisher}."
    : "<em>{$sonic_title_clean}</em> ({$sonic_year}). {$sonic_publisher}.";
$sonic_citations['Chicago'] = $sonic_authors_string
    ? "{$sonic_authors_string}. <em>{$sonic_title_clean}</em>. {$sonic_edition} {$sonic_place}: {$sonic_publisher}, {$sonic_year}. {$sonic_gmd}."
    : "<em>{$sonic_title_clean}</em>. {$sonic_edition} {$sonic_place}: {$sonic_publisher}, {$sonic_year}. {$sonic_gmd}.";
$sonic_citations['MLA'] = $sonic_authors_string
    ? "{$sonic_authors_string}. \"{$sonic_title_clean}\". <em>{$sonic_edition}</em>, {$sonic_publisher}, {$sonic_year}."
    : "<em>{$sonic_title_clean}</em>. {$sonic_edition}, {$sonic_publisher}, {$sonic_year}.";
$sonic_citations['Turabian'] = $sonic_authors_string
    ? "{$sonic_authors_string}. <em>{$sonic_title_clean}</em>. {$sonic_edition} {$sonic_place}: {$sonic_publisher}, {$sonic_year}. {$sonic_gmd}."
    : "<em>{$sonic_title_clean}</em>. {$sonic_place}: {$sonic_publisher}, {$sonic_year}. {$sonic_gmd}.";

/* ===== 7. SIMILAR BOOKS (DB-guarded) ===== */
$sonic_similar_books = [];
if ($sonic_has_db) {
    try {
        $sonic_author_names = [];
        foreach (array_slice($sonic_author_list, 0, 2) as $a) {
            $sonic_author_names[] = $dbs->real_escape_string($a['author_name']);
        }
        if (count($sonic_author_names) > 0) {
            $conds = array_map(fn($n) => "mst_author.author_name LIKE '%{$n}%'", $sonic_author_names);
            $sql = "SELECT DISTINCT b.biblio_id, b.title, b.image,
                    GROUP_CONCAT(mst_author.author_name SEPARATOR ' - ') AS author
                    FROM biblio b
                    LEFT JOIN biblio_author ON b.biblio_id=biblio_author.biblio_id
                    LEFT JOIN mst_author ON biblio_author.author_id=mst_author.author_id
                    WHERE (" . implode(' OR ', $conds) . ")
                    AND b.biblio_id != " . (int)$biblio_id . "
                    AND b.title IS NOT NULL
                    GROUP BY b.biblio_id ORDER BY b.last_update DESC LIMIT 6";
            $q = $dbs->query($sql);
            if ($q) { while ($r = $q->fetch_assoc()) { $sonic_similar_books[] = $r; } }
        }
        if (count($sonic_similar_books) < 4) {
            $topics = [];
            $tq = $dbs->query("SELECT mt.topic FROM biblio_topic bt JOIN mst_topic mt ON bt.topic_id=mt.topic_id WHERE bt.biblio_id=" . (int)$biblio_id . " LIMIT 3");
            if ($tq) { while ($r = $tq->fetch_row()) { $topics[] = $dbs->real_escape_string($r[0]); } }
            if (count($topics) > 0) {
                $tconds = array_map(fn($t) => "mt.topic LIKE '%{$t}%'", $topics);
                $sql2 = "SELECT DISTINCT b.biblio_id, b.title, b.image,
                         GROUP_CONCAT(mst_author.author_name SEPARATOR ' - ') AS author
                         FROM biblio b
                         LEFT JOIN biblio_topic bt ON b.biblio_id=bt.biblio_id
                         LEFT JOIN mst_topic mt ON bt.topic_id=mt.topic_id
                         LEFT JOIN biblio_author ba ON b.biblio_id=ba.biblio_id
                         LEFT JOIN mst_author ON ba.author_id=mst_author.author_id
                         WHERE (" . implode(' OR ', $tconds) . ")
                         AND b.biblio_id != " . (int)$biblio_id . "
                         AND b.title IS NOT NULL
                         GROUP BY b.biblio_id ORDER BY b.last_update DESC LIMIT " . (6 - count($sonic_similar_books));
                $q2 = $dbs->query($sql2);
                if ($q2) {
                    while ($r = $q2->fetch_assoc()) {
                        if (!in_array($r['biblio_id'], array_column($sonic_similar_books, 'biblio_id'))) $sonic_similar_books[] = $r;
                    }
                }
            }
        }
    } catch (Throwable $e) { /* silent */ }
}
?>

<section class="sonic-detail-revolution">
    <!-- LEFT SIDEBAR: 3D Book + Actions -->
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
                <div class="sonic-avail-stat">
                    <span class="sonic-avail-number sonic-success"><?= $sonic_available_items ?></span>
                    <span class="sonic-avail-label">Tersedia</span>
                </div>
                <div class="sonic-avail-stat">
                    <span class="sonic-avail-number sonic-danger"><?= $sonic_borrowed_items ?></span>
                    <span class="sonic-avail-label">Dipinjam</span>
                </div>
                <div class="sonic-avail-stat">
                    <span class="sonic-avail-number"><?= $sonic_total_items ?></span>
                    <span class="sonic-avail-label">Total</span>
                </div>
            </div>
            <?php if ($sonic_total_items > 0): ?>
            <div class="sonic-avail-bar">
                <div class="sonic-avail-bar-fill" style="width: <?= round(($sonic_available_items / max(1, $sonic_total_items)) * 100) ?>%"></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($call_number)): ?>
            <div class="sonic-call-number">
                <i class="fas fa-map-marker-alt"></i>
                <span>Call Number: <strong><?= htmlspecialchars($call_number) ?></strong></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="sonic-detail-actions">
            <a href="javascript:void(0)" data-id="<?= $biblio_id ?>" class="sonic-action-btn bookMarkBook <?= $setBookmarked ?>" id="bookmarkBtn">
                <i class="far fa-heart"></i> <span id="label-<?= $biblio_id ?>"><?= (isset($_SESSION['bookmark'][$biblio_id]) ? __('Bookmarked') : __('Bookmark')) ?></span>
            </a>
            <a href="javascript:void(0)" data-id="<?= $biblio_id ?>" data-title="<?= htmlspecialchars($title ?? '') ?>" data-target="#mediaSocialModal" class="sonic-action-btn">
                <i class="fas fa-share-alt"></i> <?= __('Share') ?>
            </a>
            <a href="javascript:void(0)" class="sonic-action-btn add-to-chart-button" data-biblio="<?= $biblio_id ?>">
                <i class="fas fa-shopping-basket"></i> <?= __('Add to Basket') ?>
            </a>
            <a href="index.php?p=show_detail&id=<?= $biblio_id ?>&MARC=true" class="sonic-action-btn sonic-marc-trigger">
                <i class="fas fa-terminal"></i> MARC View
            </a>
        </div>
    </div>

    <!-- RIGHT MAIN CONTENT -->
    <div class="sonic-detail-main">
        <div class="sonic-detail-header">
            <p class="sonic-detail-category"><i class="fas fa-book"></i> <?= $gmd_name ?? '' ?></p>
            <h1 class="sonic-detail-title"><?= stripslashes($title ?? '') ?></h1>
            <div class="sonic-detail-authors">
                <?php if (is_array($authors ?? null)): ?>
                    <?php foreach ($authors as $a): ?>
                        <a href="index.php?author=<?= urlencode(is_array($a) ? ($a['author_name'] ?? '') : $a) ?>&search=search"><?= htmlspecialchars(is_array($a) ? ($a['author_name'] ?? '') : $a) ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?= str_replace("<br />", ' ', $authors ?? '') ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="sonic-detail-tabs">
            <button class="sonic-tab active" data-tab="description"><i class="fas fa-align-left"></i> Deskripsi</button>
            <button class="sonic-tab" data-tab="details"><i class="fas fa-info-circle"></i> Detail</button>
            <button class="sonic-tab" data-tab="citations"><i class="fas fa-quote-right"></i> Sitasi</button>
            <button class="sonic-tab" data-tab="availability"><i class="fas fa-box"></i> Eksemplar</button>
            <button class="sonic-tab" data-tab="comments"><i class="fas fa-comments"></i> Komentar</button>
            <?php if (count($sonic_similar_books) > 0): ?>
            <button class="sonic-tab" data-tab="similar"><i class="fas fa-layer-group"></i> Serupa (<?= count($sonic_similar_books) ?>)</button>
            <?php endif; ?>
        </div>

        <div class="sonic-tab-content">
            <div class="sonic-tab-panel active" id="tab-description">
                <div class="sonic-info-block">
                    <h5><?= __('Abstract') ?></h5>
                    <div class="sonic-abstract-text">
                        <?= $notes ? $notes : '<p style="color:var(--sonic-muted);font-style:italic;">' . __('Description Not Available') . '</p>' ?>
                    </div>
                </div>
            </div>

            <div class="sonic-tab-panel" id="tab-details">
                <div class="sonic-info-block">
                    <h5><?= __('Detail Information') ?></h5>
                    <dl class="sonic-detail-dl">
                        <dt><?= __('Series Title') ?></dt><dd><?= $series_title ? $series_title : '-' ?></dd>
                        <dt><?= __('Call Number') ?></dt><dd><span class="sonic-highlight"><?= $call_number ? $call_number : '-' ?></span></dd>
                        <dt><?= __('Publisher') ?></dt><dd><?= $publish_place ?> : <?= $publisher_name ?>., <?= $publish_year ?></dd>
                        <dt><?= __('Specific Detail Info') ?></dt><dd><?= $spec_detail_info ? $spec_detail_info : '-' ?></dd>
                        <dt><?= __('Statement of Responsibility') ?></dt><dd><?= $sor ? $sor : '-' ?></dd>
                    </dl>
                </div>
                <?php if (count($biblio_custom ?? []) > 0): ?>
                <div class="sonic-info-block">
                    <h5><?= __('Other Information') ?></h5>
                    <dl class="sonic-detail-dl">
                        <?php foreach ($biblio_custom as $item): ?>
                            <dt><?= $item['label'] ?></dt><dd><?= $item['value'] ? $item['value'] : '-' ?></dd>
                        <?php endforeach; ?>
                    </dl>
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
                            <div class="sonic-citation-head">
                                <span class="sonic-citation-style"><?= $style ?></span>
                                <button class="sonic-cite-copy" data-style="<?= $style ?>" title="Copy to clipboard">
                                    <i class="far fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="sonic-citation-body" id="citation-<?= strtolower($style) ?>"><?= $citation ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="sonic-cite-all" id="copyAllCitations">
                        <i class="fas fa-clipboard-list"></i> Copy Semua Sitasi
                    </button>
                </div>
            </div>

            <div class="sonic-tab-panel" id="tab-availability">
                <div class="sonic-info-block">
                    <h5><?= __('Availability') ?></h5>
                    <div class="sonic-availability-table">
                        <?= ($availability) ? $availability : '<p style="color:var(--sonic-danger);">' . __('No copy data') . '</p>' ?>
                    </div>
                </div>
            </div>

            <div class="sonic-tab-panel" id="tab-comments">
                <div class="sonic-info-block" id="comment">
                    <h5><?= __('Comments') ?></h5>
                    <div class="sonic-comments-section">
                        <?= showComment($biblio_id) ?>
                    </div>
                </div>
            </div>

            <?php if (count($sonic_similar_books) > 0): ?>
            <div class="sonic-tab-panel" id="tab-similar">
                <div class="sonic-similar-section">
                    <h5><i class="fas fa-layer-group"></i> Buku Serupa</h5>
                    <p class="sonic-similar-sub">Buku lain dengan pengarang atau subjek yang mirip</p>
                    <div class="sonic-biblio-grid">
                        <?php foreach ($sonic_similar_books as $book): ?>
                        <a class="sonic-book" href="index.php?p=show_detail&id=<?= (int)$book['biblio_id'] ?>">
                            <div class="sonic-book-cover">
                                <img loading="lazy" src="<?= sonic_similar_cover($book['image'] ?? '') ?>" alt="<?= htmlspecialchars($book['title']) ?>" onerror="this.src='./images/default/image.png'">
                            </div>
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
<div id="copyToast" class="sonic-copy-toast">
    <i class="fas fa-check-circle"></i>
    <span id="copyToastText">Disalin!</span>
</div>