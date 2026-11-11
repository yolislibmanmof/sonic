<?php
/**
 * Visitor template (SONIC Edition — FASE 1, kompatibel SLiMS 9.8)
 * Kontrak core: POST counter=1, memberID, institution + CSRF (Volnix) → JSON {message,image,new_token}.
 */
$main_template_path = __DIR__ . '/login_template.inc.php';

$sonic_csrf_name  = class_exists('\Volnix\CSRF\CSRF') ? \Volnix\CSRF\CSRF::getTokenName() : 'csrf_token';
$sonic_csrf_token = class_exists('\Volnix\CSRF\CSRF') ? \Volnix\CSRF\CSRF::getToken() : '';
$sonic_room = isset($_GET['room']) ? trim(strip_tags($_GET['room'])) : '';
$sonic_action = 'index.php?p=visitor' . ($sonic_room !== '' ? '&room=' . urlencode($sonic_room) : '');
$sonic_voice = !empty($sysconf['visitor_log_voice']);
$sonic_lang  = str_replace('_', '-', $sysconf['default_lang'] ?? 'id-ID');
?>
<div class="sonic-container" style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;">
    <div class="sonic-terminal" style="max-width:560px;width:100%;">
        <div class="sonic-terminal-head">
            <span class="sonic-dot r"></span><span class="sonic-dot y"></span><span class="sonic-dot g"></span>
            <span class="sonic-terminal-title">visitor_log.exe</span>
        </div>
        <div class="sonic-terminal-body">
            <h3 style="color:var(--sonic-neon);text-align:center;margin-bottom:1.2rem;">⚡ <?= _('Library Visitor Log') ?></h3>

            <div id="sonicVisitorInfo" style="display:none;text-align:center;margin-bottom:1rem;">
                <img id="sonicVisitorPhoto" src="./images/persons/person.png" alt="member"
                     style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:2px solid var(--sonic-neon);box-shadow:0 0 20px rgba(0,240,255,.4);margin-bottom:.6rem;">
                <div id="sonicVisitorMsg" style="color:var(--sonic-text);line-height:1.6;"></div>
            </div>

            <form id="sonicVisitorForm" method="post" action="<?= htmlspecialchars($sonic_action) ?>">
                <input type="hidden" name="counter" value="1">
                <input type="hidden" name="<?= htmlspecialchars($sonic_csrf_name) ?>" id="sonicVisitorToken" value="<?= htmlspecialchars($sonic_csrf_token) ?>">
                <div class="sonic-adv-field">
                    <label><?= _('Member ID') ?></label>
                    <input type="text" name="memberID" id="sonicMemberID" required autocomplete="off" autofocus>
                </div>
                <div class="sonic-adv-field">
                    <label><?= _('Institution') ?></label>
                    <input type="text" name="institution" id="sonicInstitution" autocomplete="off">
                </div>
                <button type="submit" class="sonic-btn" style="width:100%;justify-content:center;">⚡ <?= _('Check In') ?></button>
            </form>

            <p id="sonicVisitorQuote" style="margin-top:1.2rem;color:var(--sonic-muted);font-style:italic;text-align:center;font-size:.85rem;"></p>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var form = document.getElementById('sonicVisitorForm');
    if (!form) return;
    var info = document.getElementById('sonicVisitorInfo');
    var msg = document.getElementById('sonicVisitorMsg');
    var photo = document.getElementById('sonicVisitorPhoto');
    var token = document.getElementById('sonicVisitorToken');
    var quotes = [
        'Buku adalah jendela dunia.', 'Membaca hari ini, memimpin esok hari.',
        'Perpustakaan adalah jantung pengetahuan.', 'Satu buku, satu petualangan.'
    ];
    var qEl = document.getElementById('sonicVisitorQuote');
    function rotateQuote() { if (qEl) qEl.textContent = '“' + quotes[Math.floor(Math.random() * quotes.length)] + '”'; }
    rotateQuote();

    function speak(text) {
        if (!<?= $sonic_voice ? 'true' : 'false' ?> || !('speechSynthesis' in window)) return;
        try {
            var u = new SpeechSynthesisUtterance(text.replace(/<[^>]+>/g, ''));
            u.lang = '<?= htmlspecialchars($sonic_lang) ?>'; u.volume = 1; u.rate = 1;
            speechSynthesis.cancel(); speechSynthesis.speak(u);
        } catch (e) {}
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var mid = document.getElementById('sonicMemberID').value.trim();
        if (mid === '') { document.getElementById('sonicMemberID').focus(); return; }
        var btn = form.querySelector('button[type="submit"]'); btn.disabled = true;
        var fd = new FormData(form);
        fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.new_token) token.value = res.new_token;
                msg.innerHTML = res.message || '';
                photo.src = './images/persons/' + (res.image || 'person.png');
                photo.onerror = function () { this.src = './images/persons/person.png'; };
                info.style.display = 'block';
                speak(res.message || '');
                form.reset();
                rotateQuote();
                setTimeout(function () { info.style.display = 'none'; document.getElementById('sonicMemberID').focus(); }, 6000);
            })
            .catch(function () { msg.textContent = 'Terjadi kesalahan. Coba lagi.'; info.style.display = 'block'; })
            .finally(function () { btn.disabled = false; document.getElementById('sonicMemberID').focus(); });
    });
})();
</script>