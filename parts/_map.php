<?php
# @Filename: _map.php (SONIC v2 — Library Map + socials)
$sonic_map_raw  = $sysconf['template']['sonic_map'] ?? 1;
$sonic_show_map = !in_array($sonic_map_raw, ['0', 0, false, 'false'], true);
$sonic_map_link = trim((string)($sysconf['template']['sonic_map_link'] ?? ''));
$sonic_map_desc = trim((string)($sysconf['template']['sonic_map_desc'] ?? ''));
if (!$sonic_show_map || $sonic_map_link === '') return;

/* Parser embed tahan-banting: /@lat,lng,z | !3d!4d | ?q= | ?ll= | /place/NAMA | alamat biasa */
if (!function_exists('sonic_map_embed')) {
    function sonic_map_embed($link) {
        $link = trim((string)$link);
        if ($link === '') return '';
        if (stripos($link, 'output=embed') !== false) return $link;
        $lat = null; $lng = null; $zoom = null; $place = '';

        // 1) /@lat,lng,17z
        if (preg_match('#/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)(?:,(\d+(?:\.\d+)?)z)?#', $link, $m)) {
            $lat = $m[1]; $lng = $m[2]; $zoom = isset($m[3]) ? $m[3] : null;
        }
        // 2) blob !3dlat!4dlng
        if ($lat === null && preg_match('#!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)#', $link, $m)) {
            $lat = $m[1]; $lng = $m[2];
        }
        // 3) ?q= atau ?ll=
        if ($lat === null) {
            $qs = parse_url($link, PHP_URL_QUERY);
            if ($qs) {
                parse_str($qs, $params);
                if (!empty($params['q'])) $place = $params['q'];
                elseif (!empty($params['ll'])) {
                    $ll = explode(',', $params['ll']);
                    if (count($ll) === 2) { $lat = $ll[0]; $lng = $ll[1]; }
                }
            }
        }
        // 4) /place/NAMA/
        if ($lat === null && $place === '' && preg_match('#/place/([^/]+)#', $link, $m)) {
            $place = str_replace('+', ' ', urldecode($m[1]));
        }

        if ($lat !== null && $lng !== null) {
            $z = $zoom ? (int)$zoom : 17;
            return 'https://maps.google.com/maps?q=' . $lat . ',' . $lng . '&z=' . $z . '&output=embed';
        }
        if ($place !== '') {
            return 'https://maps.google.com/maps?q=' . urlencode($place) . '&output=embed';
        }
        return 'https://maps.google.com/maps?q=' . urlencode($link) . '&output=embed';
    }
}
$embed = sonic_map_embed($sonic_map_link);

$sonic_socials = array_filter([
    'facebook'  => ['icon'=>'fab fa-facebook-square','url'=>($sysconf['template']['sonic_fb_link'] ?? '')],
    'twitter'   => ['icon'=>'fab fa-twitter','url'=>($sysconf['template']['sonic_twitter_link'] ?? '')],
    'youtube'   => ['icon'=>'fab fa-youtube','url'=>($sysconf['template']['sonic_youtube_link'] ?? '')],
    'instagram' => ['icon'=>'fab fa-instagram','url'=>($sysconf['template']['sonic_instagram_link'] ?? '')],
    'telegram'  => ['icon'=>'fab fa-telegram-plane','url'=>($sysconf['template']['sonic_telegram_link'] ?? '')],
], fn($s) => $s['url'] !== '');
?>
<section class="sonic-map-section" id="sonicMap">
    <div class="sonic-container">
        <div class="sonic-map-head">
            <h3 class="sonic-section-title"><i class="fas fa-map-marked-alt"></i> <?= __('Library Location') ?></h3>
            <?php if ($sonic_map_desc !== ''): ?>
                <p class="sonic-map-desc"><?= nl2br(htmlspecialchars($sonic_map_desc)) ?></p>
            <?php endif; ?>
        </div>
        <div class="sonic-map-frame">
            <iframe src="<?= htmlspecialchars($embed) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?= __('Library Location') ?>" allowfullscreen></iframe>
        </div>
        <div class="sonic-map-foot">
            <a class="sonic-map-open" href="<?= htmlspecialchars($sonic_map_link) ?>" target="_blank" rel="noopener">
                <i class="fas fa-directions"></i> <?= __('Get Directions') ?>
            </a>
            <?php if (count($sonic_socials) > 0): ?>
            <div class="sonic-map-socials">
                <?php foreach ($sonic_socials as $net => $s): ?>
                <a class="sonic-mini-btn" target="_blank" rel="noopener" href="<?= htmlspecialchars($s['url']) ?>"><i class="<?= $s['icon'] ?>"></i> <?= ucfirst($net) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>