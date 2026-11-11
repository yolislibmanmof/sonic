<?php
/**
 * Visitor template (SONIC Edition — FASE 5.1 Kiosk Ultimate, kompatibel SLiMS 9.8)
 * Kontrak core: POST counter=1, memberID, institution + CSRF (Volnix) → JSON {message,image,new_token}.
 * Load-order safe: path aset & statistik dihitung lokal (file ini di-require sebelum classic.php).
 */
$main_template_path = __DIR__ . '/login_template.inc.php';

$sonic_csrf_name  = class_exists('\Volnix\CSRF\CSRF') ? \Volnix\CSRF\CSRF::getTokenName() : 'csrf_token';
$sonic_csrf_token = class_exists('\Volnix\CSRF\CSRF') ? \Volnix\CSRF\CSRF::getToken() : '';
$sonic_room = isset($_GET['room']) ? trim(strip_tags($_GET['room'])) : '';
$sonic_action = 'index.php?p=visitor' . ($sonic_room !== '' ? '&room=' . urlencode($sonic_room) : '');
$sonic_voice = !empty($sysconf['visitor_log_voice']);
$sonic_lang  = str_replace('_', '-', $sysconf['default_lang'] ?? 'id-ID');
$sonic_kiosk_quotes = array_values(array_filter(array_map('trim', explode('||', (string)($sysconf['template']['sonic_kiosk_quotes'] ?? '')))));

$sonic_tpl = $sysconf['template'] ?? ($GLOBALS['sysconf']['template'] ?? []);
$sonic_asset_base = ($sonic_tpl['dir'] ?? 'template') . '/' . ($sonic_tpl['theme'] ?? 'sonic') . '/assets/';
$sonic_css_ver = @filemtime(__DIR__ . '/assets/css/sonic-visitor.css') ?: 1;
$sonic_js_ver  = @filemtime(__DIR__ . '/assets/js/sonic-visitor.js') ?: 1;

/* ===== STATISTIK KUNJUNGAN (defensif, tanpa fatal) ===== */
$sonic_stats = ['today' => 0, 'week' => 0, 'month' => 0, 'year' => 0, 'total' => 0];
$sonic_last7 = [];
for ($i = 6; $i >= 0; $i--) $sonic_last7[date('Y-m-d', strtotime("-$i day"))] = 0;
if (isset($dbs) && is_object($dbs)) {
    try {
        $vt = null;
        foreach (['visitor', 'visitor_count'] as $cand) { $r = $dbs->query("SHOW TABLES LIKE '" . $cand . "'"); if ($r && $r->num_rows) { $vt = $cand; break; } }
        if ($vt) {
            $vc = null; $cr = $dbs->query("SHOW COLUMNS FROM `" . $vt . "`");
            if ($cr) { while ($c = $cr->fetch_assoc()) { $f = strtolower($c['Field']); $t = strtolower($c['Type']); if (in_array($f, ['checkin_date','visitor_date','date','input_date','created_at']) || strpos($t,'date')===0 || strpos($t,'timestamp')===0) { $vc = $c['Field']; break; } } }
            if ($vc) {
                $one = function ($sql) use ($dbs) { $r = $dbs->query($sql); return $r ? (int)($r->fetch_row()[0] ?? 0) : 0; };
                $sonic_stats['today'] = $one("SELECT COUNT(*) FROM `$vt` WHERE DATE(`$vc`)=CURDATE()");
                $sonic_stats['week']  = $one("SELECT COUNT(*) FROM `$vt` WHERE YEARWEEK(`$vc`,1)=YEARWEEK(CURDATE(),1)");
                $sonic_stats['month'] = $one("SELECT COUNT(*) FROM `$vt` WHERE DATE_FORMAT(`$vc`,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
                $sonic_stats['year']  = $one("SELECT COUNT(*) FROM `$vt` WHERE YEAR(`$vc`)=YEAR(CURDATE())");
                $sonic_stats['total'] = $one("SELECT COUNT(*) FROM `$vt`");
                $r = $dbs->query("SELECT DATE(`$vc`) d, COUNT(*) c FROM `$vt` WHERE `$vc` >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(`$vc`)");
                if ($r) while ($row = $r->fetch_row()) { if (isset($sonic_last7[$row[0]])) $sonic_last7[$row[0]] = (int)$row[1]; }
            }
        }
    } catch (Throwable $e) {}
}
$sonic_last7_max = max(1, max($sonic_last7));
$sonic_stat_cards = [
    ['today', _('Today'),        'fa-calendar-day'],
    ['week',  _('This Week'),    'fa-calendar-week'],
    ['month', _('This Month'),   'fa-calendar-alt'],
    ['year',  _('This Year'),    'fa-calendar-check'],
    ['total', _('Total'),        'fa-database'],
];
?>
<link rel="stylesheet" href="<?= $sonic_asset_base ?>css/sonic-visitor.css?v=<?= $sonic_css_ver ?>">

<div class="sonic-kiosk" id="sonicKiosk">
    <canvas id="kioskFx" aria-hidden="true"></canvas>

    <!-- ATTRACT / IDLE -->
    <div class="sonic-kiosk-attract">
        <div class="sonic-kiosk-brand"><?= htmlspecialchars($sysconf['library_name'] ?? 'Perpustakaan') ?>
            <small><?= htmlspecialchars($sysconf['template']['sonic_library_subname'] ?? '') ?></small>
        </div>
        <div class="sonic-kiosk-clock" id="kioskClock">00:00:00</div>
        <div class="sonic-kiosk-date" id="kioskDate"></div>
        <div class="sonic-kiosk-marquee"><span id="kioskMarquee"></span></div>
        <div class="sonic-kiosk-quote" id="kioskQuote"></div>
    </div>

    <!-- STATISTIK KUNJUNGAN -->
    <div class="sonic-kiosk-stats">
        <?php foreach ($sonic_stat_cards as $sc): ?>
        <div class="sonic-kstat">
            <i class="fas <?= $sc[2] ?>"></i>
            <span class="sonic-kstat-num" id="kstat-<?= $sc[0] ?>" data-count="<?= (int)$sonic_stats[$sc[0]] ?>">0</span>
            <span class="sonic-kstat-label"><?= htmlspecialchars($sc[1]) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="sonic-kspark" title="<?= _('Last 7 days') ?>">
        <?php foreach ($sonic_last7 as $d => $n): $h = max(8, (int)round($n / $sonic_last7_max * 100)); ?>
        <div class="sonic-kspark-bar" style="--h:<?= $h ?>%" title="<?= htmlspecialchars($d) ?>: <?= (int)$n ?>"><span></span><em><?= (int)substr($d, 8, 2) ?></em></div>
        <?php endforeach; ?>
    </div>

    <!-- CHECK-IN -->
    <div class="sonic-terminal sonic-kiosk-card">
        <div class="sonic-terminal-head">
            <span class="sonic-dot r"></span><span class="sonic-dot y"></span><span class="sonic-dot g"></span>
            <span class="sonic-terminal-title">visitor_log.exe</span>
        </div>
        <div class="sonic-terminal-body">
            <h3 style="color:var(--sonic-neon);text-align:center;margin-bottom:1.2rem;">⚡ <?= _('Library Visitor Log') ?></h3>
            <form id="sonicVisitorForm" method="post" action="<?= htmlspecialchars($sonic_action) ?>">
                <input type="hidden" name="counter" value="1">
                <input type="hidden" name="<?= htmlspecialchars($sonic_csrf_name) ?>" id="sonicVisitorToken" value="<?= htmlspecialchars($sonic_csrf_token) ?>">
                <div class="sonic-adv-field">
                    <label><?= _('Member ID') ?></label>
                    <input type="text" name="memberID" id="sonicMemberID" required autocomplete="off" autofocus inputmode="text">
                </div>
                <div class="sonic-adv-field">
                    <label><?= _('Institution') ?></label>
                    <input type="text" name="institution" id="sonicInstitution" autocomplete="off">
                </div>
                <button type="submit" class="sonic-btn" style="width:100%;justify-content:center;">⚡ <?= _('Check In') ?></button>
            </form>
            <p class="sonic-kiosk-hint"><i class="fas fa-keyboard"></i> <?= _('Scan / ketik ID anggota lalu Enter') ?></p>
        </div>
    </div>

    <!-- SUCCESS -->
    <div class="sonic-kiosk-success" id="kioskSuccess">
        <canvas id="kioskConfetti" aria-hidden="true"></canvas>
        <img id="kioskPhoto" src="./images/persons/person.png" alt="member">
        <div class="msg" id="kioskMsg"></div>
        <div class="sonic-kiosk-success-bar"><span></span></div>
    </div>
</div>

<script>
window.SONIC_VISITOR_CFG = <?= json_encode([
    'voice'   => (bool)$sonic_voice,
    'lang'    => $sonic_lang,
    'idle'    => (int)($sysconf['template']['sonic_kiosk_idle'] ?? 20),
    'action'  => $sonic_action,
    'welcome' => (string)($sysconf['template']['sonic_welcome_text'] ?? 'SELAMAT DATANG DI LAYANAN OPAC PERPUSTAKAAN'),
    'quotes'  => $sonic_kiosk_quotes,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
</script>
<script src="<?= $sonic_asset_base ?>js/sonic-visitor.js?v=<?= $sonic_js_ver ?>"></script>