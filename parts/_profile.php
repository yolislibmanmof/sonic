<?php
# @Filename: _profile.php (SONIC FINAL — Library Profile, editable via Kustomisasi)
$sp = [
    'vision'  => trim((string)($sysconf['template']['sonic_profile_vision'] ?? '')),
    'mission' => trim((string)($sysconf['template']['sonic_profile_mission'] ?? '')),
    'year'    => trim((string)($sysconf['template']['sonic_profile_year'] ?? '')),
    'accred'  => trim((string)($sysconf['template']['sonic_profile_accreditation'] ?? '')),
];
$sp_empty = ($sp['vision'] === '' && $sp['mission'] === '' && $sp['year'] === '' && $sp['accred'] === '');

/* ===== PARSER MISI: tahan simpan SLiMS =====
   Menerima pemisah:  ||  atau  |  atau baris baru  atau daftar bernomor (1. 2. 3.)
   Nomor awal otomatis dibuang -> dirender sebagai bullet neon yang konsisten. */
$sonic_mission_items = [];
if ($sp['mission'] !== '') {
    $_m_raw   = str_replace(['||', '|'], "\n", $sp['mission']);
    $_m_parts = preg_split('/\r\n|\r|\n|(?=\d+[.)]\s)/', $_m_raw);
    foreach ($_m_parts as $_m) {
        $_m = trim(preg_replace('/^\d+[.)]\s+/', '', trim($_m)));
        if ($_m !== '') $sonic_mission_items[] = $_m;
    }
}
?>
<div id="slims-profile" class="sonic-container">
    <section class="sonic-section">
        <h3 class="sonic-section-title"><i class="fas fa-landmark" style="color:var(--sonic-neon);margin-right:.5rem;"></i><?= sonic_t('Library Profile') ?></h3>
        <p class="sonic-section-sub"><?= sonic_t('Vision, mission, history and accreditation of our library') ?></p>
    </section>

    <?php if ($sp_empty): ?>
    <div class="sonic-profile-card">
        <p style="color:var(--sonic-muted);font-style:italic;"><?= sonic_t('Profile content is not set yet. Please fill it in Theme Customization.') ?></p>
    </div>
    <?php else: ?>
    <div class="sonic-profile-grid">

        <?php if ($sp['vision'] !== ''): ?>
        <div class="sonic-profile-card">
            <div class="sonic-profile-head"><span class="sonic-profile-icon"><i class="fas fa-eye"></i></span><h4><?= sonic_t('Vision') ?></h4></div>
            <div class="sonic-profile-body"><p><?= nl2br(htmlspecialchars($sp['vision'])) ?></p></div>
        </div>
        <?php endif; ?>

        <?php if (count($sonic_mission_items) > 0): ?>
        <div class="sonic-profile-card">
            <div class="sonic-profile-head"><span class="sonic-profile-icon"><i class="fas fa-flag"></i></span><h4><?= sonic_t('Mission') ?></h4></div>
            <div class="sonic-profile-body">
                <ul class="sonic-profile-list">
                    <?php foreach ($sonic_mission_items as $_mi): ?>
                    <li><?= htmlspecialchars($_mi) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($sp['year'] !== ''): ?>
        <div class="sonic-profile-card">
            <div class="sonic-profile-head"><span class="sonic-profile-icon"><i class="fas fa-calendar-alt"></i></span><h4><?= sonic_t('Founding Year') ?></h4></div>
            <div class="sonic-profile-body"><span class="sonic-profile-big"><?= htmlspecialchars($sp['year']) ?></span></div>
        </div>
        <?php endif; ?>

        <?php if ($sp['accred'] !== ''): ?>
        <div class="sonic-profile-card">
            <div class="sonic-profile-head"><span class="sonic-profile-icon"><i class="fas fa-award"></i></span><h4><?= sonic_t('Accreditation Status') ?></h4></div>
            <div class="sonic-profile-body"><span class="sonic-profile-big sonic-profile-accred"><?= htmlspecialchars($sp['accred']) ?></span></div>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>
</div>