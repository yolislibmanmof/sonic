<?php
# @Filename: _other.php (SONIC v3 — Info Deck)
include '_navbar.php';

if ($_GET['p'] !== 'show_detail'):
    $sonic_compact_hero = true;
    include '_search-form.php';
    
    $sonic_is_librarian = ($_GET['p'] === 'librarian');
    $sonic_is_libinfo   = ($_GET['p'] === 'libinfo');
?>
<section class="sonic-container" style="padding:2.5rem 1.5rem 4rem;">
    <h2 class="sonic-section-title mb-4"><?= $page_title ?></h2>
    
    <?php if ($sonic_is_librarian): ?>
        <div class="sonic-librarian-wrapper">
            <div class="sonic-librarian-header">
                <i class="fas fa-user-tie"></i>
                <span><?= __('Meet Our Librarians') ?></span>
            </div>
            <div class="sonic-librarian-grid" id="sonicLibrarianGrid">
                <?php echo $main_content; ?>
            </div>
        </div>
        
    <?php elseif ($sonic_is_libinfo): ?>
        <!-- INFO DECK: auto-skin markup bawaan SLiMS -->
        <div class="sonic-info-deck" id="sonicInfoDeck">
            <div class="sonic-info-deck-inner">
                <?php echo $main_content; ?>
            </div>
        </div>
        
    <?php else: ?>
        <div class="sonic-info-block">
            <?php echo $main_content; ?>
        </div>
    <?php endif; ?>
</section>
<?php else:
    echo $main_content;
endif;