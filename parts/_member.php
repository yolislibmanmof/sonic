<?php
# @Filename: _member.php (SONIC v3 — Holographic Auth + BATCH 6: Kartu Digital ber-QR, INLINE anti-cache)
// BATCH 6 FIX: saat logout, core sudah menghancurkan sesi.
// Paksa tampilan logged-out supaya tidak merender kartu/dashboard dengan sesi mati (penyebab 500).
if (isset($_GET['logout']) && $_GET['logout'] == '1') { $is_login = false; }
include '_navbar.php';
if (!$is_login) {
    $sonic_compact_hero = true;
    include '_search-form.php';
}

$sonic_member = ['id' => '', 'name' => '', 'type' => '', 'expire' => '', 'image' => 'person.png', 'loan' => 0, 'history' => 0, 'basket' => count($_SESSION['m_mark_biblio'] ?? [])];
if ($is_login) {
    $sonic_member['name']  = $_SESSION['m_name'] ?? '';
    $sonic_member['image'] = $_SESSION['m_image'] ?? 'person.png';
    $sonic_member['type']  = $_SESSION['m_member_type'] ?? ($_SESSION['m_type'] ?? '');
    try {
        $m_id = $_SESSION['mid'] ?? ($_SESSION['m_id'] ?? ($_SESSION['member_id'] ?? null));
        if ($m_id && isset($dbs)) {
            $sonic_member['id'] = $m_id;
            $mid = $dbs->escape_string($m_id);
            $q = $dbs->query("SELECT m.member_name, m.expire_date, m.member_image, mt.member_type_name
                               FROM member AS m
                               LEFT JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
                               WHERE m.member_id='{$mid}'");
            if ($r = $q->fetch_assoc()) {
                $sonic_member['name']   = $r['member_name'] ?: $sonic_member['name'];
                $sonic_member['expire'] = $r['expire_date'] ?? '';
                $sonic_member['image']  = $r['member_image'] ?: $sonic_member['image'];
                $sonic_member['type']   = $r['member_type_name'] ?: $sonic_member['type'];
            }
            $q2 = $dbs->query("SELECT COUNT(*) FROM loan AS l INNER JOIN member AS m ON l.member_id=m.member_id
                                WHERE m.member_id='{$mid}' AND l.is_lent=1 AND l.is_return=0");
            $sonic_member['loan'] = (int)($q2->fetch_row()[0] ?? 0);
            $q3 = $dbs->query("SELECT COUNT(*) FROM loan AS l INNER JOIN member AS m ON l.member_id=m.member_id
                                WHERE m.member_id='{$mid}'");
            $sonic_member['history'] = (int)($q3->fetch_row()[0] ?? 0);
        }
    } catch (Throwable $e) {}
}

$sonic_is_login_page = !$is_login && (empty($_GET['sec']) || $_GET['sec'] === '');
$sonic_page_title    = $sonic_is_login_page ? __('Member Login') : __('Member Area');
$sonic_page_icon     = $sonic_is_login_page ? 'fa-user-astronaut' : 'fa-id-card';
$sonic_card_qr_on    = (int)($sysconf['template']['sonic_member_card_qr'] ?? 1) === 1;
?>

<?php if ($is_login && $sonic_card_qr_on): ?>
<!-- BATCH 6 INLINE: CSS kartu QR (tidak lewat bundle → anti-cache) -->
<style>
.sonic-holo-glare,.sonic-holo-scan{pointer-events:none!important}
.sonic-holo-qr{position:relative!important;top:auto!important;right:auto!important;z-index:2;display:inline-flex;width:76px;height:76px;margin-top:.6rem;color:#000;background:#fff;border-radius:10px;padding:5px;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 0 18px rgba(0,240,255,.3);pointer-events:auto;vertical-align:top;font-family:'Orbitron',sans-serif;font-size:.8rem;overflow:hidden}
.sonic-holo-qr svg{width:100%;height:100%}
.sonic-holo-actions{position:relative;z-index:6}
.sonic-qr-modal{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;background:rgba(3,5,10,.88);backdrop-filter:blur(10px);padding:1.5rem}
.sonic-qr-modal.show{display:flex}
.sonic-qr-modal-card{position:relative;background:#0a0e1a;border:1px solid #00f0ff;border-radius:18px;padding:2rem;text-align:center;max-width:340px;width:100%;box-shadow:0 0 50px rgba(0,240,255,.3)}
.sonic-qr-modal-close{position:absolute;top:.6rem;right:.8rem;background:none;border:none;color:#8899aa;font-size:1.6rem;cursor:pointer;line-height:1}
.sonic-qr-modal-title{color:#fff;font-family:'Orbitron',sans-serif;font-weight:700;margin-bottom:1rem}
.sonic-qr-modal-qr{background:#fff;border-radius:12px;padding:12px;display:inline-block;color:#000}
.sonic-qr-modal-qr svg{width:220px;height:220px}
.sonic-qr-modal-fallback{font-family:'Orbitron',sans-serif;font-size:1.4rem;color:#000}
.sonic-qr-modal-id{color:#00f0ff;font-family:'Orbitron',sans-serif;font-size:1.1rem;margin-top:1rem;letter-spacing:1px}
.sonic-qr-modal-hint{color:#8899aa;font-size:.75rem;margin-top:.5rem}
</style>
<?php endif; ?>

<div class="sonic-member-wrap">

    <?php if ($is_login): ?>
    <!-- ===== HOLOGRAPHIC MEMBER ID ===== -->
    <div class="sonic-holo-card" id="sonicHoloCard" data-member-id="<?= htmlspecialchars($sonic_member['id']) ?>" data-member-name="<?= htmlspecialchars($sonic_member['name']) ?>">
        <div class="sonic-holo-glare"></div>
        <div class="sonic-holo-scan"></div>
        <div class="sonic-holo-grid">
            <div class="sonic-holo-avatar">
                <img src="./lib/minigalnano/createthumb.php?filename=<?= urlencode('images/persons/' . $sonic_member['image']) ?>&width=160"
                     onerror="this.src='./images/persons/person.png'" alt="member">
            </div>
            <div class="sonic-holo-id">
                <div class="sonic-holo-label"><?= __('Member ID') ?> // SONIC ACCESS CARD</div>
                <div class="sonic-holo-name"><?= htmlspecialchars($sonic_member['name']) ?></div>
                <div class="sonic-holo-type"><i class="fas fa-id-badge"></i> <?= htmlspecialchars($sonic_member['type'] ?: '-') ?></div>
                <div class="sonic-holo-expire"><i class="far fa-calendar-alt"></i> <?= __('Expired') ?>: <?= htmlspecialchars($sonic_member['expire'] ?: '-') ?></div>
                <?php if ($sonic_card_qr_on && $sonic_member['id'] !== ''): ?>
                <div class="sonic-holo-mid"><i class="fas fa-barcode"></i> <?= htmlspecialchars($sonic_member['id']) ?></div>
                <!-- QR in-flow di kolom identitas → tidak menimpa statistik BASKET -->
                <div class="sonic-holo-qr" id="sonicHoloQr" title="<?= __('QR kartu anggota — klik untuk perbesar') ?>"></div>
                <?php endif; ?>
            </div>
            <div class="sonic-holo-stats">
                <div class="sonic-holo-stat"><span class="num" data-count="<?= (int)$sonic_member['loan'] ?>">0</span><label><?= __('Borrowed') ?></label></div>
                <div class="sonic-holo-stat"><span class="num" data-count="<?= (int)$sonic_member['history'] ?>">0</span><label><?= __('History') ?></label></div>
                <div class="sonic-holo-stat"><span class="num" data-count="<?= (int)$sonic_member['basket'] ?>">0</span><label><?= __('Basket') ?></label></div>
            </div>
        </div>
        <?php if ($sonic_card_qr_on && $sonic_member['id'] !== ''): ?>
        <div class="sonic-holo-actions">
            <button type="button" class="sonic-mini-btn" id="sonicCardQrBtn"><i class="fas fa-qrcode"></i> <?= __('Kartu Digital / QR') ?></button>
        </div>
        <?php endif; ?>
    </div>
    <?php include __DIR__ . '/_member-dashboard.php'; ?>
    <?php endif; ?>

    <!-- ===== HOLOGRAPHIC AUTH FRAME ===== -->
    <div class="sonic-auth-frame">
        <div class="sonic-auth-corner tl"></div>
        <div class="sonic-auth-corner tr"></div>
        <div class="sonic-auth-corner bl"></div>
        <div class="sonic-auth-corner br"></div>
        <div class="sonic-auth-scanline"></div>
        <div class="sonic-auth-header">
            <div class="sonic-auth-icon"><i class="fas <?= $sonic_page_icon ?>"></i></div>
            <div>
                <h2 class="sonic-auth-title"><?= $sonic_page_title ?></h2>
                <p class="sonic-auth-sub">
                    <?php if ($sonic_is_login_page): ?>
                        <?= __('Secure access to your digital library') ?>
                    <?php else: ?>
                        <?= __('Manage your library profile') ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="sonic-auth-status">
                <span class="sonic-status-dot"></span>
                <span><?= $is_login ? __('Online') : __('Secure Channel') ?></span>
            </div>
        </div>
        <div class="sonic-auth-body">
            <?php echo $main_content; ?>
            <?php if ($sonic_is_login_page): ?>
            <div style="margin-top:1rem;text-align:center;">
                <a href="index.php?p=forgot" class="sonic-mini-btn" style="justify-content:center;"><i class="fas fa-key"></i> <?= __('Forgot password?') ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php if ($is_login && $sonic_card_qr_on && $sonic_member['id'] !== ''): ?>
<!-- BATCH 6 INLINE: JS kartu QR (tidak lewat bundle → anti-cache). Jalan setelah lib qrcode (defer) siap. -->
<script>
(function () {
    function init() {
        var card = document.getElementById('sonicHoloCard'); if (!card) return;
        var id = card.getAttribute('data-member-id'); if (!id) return;
        var name = card.getAttribute('data-member-name') || '';
        var cell = document.getElementById('sonicHoloQr');
        var btn = document.getElementById('sonicCardQrBtn');
        function makeQr(cs) {
            if (typeof window.qrcode !== 'function') return null;
            try { var q = window.qrcode(0, 'M'); q.addData(id); q.make(); return q.createSvgTag(cs, 1); }
            catch (e) { return null; }
        }
        if (cell) { var s = makeQr(3); if (s) { cell.innerHTML = s; } else { cell.textContent = id; } }
        function open() {
            var m = document.getElementById('sonicCardQrModal');
            if (!m) {
                m = document.createElement('div'); m.id = 'sonicCardQrModal'; m.className = 'sonic-qr-modal';
                m.innerHTML = '<div class="sonic-qr-modal-card"><button type="button" class="sonic-qr-modal-close" data-close>&times;</button>' +
                    '<div class="sonic-qr-modal-title"></div><div class="sonic-qr-modal-qr"></div>' +
                    '<div class="sonic-qr-modal-id"></div><div class="sonic-qr-modal-hint"></div></div>';
                document.body.appendChild(m);
                m.addEventListener('click', function (e) { if (e.target === m || e.target.hasAttribute('data-close')) m.classList.remove('show'); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape') m.classList.remove('show'); });
            }
            m.querySelector('.sonic-qr-modal-title').textContent = name || 'Kartu Anggota';
            var big = makeQr(6);
            var qrBox = m.querySelector('.sonic-qr-modal-qr');
            if (big) { qrBox.innerHTML = big; }
            else { qrBox.innerHTML = '<span class="sonic-qr-modal-fallback"></span>'; qrBox.querySelector('.sonic-qr-modal-fallback').textContent = id; }
            m.querySelector('.sonic-qr-modal-id').textContent = id;
            m.querySelector('.sonic-qr-modal-hint').textContent = big
                ? 'Scan QR ini di kiosk untuk check-in'
                : 'Lib QR belum dimuat. Jalankan: php tools/fetch_qr.php lalu rebuild bundle. Tunjukkan ID ini ke petugas.';
            m.classList.add('show');
        }
        if (btn) btn.addEventListener('click', open);
        if (cell) cell.addEventListener('click', open);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
<?php endif; ?>