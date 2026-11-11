<?php
# @Filename: _modal_advanced.php (SONIC Edition - Terminal Style)
?>

<div class="sonic-adv-modal" id="adv-modal">
    <div class="sonic-adv-box">
        <form action="index.php" method="get">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 class="sonic-section-title">⚡ <?= __('Advanced Search') ?></h3>
                <button type="button" class="sonic-terminal-close" data-dismiss="modal" onclick="document.getElementById('adv-modal').classList.remove('active')">&times;</button>
            </div>

            <div class="sonic-adv-grid">
                <div class="sonic-adv-field">
                    <label><?= __('Title') ?></label>
                    <input type="text" name="title" placeholder="<?= __('Enter title') ?>">
                </div>
                <div class="sonic-adv-field">
                    <label><?= __('Author(s)') ?></label>
                    <input type="text" name="author" placeholder="<?= __('Enter author(s) name') ?>">
                </div>
                <div class="sonic-adv-field">
                    <label><?= __('Subject(s)') ?></label>
                    <input type="text" name="subject" placeholder="<?= __('Enter subject') ?>">
                </div>
                <div class="sonic-adv-field">
                    <label><?= __('ISBN/ISSN') ?></label>
                    <input type="text" name="isbn" placeholder="<?= __('Enter ISBN/ISSN') ?>">
                </div>
                <div class="sonic-adv-field">
                    <label><?= __('Publish Year') ?></label>
                    <input type="text" name="publishyear" placeholder="<?= __('Enter publish year') ?>">
                </div>
                <div class="sonic-adv-field">
                    <label><?= __('Location') ?></label>
                    <select name="location"><?= sonic_common_list('location') ?></select>
                </div>
                <div class="sonic-adv-field">
                    <label><?= __('GMD') ?></label>
                    <select name="gmd"><?= sonic_common_list('gmd') ?></select>
                </div>
                <div class="sonic-adv-field">
                    <label><?= __('Collection Type') ?></label>
                    <select name="colltype"><?= sonic_common_list('collection') ?></select>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="sonic-btn-outline" onclick="document.getElementById('adv-modal').classList.remove('active')"><?= __('Cancel') ?></button>
                <button type="submit" name="search" value="search" class="sonic-btn">⚡ <?= __('Find Collection') ?></button>
            </div>
        </form>
    </div>
</div>