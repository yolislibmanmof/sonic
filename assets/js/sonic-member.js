/**
 * SONIC MEMBER — count-up statistik + tilt holografik + cinematic member login
 * + BATCH 6: kartu digital ber-QR, target baca tahunan, wishlist→reservasi 1 klik.
 * (konsolidasi: tidak ada file JS login terpisah; semua fitur lama dipertahankan)
 */
jQuery(function ($) {
    'use strict';

    // Count-up angka statistik
    $('.sonic-holo-stat .num').each(function () {
        var $n = $(this), target = parseInt($n.data('count') || '0', 10), cur = 0;
        if (target <= 0) { $n.text('0'); return; }
        var step = Math.max(1, Math.round(target / 30));
        var iv = setInterval(function () {
            cur += step;
            if (cur >= target) { cur = target; clearInterval(iv); }
            $n.text(cur);
        }, 40);
    });

    // Tilt 3D kartu holografik
    var card = document.getElementById('sonicHoloCard');
    if (card) {
        card.addEventListener('mousemove', function (e) {
            var r = card.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - 0.5;
            var y = (e.clientY - r.top) / r.height - 0.5;
            card.style.transform = 'perspective(900px) rotateY(' + (x * 8) + 'deg) rotateX(' + (-y * 8) + 'deg)';
        });
        card.addEventListener('mouseleave', function () { card.style.transform = ''; });
    }

    /* ===== BATCH 6: WISHLIST → RESERVASI 1 KLIK (kontrak basket core) ===== */
    $(document).on('click', '.sonic-wish-reserve', function (e) {
        e.preventDefault();
        var $b = $(this); if ($b.data('busy')) return; $b.data('busy', true);
        var id = $b.attr('data-biblio');
        $.post('index.php?p=member', { biblio: [id], callback: 'json' })
            .done(function (data) {
                if (typeof data === 'string') {
                    try { data = JSON.parse(data); }
                    catch (err) { window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href); return; }
                }
                if (data && data.status) {
                    if (window.toastr) toastr.success(data.message || 'Ditambahkan ke basket');
                    // langsung ke halaman reservasi core (basket)
                    window.location = 'index.php?p=member&sec=title_basket';
                } else {
                    if (window.toastr) toastr.error((data && data.message) || 'Gagal menambahkan ke basket');
                    $b.data('busy', false);
                }
            })
            .fail(function () { if (window.toastr) toastr.error('Gagal menambahkan ke basket'); $b.data('busy', false); });
    });
});

/* ===== CINEMATIC MEMBER LOGIN (MISI 4) — hanya aktif di halaman login member ===== */
(function () {
    'use strict';
    function isMemberLoginPage() {
        var p = new URLSearchParams(window.location.search);
        return p.get('p') === 'member' && !p.get('sec') && !document.getElementById('sonicHoloCard');
    }
    document.addEventListener('DOMContentLoaded', function () {
        if (!isMemberLoginPage()) return;
        var frame = document.querySelector('.sonic-member-wrap .sonic-auth-frame');
        if (!frame) return;
        frame.classList.add('sonic-login-cinema');

        // Banner uplink
        var body = frame.querySelector('.sonic-auth-body');
        if (body) {
            var banner = document.createElement('div');
            banner.className = 'sonic-login-banner';
            banner.innerHTML = '<span class="t"><i class="fas fa-satellite-dish"></i> Member Portal // Secure Uplink</span>';
            body.insertBefore(banner, body.firstChild);
        }

        // Toggle show/hide password
        frame.querySelectorAll('input[type="password"]').forEach(function (pw) {
            if (pw.dataset.sonicPw) return;
            pw.dataset.sonicPw = '1';
            var wrap = document.createElement('span');
            wrap.className = 'sonic-pw-wrap';
            pw.parentNode.insertBefore(wrap, pw);
            wrap.appendChild(pw);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sonic-pw-toggle';
            btn.setAttribute('aria-label', 'Toggle password visibility');
            btn.innerHTML = '<i class="far fa-eye"></i>';
            wrap.appendChild(btn);
            btn.addEventListener('click', function () {
                var show = pw.type === 'password';
                pw.type = show ? 'text' : 'password';
                btn.innerHTML = show ? '<i class="far fa-eye-slash"></i>' : '<i class="far fa-eye"></i>';
                pw.focus();
            });
        });

        // Loading state saat submit
        var form = frame.querySelector('form');
        if (form) {
            form.addEventListener('submit', function () {
                var sb = form.querySelector('input[type="submit"],button[type="submit"]');
                if (sb) sb.classList.add('sonic-submit-loading');
            });
        }
    });
})();

/* ===== BATCH 6: KARTU DIGITAL ber-QR + TARGET BACA TAHUNAN ===== */
(function () {
    'use strict';

    /* Kartu digital: QR dari lib qrcode lokal (Batch 4). Fallback: tampilkan ID teks. */
    function initCardQr() {
        var card = document.getElementById('sonicHoloCard'); if (!card) return;
        var id = card.getAttribute('data-member-id'); if (!id) return;
        var name = card.getAttribute('data-member-name') || '';
        var qrCell = document.getElementById('sonicHoloQr');
        var btn = document.getElementById('sonicCardQrBtn');
        function makeQr(cellSize) {
            if (typeof window.qrcode !== 'function') return null;
            try { var qr = window.qrcode(0, 'M'); qr.addData(id); qr.make(); return qr.createSvgTag(cellSize, 1); }
            catch (e) { return null; }
        }
        if (qrCell) { var svg = makeQr(3); if (svg) qrCell.innerHTML = svg; else qrCell.textContent = id; }
        function openModal() {
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
            m.querySelector('.sonic-qr-modal-qr').innerHTML = big || ('<span class="sonic-qr-modal-fallback"></span>');
            if (!big) m.querySelector('.sonic-qr-modal-fallback').textContent = id;
            m.querySelector('.sonic-qr-modal-id').textContent = id;
            m.querySelector('.sonic-qr-modal-hint').textContent = big
                ? 'Scan QR ini di kiosk untuk check-in'
                : 'Lib QR belum dimuat (jalankan php tools/fetch_qr.php). Tunjukkan ID ini ke petugas.';
            m.classList.add('show');
        }
        if (btn) btn.addEventListener('click', openModal);
        if (qrCell) qrCell.addEventListener('click', openModal);
    }

    /* Target baca tahunan: progress dari data sirkulasi, target disimpan di perangkat (per anggota). */
    function initGoal() {
        var quest = document.getElementById('sonicReadingQuest'); if (!quest) return;
        var mid = quest.getAttribute('data-member-id') || 'guest';
        var done = parseInt(quest.getAttribute('data-year-count'), 10) || 0;
        var key = 'sonicReadingGoal_' + mid;
        var target = 12;
        try { var s = localStorage.getItem(key); if (s) { var v = parseInt(s, 10); if (v >= 1 && v <= 365) target = v; } } catch (e) {}
        var input = document.getElementById('sonicQuestInput');
        var targetEl = document.getElementById('sonicQuestTarget');
        var doneEl = document.getElementById('sonicQuestDone');
        var fill = document.getElementById('sonicQuestFill');
        function paint() {
            if (targetEl) targetEl.textContent = target;
            if (doneEl) doneEl.textContent = done;
            if (fill) fill.style.width = Math.min(100, Math.round(done / target * 100)) + '%';
            if (input) input.value = target;
        }
        paint();
        if (input) input.addEventListener('change', function () {
            var v = parseInt(input.value, 10);
            if (!(v >= 1 && v <= 365)) { input.value = target; return; }
            target = v; try { localStorage.setItem(key, String(target)); } catch (e) {}
            paint();
        });
    }

    function init() { initCardQr(); initGoal(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();