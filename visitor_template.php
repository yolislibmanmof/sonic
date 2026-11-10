<?php
/**
 * Visitor template (SONIC Edition)
 */
?>
<div class="sonic-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div class="sonic-terminal" style="max-width: 500px;">
        <div class="sonic-terminal-head">
            <span class="sonic-dot r"></span>
            <span class="sonic-dot y"></span>
            <span class="sonic-dot g"></span>
            <span class="sonic-terminal-title">visitor_log.exe</span>
        </div>
        <div class="sonic-terminal-body">
            <h3 style="color: var(--sonic-neon); text-align: center; margin-bottom: 1.5rem;">
                ⚡ <?= _('Library Visitor Log') ?>
            </h3>
            <form method="post" action="index.php?p=visitor">
                <?php foreach ($sysconf['visitor_form'] as $field => $config): ?>
                    <div class="sonic-adv-field">
                        <label><?= $config['label'] ?></label>
                        <?php if ($config['type'] == 'dropdown'): ?>
                            <select name="<?= $field ?>">
                                <?php foreach ($config['data'] as $opt): ?>
                                    <option value="<?= $opt[0] ?>"><?= $opt[1] ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="<?= $config['type'] ?>" name="<?= $field ?>" required>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="sonic-btn" style="width: 100%; justify-content: center;">⚡ <?= _('Submit') ?></button>
            </form>
        </div>
    </div>
</div>