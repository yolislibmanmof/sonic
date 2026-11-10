<?php
# @Filename: _modal_topic.php (SONIC Edition)
?>

<div class="sonic-adv-modal" id="exampleModal">
    <div class="sonic-adv-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h3 class="sonic-section-title">📚 <?= __('All Topics') ?></h3>
            <button type="button" class="sonic-terminal-close" onclick="document.getElementById('exampleModal').classList.remove('active')">&times;</button>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <?php
            $topics = getTopic($dbs, 0);
            foreach ($topics as $topic): ?>
                <a href="index.php?subject=<?= urlencode($topic) ?>&search=search" class="sonic-tag"><?= htmlspecialchars($topic) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>