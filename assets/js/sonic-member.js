/**
 * SONIC MEMBER — count-up statistik + tilt holografik + cinematic member login
 * (konsolidasi: tidak ada file JS login terpisah)
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