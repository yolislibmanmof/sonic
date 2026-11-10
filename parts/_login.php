<?php
# @Filename: _login.php (SONIC — Admin Command Center)
?>

<div class="sonic-admin-login-wrap">
    <div class="sonic-auth-frame sonic-admin-frame">
        <div class="sonic-auth-corner tl"></div>
        <div class="sonic-auth-corner tr"></div>
        <div class="sonic-auth-corner bl"></div>
        <div class="sonic-auth-corner br"></div>
        <div class="sonic-auth-scanline"></div>

        <div class="sonic-auth-header">
            <div class="sonic-auth-icon sonic-admin-icon"><i class="fas fa-user-shield"></i></div>
            <div>
                <h2 class="sonic-auth-title"><?= __('Librarian Access') ?></h2>
                <p class="sonic-auth-sub"><?= __('Restricted area — authorized personnel only') ?></p>
            </div>
            <div class="sonic-auth-status sonic-admin-status">
                <span class="sonic-status-dot"></span>
                <span><?= __('Secure Channel') ?></span>
            </div>
        </div>

        <div class="sonic-auth-body">
            <?php echo $main_content; ?>
        </div>

        <div class="sonic-admin-footer">
            <div class="sonic-admin-badge"><i class="fas fa-shield-alt"></i> <?= __('SLiMS') ?> <?= SENAYAN_VERSION ?></div>
            <a href="index.php" class="sonic-admin-home"><i class="fas fa-arrow-left"></i> <?= __('Back to OPAC') ?></a>
        </div>
    </div>
</div>