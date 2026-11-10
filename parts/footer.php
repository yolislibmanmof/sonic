<?php
# @Filename: footer.php (SONIC v4.2 — No Chat Buttons)
?>

<footer class="sonic-footer" id="sonicFooter">
    <!-- Animated Grid Background -->
    <div class="sonic-footer-grid-bg"></div>
    
    <!-- Top Section: Stats & Quick Actions -->
    <div class="sonic-footer-top">
        <div class="sonic-container">
            <div class="sonic-stats-row">
                <div class="sonic-stat-card">
                    <div class="sonic-stat-icon"><i class="fas fa-book"></i></div>
                    <div class="sonic-stat-content">
                        <span class="sonic-stat-number" data-target="12547">0</span>
                        <span class="sonic-stat-label"><?= __('Total Collections') ?></span>
                    </div>
                </div>
                <div class="sonic-stat-card">
                    <div class="sonic-stat-icon"><i class="fas fa-users"></i></div>
                    <div class="sonic-stat-content">
                        <span class="sonic-stat-number" data-target="3842">0</span>
                        <span class="sonic-stat-label"><?= __('Active Members') ?></span>
                    </div>
                </div>
                <div class="sonic-stat-card">
                    <div class="sonic-stat-icon"><i class="fas fa-download"></i></div>
                    <div class="sonic-stat-content">
                        <span class="sonic-stat-number" data-target="15623">0</span>
                        <span class="sonic-stat-label"><?= __('Downloads') ?></span>
                    </div>
                </div>
                <div class="sonic-stat-card">
                    <div class="sonic-stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="sonic-stat-content">
                        <span class="sonic-stat-number" data-target="24">0</span>
                        <span class="sonic-stat-label"><?= __('Hours Online') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Footer Grid -->
    <div class="sonic-container">
        <div class="sonic-footer-grid">
            <!-- Brand Column -->
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
                <p class="sonic-footer-desc"><?= $sysconf['template']['sonic_footer_about_us'] ?? __('Digital library with lightning-fast access') ?></p>
                
                <div class="sonic-social-links">
                    <a href="#" class="sonic-social-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="sonic-social-btn" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="sonic-social-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="sonic-social-btn" title="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="sonic-social-btn" title="Telegram"><i class="fab fa-telegram-plane"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="sonic-footer-col">
                <h4 class="sonic-footer-heading"><i class="fas fa-compass"></i> <?= __('Quick Links') ?></h4>
                <ul class="sonic-footer-links">
                    <li><a href="index.php?p=libinfo"><i class="fas fa-angle-right"></i> <?= __('Information') ?></a></li>
                    <li><a href="index.php?p=services"><i class="fas fa-angle-right"></i> <?= __('Services') ?></a></li>
                    <li><a href="index.php?p=news"><i class="fas fa-angle-right"></i> <?= __('News') ?></a></li>
                    <li><a href="index.php?p=help"><i class="fas fa-angle-right"></i> <?= __('Help') ?></a></li>
                    <li><a href="index.php?p=librarian"><i class="fas fa-angle-right"></i> <?= __('Librarian') ?></a></li>
                    <li><a href="index.php?p=member"><i class="fas fa-angle-right"></i> <?= __('Member Area') ?></a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="sonic-footer-col">
                <h4 class="sonic-footer-heading"><i class="fas fa-cogs"></i> <?= __('Our Services') ?></h4>
                <ul class="sonic-footer-links">
                    <li><a href="#"><i class="fas fa-angle-right"></i> <?= __('Digital Collections') ?></a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> <?= __('E-Books') ?></a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> <?= __('Journals') ?></a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> <?= __('Thesis & Dissertations') ?></a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> <?= __('Interlibrary Loan') ?></a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> <?= __('Reference Service') ?></a></li>
                </ul>
            </div>

            <!-- Quick Search & Contact -->
            <div class="sonic-footer-col">
                <h4 class="sonic-footer-heading"><i class="fas fa-search"></i> <?= __('Quick Search') ?></h4>
                <form action="index.php" class="sonic-footer-search">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="sonic-search-box">
                        <i class="fas fa-search"></i>
                        <input name="keywords" type="text" placeholder="<?= __('Search collections...') ?>" autocomplete="off">
                        <button type="submit" name="search" value="search" class="sonic-search-btn"><i class="fas fa-bolt"></i></button>
                    </div>
                </form>

                <div class="sonic-footer-contact">
                    <h5 class="sonic-contact-title"><i class="fas fa-headset"></i> <?= __('Need Help?') ?></h5>
                    <p class="sonic-contact-text"><?= __('Our team is ready to assist you') ?></p>
                    <a href="mailto:library@example.com" class="sonic-contact-btn"><i class="fas fa-envelope"></i> library@example.com</a>
                </div>

                <div class="sonic-footer-actions">
                    <a target="_blank" href="https://slims.web.id/web/pages/support-us/" class="sonic-action-btn sonic-action-primary">
                        <i class="fas fa-heart"></i> <?= __('Support SLiMS') ?>
                    </a>
                    <a target="_blank" href="https://github.com/slims/" class="sonic-action-btn sonic-action-outline">
                        <i class="fab fa-github"></i> <?= __('Contribute') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="sonic-footer-bottom">
        <div class="sonic-container">
            <div class="sonic-footer-bottom-content">
                <div class="sonic-footer-copyright">
                    <p>&copy; <?= date('Y') ?> <span class="sonic-highlight"><?= $sysconf['library_name'] ?></span>. <?= __('All rights reserved') ?>.</p>
                    <p><?= __('Powered by') ?> <a href="https://slims.web.id" target="_blank" class="sonic-link"><code>SLiMS</code></a> | 
                       Template <span class="sonic-brand-text">SONIC <i class="fas fa-bolt"></i></span></p>
                </div>
                <div class="sonic-footer-badges">
                    <span class="sonic-badge"><i class="fas fa-shield-alt"></i> <?= __('Secure') ?></span>
                    <span class="sonic-badge"><i class="fas fa-tachometer-alt"></i> <?= __('Fast') ?></span>
                    <span class="sonic-badge"><i class="fas fa-mobile-alt"></i> <?= __('Responsive') ?></span>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php
// ===== Modals (safe includes) =====
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
// ===== Assets JS (safe) =====
$_sonic_js = ['js/app.js', 'js/app_jquery.js', 'js/sonic.js', 'js/sonic-marc.js', 'js/sonic-facet.js', 'js/sonic-member.js', 'js/sonic-footer.js', 'js/sonic-librarian.js', 'js/sonic-info.js', 'js/sonic-particles.js', 'js/sonic-detail.js', 'js/sonic-suggest.js', 'js/sonic-result.js'];
foreach ($_sonic_js as $_js) {
    $_js_path = __DIR__ . '/../assets/' . $_js;
    if (file_exists($_js_path)) {
        echo '<script src="' . assets($_js) . '"></script>' . "\n";
    }
}

$_vegas_path = __DIR__ . '/../assets/js/vegas.js.php';
if (file_exists($_vegas_path)) {
    include $_vegas_path;
}
?>

</body>
</html>