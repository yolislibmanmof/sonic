<?php
# @Filename: footer.php (SONIC v4.5 — Live Stats + Config Socials + i18n)

/* ===== LIVE STATS (read-only, guarded) ===== */
$sonic_stats = ['collections' => 0, 'members' => 0, 'downloads' => 0, 'hours' => 24];
if (isset($dbs) && is_object($dbs)) {
    try {
        $r = $dbs->query('SELECT COUNT(*) FROM biblio');
        if ($r) $sonic_stats['collections'] = (int)$r->fetch_row()[0];
        $r = $dbs->query("SELECT COUNT(*) FROM member WHERE expire_date >= CURDATE()");
        if ($r) $sonic_stats['members'] = (int)$r->fetch_row()[0];
        $r = $dbs->query('SELECT COUNT(*) FROM biblio_attachment');
        if ($r) $sonic_stats['downloads'] = (int)$r->fetch_row()[0];
    } catch (Throwable $e) { /* silent */ }
}
$sonic_social = [
    'facebook'  => $sysconf['template']['sonic_fb_link'] ?? '',
    'twitter'   => $sysconf['template']['sonic_twitter_link'] ?? '',
    'instagram' => $sysconf['template']['sonic_instagram_link'] ?? '',
    'youtube'   => $sysconf['template']['sonic_youtube_link'] ?? '',
    'telegram'  => $sysconf['template']['sonic_telegram_link'] ?? '',
];
$sonic_email = $sysconf['template']['sonic_contact_email'] ?? 'library@example.com';
?>

<footer class="sonic-footer" id="sonicFooter">
    <div class="sonic-footer-grid-bg"></div>

    <div class="sonic-footer-top">
        <div class="sonic-container">
            <div class="sonic-stats-row">
                <div class="sonic-stat-card">
                    <div class="sonic-stat-icon"><i class="fas fa-book"></i></div>
                    <div class="sonic-stat-content">
                        <span class="sonic-stat-number" data-target="<?= (int)$sonic_stats['collections'] ?>">0</span>
                        <span class="sonic-stat-label"><?= sonic_t('Total Collections') ?></span>
                    </div>
                </div>
                <div class="sonic-stat-card">
                    <div class="sonic-stat-icon"><i class="fas fa-users"></i></div>
                    <div class="sonic-stat-content">
                        <span class="sonic-stat-number" data-target="<?= (int)$sonic_stats['members'] ?>">0</span>
                        <span class="sonic-stat-label"><?= sonic_t('Active Members') ?></span>
                    </div>
                </div>
                <div class="sonic-stat-card">
                    <div class="sonic-stat-icon"><i class="fas fa-download"></i></div>
                    <div class="sonic-stat-content">
                        <span class="sonic-stat-number" data-target="<?= (int)$sonic_stats['downloads'] ?>">0</span>
                        <span class="sonic-stat-label"><?= sonic_t('Downloads') ?></span>
                    </div>
                </div>
                <div class="sonic-stat-card">
                    <div class="sonic-stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="sonic-stat-content">
                        <span class="sonic-stat-number" data-target="<?= (int)$sonic_stats['hours'] ?>">0</span>
                        <span class="sonic-stat-label"><?= sonic_t('Hours Online') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sonic-container">
        <div class="sonic-footer-grid">
            <div class="sonic-footer-brand">
                <div class="sonic-footer-logo">
                    <?php
                    if(isset($sysconf['logo_image']) && $sysconf['logo_image'] != '' && $imagesDisk->isExists($path = 'default/'.$sysconf['logo_image'])){
                        echo '<img src="'.SWB . 'lib/minigalnano/createthumb.php?filename=images/' . $path.'&width=350" alt="logo">';
                    } else {
                        echo '<div class="sonic-logo-glow"><i class="fas fa-bolt"></i></div>';
                    }
                    ?>
                </div>
                <h3 class="sonic-footer-title"><?= $sysconf['library_name'] ?></h3>
                <p class="sonic-footer-desc"><?= $sysconf['template']['sonic_footer_about_us'] ?? sonic_t('Digital library with lightning-fast access') ?></p>

                <div class="sonic-social-links">
                    <?php foreach ($sonic_social as $net => $link): if ($link === '') continue; ?>
                    <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener" class="sonic-social-btn" title="<?= ucfirst($net) ?>"><i class="fab fa-<?= $net === 'facebook' ? 'facebook-f' : ($net === 'telegram' ? 'telegram-plane' : $net) ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sonic-footer-col">
                <h4 class="sonic-footer-heading"><i class="fas fa-compass"></i> <?= sonic_t('Quick Links') ?></h4>
                <ul class="sonic-footer-links">
                    <li><a href="index.php?p=libinfo"><i class="fas fa-angle-right"></i> <?= sonic_t('Information') ?></a></li>
                    <li><a href="index.php?p=libinfo"><i class="fas fa-angle-right"></i> <?= sonic_t('Services') ?></a></li>
                    <li><a href="index.php?p=news"><i class="fas fa-angle-right"></i> <?= sonic_t('News') ?></a></li>
                    <li><a href="index.php?p=help"><i class="fas fa-angle-right"></i> <?= sonic_t('Help') ?></a></li>
                    <li><a href="index.php?p=librarian"><i class="fas fa-angle-right"></i> <?= sonic_t('Librarian') ?></a></li>
                    <li><a href="index.php?p=member"><i class="fas fa-angle-right"></i> <?= sonic_t('Member Area') ?></a></li>
                </ul>
            </div>

            <div class="sonic-footer-col">
                <h4 class="sonic-footer-heading"><i class="fas fa-cogs"></i> <?= sonic_t('Our Services') ?></h4>
                <ul class="sonic-footer-links">
                    <li><a href="index.php?search=search&gmd=E-Book"><i class="fas fa-angle-right"></i> <?= sonic_t('E-Books') ?></a></li>
                    <li><a href="index.php?search=search&gmd=Journal"><i class="fas fa-angle-right"></i> <?= sonic_t('Journals') ?></a></li>
                    <li><a href="index.php?search=search&gmd=Thesis"><i class="fas fa-angle-right"></i> <?= sonic_t('Thesis & Dissertations') ?></a></li>
                    <li><a href="mailto:<?= htmlspecialchars($sonic_email) ?>?subject=<?= rawurlencode(sonic_t('Interlibrary Loan')) ?>"><i class="fas fa-angle-right"></i> <?= sonic_t('Interlibrary Loan') ?></a></li>
                    <li><a href="mailto:<?= htmlspecialchars($sonic_email) ?>?subject=<?= rawurlencode(sonic_t('Reference Service')) ?>"><i class="fas fa-angle-right"></i> <?= sonic_t('Reference Service') ?></a></li>
                </ul>
            </div>

            <div class="sonic-footer-col">
                <h4 class="sonic-footer-heading"><i class="fas fa-search"></i> <?= sonic_t('Quick Search') ?></h4>
                <form action="index.php" class="sonic-footer-search">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="sonic-search-box">
                        <i class="fas fa-search"></i>
                        <input name="keywords" type="text" placeholder="<?= sonic_t('Search collections...') ?>" autocomplete="off">
                        <button type="submit" name="search" value="search" class="sonic-search-btn"><i class="fas fa-bolt"></i></button>
                    </div>
                </form>

                <div class="sonic-footer-contact">
                    <h5 class="sonic-contact-title"><i class="fas fa-headset"></i> <?= sonic_t('Need Help?') ?></h5>
                    <p class="sonic-contact-text"><?= sonic_t('Our team is ready to assist you') ?></p>
                    <a href="mailto:<?= htmlspecialchars($sonic_email) ?>" class="sonic-contact-btn"><i class="fas fa-envelope"></i> <?= htmlspecialchars($sonic_email) ?></a>
                </div>

                <div class="sonic-footer-actions">
                    <a target="_blank" rel="noopener" href="https://slims.web.id/web/pages/support-us/" class="sonic-action-btn sonic-action-primary">
                        <i class="fas fa-heart"></i> <?= sonic_t('Support SLiMS') ?>
                    </a>
                    <a target="_blank" rel="noopener" href="https://github.com/slims/" class="sonic-action-btn sonic-action-outline">
                        <i class="fab fa-github"></i> <?= sonic_t('Contribute') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="sonic-footer-bottom">
        <div class="sonic-container">
            <div class="sonic-footer-bottom-content">
                <div class="sonic-footer-copyright">
                    <p>&copy; <?= date('Y') ?> <span class="sonic-highlight"><?= $sysconf['library_name'] ?></span>. <?= sonic_t('All rights reserved') ?>.</p>
                    <p><?= sonic_t('Powered by') ?> <a href="https://slims.web.id" target="_blank" rel="noopener" class="sonic-link"><code>SLiMS</code></a> |
                       <?= sonic_t('Template') ?> <span class="sonic-brand-text">SONIC <i class="fas fa-bolt"></i></span></p>
                </div>
                <div class="sonic-footer-badges">
                    <span class="sonic-badge"><i class="fas fa-shield-alt"></i> <?= sonic_t('Secure') ?></span>
                    <span class="sonic-badge"><i class="fas fa-tachometer-alt"></i> <?= sonic_t('Fast') ?></span>
                    <span class="sonic-badge"><i class="fas fa-mobile-alt"></i> <?= sonic_t('Responsive') ?></span>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php
$_sonic_modals = ['_modal_topic.php', '_modal_advanced.php', '_modal_social_media.php'];
foreach ($_sonic_modals as $_modal) {
    $_modal_path = __DIR__ . '/' . $_modal;
    if (file_exists($_modal_path)) {
        include $_modal_path;
    }
}
?>

<script src="<?= JWB; ?>highlight.js"></script>
<?php if(isset($engine) && method_exists($this, 'generateKeywords') && $searchableInJsArray = $this->generateKeywords($engine->searchable_fields)): ?>
    <script>$('.card-body > *').highlight(<?= $searchableInJsArray ?>);</script>
<?php endif; ?>

<?php
$_sonic_js = ['js/app_jquery.js', 'js/sonic.js', 'js/sonic-marc.js', 'js/sonic-facet.js', 'js/sonic-member.js', 'js/sonic-footer.js', 'js/sonic-librarian.js', 'js/sonic-info.js', 'js/sonic-particles.js', 'js/sonic-detail.js', 'js/sonic-suggest.js', 'js/sonic-result.js'];
foreach ($_sonic_js as $_js) {
    $_js_path = __DIR__ . '/../assets/' . $_js;
    if (file_exists($_js_path)) {
        echo '<script src="' . assets($_js) . '?v=' . sonic_asset_ver($_js) . '"></script>' . "\n";
    }
}

$_vegas_path = __DIR__ . '/../assets/js/vegas.js.php';
if (file_exists($_vegas_path)) {
    include $_vegas_path;
}
?>

</body>
</html>