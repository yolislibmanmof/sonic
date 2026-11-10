<?php
/* @Filename: detail_template.php (SONIC v2) */
$setBookmarked = trim(isset($_SESSION['bookmark'][$biblio_id]) ? 'active' : '');

// Sampul: inti SLiMS memakai $image_src
$sonic_cover_raw = $image_src ?? ($image ?? '');
$sonic_cover_url = !empty($sonic_cover_raw)
    ? './lib/minigalnano/createthumb.php?filename=' . urlencode('images/docs/' . basename($sonic_cover_raw)) . '&width=420'
    : './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=420';
?>

<section class="sonic-detail-hero">
    <div class="sonic-detail-cover-wrap">
        <div class="sonic-detail-cover">
            <img src="<?= $sonic_cover_url ?>" alt="<?= htmlspecialchars($title) ?>" onerror="this.src='./images/default/image.png'">
        </div>
        <div style="margin-top:1.5rem;display:flex;flex-direction:column;gap:.8rem;">
            <a href="javascript:void(0)" data-id="<?= $biblio_id ?>" class="sonic-btn bookMarkBook <?= $setBookmarked ?>">
                <i class="far fa-bookmark"></i> <span id="label-<?= $biblio_id ?>"><?= (isset($_SESSION['bookmark'][$biblio_id]) ? __('Bookmarked') : __('Bookmark')) ?></span>
            </a>
            <a href="javascript:void(0)" data-id="<?= $biblio_id ?>" data-title="<?= htmlspecialchars($title) ?>" data-target="#mediaSocialModal" class="sonic-btn-outline">
                <i class="fas fa-share-alt"></i> <?= __('Share') ?>
            </a>
            <a href="index.php?p=cite&id=<?= $biblio_id ?>" class="sonic-btn-outline openPopUp citationLink">
                <i class="fas fa-quote-right"></i> <?= __('Cite This') ?>
            </a>
<a href="index.php?p=show_detail&id=<?= $biblio_id ?>&MARC=true" class="sonic-btn-outline sonic-marc-trigger">
    <i class="fas fa-terminal"></i> MARC VIEWER
</a>
        </div>
    </div>

    <div>
        <p style="color:var(--sonic-neon);margin-bottom:.5rem;"><i class="fas fa-book"></i> <?= $gmd_name ?></p>
        <h1 class="sonic-detail-title"><?= stripslashes($title) ?></h1>
        <div class="sonic-detail-authors"><?= str_replace("<br />", ' ', $authors) ?></div>

        <div class="sonic-info-block">
            <h5><?= __('Abstract') ?></h5>
            <p style="color:var(--sonic-text);line-height:1.8;"><?= $notes ? $notes : '<i>'.__('Description Not Available').'</i>' ?></p>
        </div>

        <div class="sonic-info-block">
            <h5><?= __('Availability') ?></h5>
            <?= ($availability) ? $availability : '<p style="color:var(--sonic-danger);">'.__('No copy data').'</p>' ?>
        </div>

        <div class="sonic-info-block">
            <h5><?= __('Detail Information') ?></h5>
            <dl>
                <dt><?= __('Series Title') ?></dt><dd><?= $series_title ? $series_title : '-' ?></dd>
                <dt><?= __('Call Number') ?></dt><dd><?= $call_number ? $call_number : '-' ?></dd>
                <dt><?= __('Publisher') ?></dt><dd><?= $publish_place ?> : <?= $publisher_name ?>., <?= $publish_year ?></dd>
                <dt><?= __('Specific Detail Info') ?></dt><dd><?= $spec_detail_info ? $spec_detail_info : '-' ?></dd>
                <dt><?= __('Statement of Responsibility') ?></dt><dd><?= $sor ? $sor : '-' ?></dd>
            </dl>
        </div>

        <?php if (count($biblio_custom) > 0): ?>
        <div class="sonic-info-block">
            <h5><?= __('Other Information') ?></h5>
            <dl>
                <?php foreach ($biblio_custom as $item): ?>
                    <dt><?= $item['label'] ?></dt><dd><?= $item['value'] ? $item['value'] : '-' ?></dd>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php endif; ?>

        <div class="sonic-info-block">
            <h5 id="comment"><?= __('Comments') ?></h5>
            <?= showComment($biblio_id) ?>
        </div>
    </div>
</section>