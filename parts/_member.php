<?php
# @Filename: _member.php (SONIC v3 — Holographic Auth)
include '_navbar.php';
if (!$is_login) {
    $sonic_compact_hero = true;
    include '_search-form.php';
}

$sonic_member = ['name' => '', 'type' => '', 'expire' => '', 'image' => 'person.png', 'loan' => 0, 'history' => 0, 'basket' => count($_SESSION['m_mark_biblio'] ?? [])];
if ($is_login) {
    $sonic_member['name']  = $_SESSION['m_name'] ?? '';
    $sonic_member['image'] = $_SESSION['m_image'] ?? 'person.png';
    $sonic_member['type']  = $_SESSION['m_member_type'] ?? ($_SESSION['m_type'] ?? '');
    try {
        $m_id = $_SESSION['m_id'] ?? ($_SESSION['member_id'] ?? null);
        if ($m_id && isset($dbs)) {
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

// Deteksi mode halaman
$sonic_is_login_page = !$is_login && (empty($_GET['sec']) || $_GET['sec'] === '');
$sonic_is_register   = isset($_GET['sec']) && $_GET['sec'] === 'register';
$sonic_page_title    = $sonic_is_login_page ? __('Member Login')
                         : ($sonic_is_register ? __('Member Registration')
                         : __('Member Area'));
$sonic_page_icon     = $sonic_is_login_page ? 'fa-user-astronaut'
                         : ($sonic_is_register ? 'fa-user-plus' : 'fa-id-card');
?>

<div class="sonic-member-wrap">

    <?php if ($is_login): ?>
    <!-- ===== HOLOGRAPHIC MEMBER ID ===== -->
    <div class="sonic-holo-card" id="sonicHoloCard">
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
            </div>
            <div class="sonic-holo-stats">
                <div class="sonic-holo-stat"><span class="num" data-count="<?= (int)$sonic_member['loan'] ?>">0</span><label><?= __('Borrowed') ?></label></div>
                <div class="sonic-holo-stat"><span class="num" data-count="<?= (int)$sonic_member['history'] ?>">0</span><label><?= __('History') ?></label></div>
                <div class="sonic-holo-stat"><span class="num" data-count="<?= (int)$sonic_member['basket'] ?>">0</span><label><?= __('Basket') ?></label></div>
            </div>
        </div>
    </div>
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
                    <?php elseif ($sonic_is_register): ?>
                        <?= __('Create your sonic account') ?>
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
        </div>
    </div>

</div>