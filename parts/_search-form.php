<?php
# @Filename: _search-form.php (SONIC — FINAL: slider stabil + WebP ringan + SLIDE VIDEO 30 dtk)
$sonic_compact = !empty($sonic_compact_hero);

/* ===== SLIDER SCANNER HYBRID (foto + video, jumlah slide TANPA BATAS) =====
 * Folder yang dipindai:
 *   assets/video/          → semua video (mp4/m4v/webm/ogv)
 *   assets/images/slider/  → semua foto & video
 *   assets/images/         → hanya file bernama slide* (foto & video)
 * Urutan slide = urutan natural nama file (slide1.jpg, slide2.mp4, slide3.webp, dst).
 * Nama sama beda ekstensi → prioritas: mp4 > m4v > webm > ogv > webp > jpg > png > gif.
 * Gambar bernama sama dengan video otomatis dipakai sebagai POSTER video.
 */
$slider_imgs = [];
$slider_seen = [];
$slider_dirs = [
    __DIR__ . '/../assets/video'         => 'video/',
    __DIR__ . '/../assets/images/slider' => 'images/slider/',
    __DIR__ . '/../assets/images'        => 'images/',
];
$slider_prio = ['mp4' => 8, 'm4v' => 7, 'webm' => 6, 'ogv' => 5, 'webp' => 4, 'jpg' => 3, 'jpeg' => 3, 'png' => 2, 'gif' => 1];
$slider_video_ext = ['mp4', 'm4v', 'webm', 'ogv'];
foreach ($slider_dirs as $dir => $prefix) {
    if (!is_dir($dir)) continue;
    $files = scandir($dir);
    if ($files === false) continue;
    foreach ($files as $sf) {
        if (!preg_match('/\.(mp4|m4v|webm|ogv|jpg|jpeg|png|webp|gif)$/i', $sf, $mm)) continue;
        if ($prefix === 'images/' && !preg_match('/^slide/i', $sf)) continue;
        $ext  = strtolower($mm[1]);
        $key  = strtolower(preg_replace('/\.[^.]+$/', '', $sf));
        $prio = $slider_prio[$ext] ?? 0;
        if (isset($slider_seen[$key]) && $slider_seen[$key]['prio'] >= $prio) continue;
        $slider_seen[$key] = ['prio' => $prio, 'item' => [
            'src'    => assets($prefix . rawurlencode($sf)),
            'name'   => $sf,
            'type'   => in_array($ext, $slider_video_ext, true) ? 'video' : 'image',
            'poster' => '',
        ]];
    }
}
/* Poster otomatis: gambar bernama sama dengan video (di folder mana pun yang dipindai) */
foreach ($slider_seen as &$slider_rec) {
    if ($slider_rec['item']['type'] !== 'video') continue;
    $slider_base = preg_replace('/\.[^.]+$/', '', $slider_rec['item']['name']);
    foreach ($slider_dirs as $dir => $prefix) {
        foreach (['webp', 'jpg', 'jpeg', 'png', 'gif'] as $pe) {
            if (is_file($dir . '/' . $slider_base . '.' . $pe)) {
                $slider_rec['item']['poster'] = assets($prefix . rawurlencode($slider_base . '.' . $pe));
                break 2;
            }
        }
    }
}
unset($slider_rec);
foreach ($slider_seen as $s) $slider_imgs[] = $s['item'];
usort($slider_imgs, function ($a, $b) { return strnatcasecmp($a['name'], $b['name']); });
$sonic_slider_count = count($slider_imgs);
$sonic_slider_has_video = false;
foreach ($slider_imgs as $s) { if ($s['type'] === 'video') { $sonic_slider_has_video = true; break; } }

/* Batas durasi video per slide: 30 detik (bisa dioverride lewat sysconf bila kelak didaftarkan) */
$sonic_video_max = 30;
if (!empty($sysconf['template']['sonic_video_max']) && (int)$sysconf['template']['sonic_video_max'] > 0) {
    $sonic_video_max = (int)$sysconf['template']['sonic_video_max'];
}
$sonic_vm_label = floor($sonic_video_max / 60) . ':' . str_pad($sonic_video_max % 60, 2, '0', STR_PAD_LEFT);
?>

<section class="sonic-hero<?= $sonic_compact ? ' compact' : '' ?>" id="sonicHero">

    <?php if (!$sonic_compact && $sonic_slider_count > 0): ?>
    <!-- ===== FULLSCREEN HYBRID SLIDER (foto + video, semua slide tampil) ===== -->
    <?php $sonic_first = $slider_imgs[0]; ?>
    <?php if ($sonic_first['type'] === 'image'): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars($sonic_first['src'], ENT_QUOTES) ?>">
    <?php elseif ($sonic_first['poster'] !== ''): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars($sonic_first['poster'], ENT_QUOTES) ?>">
    <?php endif; ?>
    <div class="sonic-slider" id="sonicSlider">
        <?php foreach ($slider_imgs as $si => $s): $surl = htmlspecialchars($s['src'], ENT_QUOTES); ?>
            <?php if ($s['type'] === 'video'): ?>
            <div class="sonic-slide sonic-slide-video<?= $si === 0 ? ' active' : '' ?>" data-type="video" data-duration="<?= (int)$sonic_video_max ?>">
                <video src="<?= $surl ?>"<?= $s['poster'] !== '' ? ' poster="' . htmlspecialchars($s['poster'], ENT_QUOTES) . '"' : '' ?>
                    muted playsinline disablepictureinpicture tabindex="-1" aria-hidden="true"
                    preload="<?= $si === 0 ? 'auto' : 'metadata' ?>"></video>
            </div>
            <?php else: ?>
            <div class="sonic-slide<?= $si === 0 ? ' active' : '' ?>" data-type="image" style="background-image:url('<?= $surl ?>')"></div>
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="sonic-slider-overlay"></div>

        <div class="sonic-hud hud-tl"></div>
        <div class="sonic-hud hud-tr"></div>
        <div class="sonic-hud hud-bl"></div>
        <div class="sonic-hud hud-br"></div>

        <!-- ===== WELCOME STAGE: configurable text + walker ===== -->
        <?php
        $sonic_welcome_text = (string)($sysconf['template']['sonic_welcome_text'] ?? 'SELAMAT DATANG DI LAYANAN OPAC PERPUSTAKAAN');
        $sonic_welcome_words = array_values(array_filter(explode(' ', $sonic_welcome_text), fn($w) => $w !== ''));
        $sonic_walker = (string)($sysconf['template']['sonic_walker'] ?? 'person');
        ?>
        <div class="sonic-welcome-stage" aria-hidden="true">
            <?php if (count($sonic_welcome_words) > 0): ?>
            <h2 class="sonic-welcome-text">
                <?php foreach ($sonic_welcome_words as $wi => $w): ?>
                <span class="sonic-welcome-word" style="--wi:<?= $wi ?>"><?= htmlspecialchars($w) ?></span>
                <?php endforeach; ?>
            </h2>
            <?php endif; ?>
            <?php if ($sonic_walker !== 'none'): ?>
            <div class="sonic-walk-track">
                <div class="sonic-walker">
                    <svg class="sonic-walker-svg" viewBox="0 0 64 128" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="sonicWalkerGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#00f0ff"/>
                                <stop offset="1" stop-color="#ff00aa"/>
                            </linearGradient>
                        </defs>
                        <g stroke="url(#sonicWalkerGrad)" stroke-width="7" stroke-linecap="round">
                        <?php if ($sonic_walker === 'robot'): ?>
                            <line x1="36" y1="8" x2="36" y2="2"/><circle cx="36" cy="2" r="2.4" fill="url(#sonicWalkerGrad)" stroke="none"/>
                            <rect x="28" y="8" width="16" height="15" rx="3" fill="rgba(0,240,255,.12)"/>
                            <circle cx="33" cy="15" r="1.6" fill="url(#sonicWalkerGrad)" stroke="none"/>
                            <circle cx="39" cy="15" r="1.6" fill="url(#sonicWalkerGrad)" stroke="none"/>
                            <rect x="26" y="26" width="18" height="36" rx="4" fill="rgba(0,240,255,.10)"/>
                            <g class="w-part w-arm w-arm-b"><path d="M36 32 L24 54"/></g>
                            <g class="w-part w-arm w-arm-f"><path d="M36 32 L49 52"/></g>
                            <g class="w-part w-leg w-leg-b"><path d="M34 62 L25 90 L21 116"/></g>
                            <g class="w-part w-leg w-leg-f"><path d="M34 62 L45 89 L47 116"/></g>
                        <?php elseif ($sonic_walker === 'astronaut'): ?>
                            <circle cx="38" cy="13" r="12"/>
                            <circle cx="38" cy="13" r="8" fill="url(#sonicWalkerGrad)" stroke="none"/>
                            <rect x="18" y="26" width="9" height="26" rx="4" fill="rgba(255,0,170,.12)"/>
                            <path class="w-part w-torso" d="M37 25 L34 62"/>
                            <g class="w-part w-arm w-arm-b"><path d="M36 32 L24 54"/></g>
                            <g class="w-part w-arm w-arm-f"><path d="M36 32 L49 52"/></g>
                            <g class="w-part w-leg w-leg-b"><path d="M34 62 L25 90 L21 116"/></g>
                            <g class="w-part w-leg w-leg-f"><path d="M34 62 L45 89 L47 116"/></g>
                        <?php else: ?>
                            <circle cx="38" cy="13" r="9" fill="url(#sonicWalkerGrad)" stroke="none"/>
                            <path class="w-part w-torso" d="M37 24 L34 62"/>
                            <g class="w-part w-arm w-arm-b"><path d="M36 32 L24 54"/></g>
                            <g class="w-part w-arm w-arm-f"><path d="M36 32 L49 52"/></g>
                            <g class="w-part w-leg w-leg-b"><path d="M34 62 L25 90 L21 116"/></g>
                            <g class="w-part w-leg w-leg-f"><path d="M34 62 L45 89 L47 116"/></g>
                        <?php endif; ?>
                        </g>
                    </svg>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <a href="#search-wraper" class="sonic-scroll-cue" aria-label="Gulir ke pencarian"><i class="fas fa-chevron-down"></i></a>

        <?php if ($sonic_slider_count > 1): ?>
        <button type="button" class="sonic-slider-arrow prev" id="sliderPrev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
        <button type="button" class="sonic-slider-arrow next" id="sliderNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
        <div class="sonic-slider-dots" id="sliderDots"></div>
        <?php endif; ?>

        <?php if ($sonic_slider_has_video): ?>
        <div class="sonic-slide-badge" id="sliderBadge" hidden><i class="fas fa-video"></i><span>VIDEO</span><b id="sliderBadgeTime"><?= htmlspecialchars($sonic_vm_label, ENT_QUOTES) ?></b></div>
        <button type="button" class="sonic-slider-sound" id="sliderSound" aria-pressed="false" aria-label="Aktifkan suara video"><i class="fas fa-volume-mute"></i></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="sonic-hero-content">
        <h1 class="sonic-hero-title">
            <i class="fas fa-bolt sonic-bolt"></i>
            <span class="glow" id="sonicTitle" data-text="Discover Knowledge">Discover Knowledge</span>
        </h1>
        <p class="sonic-hero-sub"><?= __('Scan our digital library at the speed of light') ?></p>

        <div class="sonic-search-card" id="search-wraper">
            <form action="index.php" method="get" id="sonicSearchForm" autocomplete="off">
                <input type="hidden" name="search" value="search">
                <div class="sonic-input-wrap">
                    <i class="fas fa-search sonic-input-icon"></i>
                    <input type="text" id="sonic-keywords" name="keywords"
                           value="<?= htmlentities(getQuery('keywords')) ?>"
                           class="sonic-search-input"
                           placeholder="<?= __('Enter keyword to search collection...') ?>"/>
                    <div class="sonic-scanline"></div>
                    <button type="button" class="sonic-voice-btn" id="sonicVoiceBtn" hidden
                            title="<?= __('Search by voice') ?>" aria-label="<?= __('Search by voice') ?>" aria-pressed="false">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button type="submit" class="sonic-search-go" title="<?= __('Search') ?>"><i class="fas fa-bolt"></i></button>
                </div>
                <div class="sonic-voice-status" id="sonicVoiceStatus" hidden aria-live="polite">
                    <span class="sonic-voice-wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
                    <span class="sonic-voice-text" id="sonicVoiceText"></span>
                    <button type="button" class="sonic-voice-cancel" id="sonicVoiceCancel"><?= __('Cancel') ?></button>
                </div>
            </form>

            <div class="sonic-quick-tags" id="sonicQuickTags">
                <button type="button" class="sonic-tag active" data-search-mode="keywords"><i class="fas fa-globe"></i><?= __('ALL') ?></button>
                <button type="button" class="sonic-tag" data-search-mode="author"><i class="fas fa-user-edit"></i><?= __('Author') ?></button>
                <button type="button" class="sonic-tag" data-search-mode="subject"><i class="fas fa-tags"></i><?= __('Subject') ?></button>
                <button type="button" class="sonic-tag" data-search-mode="isbn"><i class="fas fa-barcode"></i><?= __('ISBN/ISSN') ?></button>
                <button type="button" class="sonic-tag" id="sonicAdvBtn"><i class="fas fa-sliders-h"></i><?= __('Advanced Search') ?></button>
            </div>

            <div class="sonic-recent" id="sonicRecent" style="display:none;">
                <p class="sonic-recent-label"><i class="fas fa-history"></i><?= __('Last search:') ?></p>
                <div class="sonic-recent-list" id="sonicRecentList"></div>
            </div>
        </div>
    </div>
</section>