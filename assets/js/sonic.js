/**
 * SONIC OPAC v2 — Hyperdrive Engine
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /* ===== 1. NAVBAR: scroll state + progress bar + burger ===== */
    var navbar = document.getElementById('sonicNavbar');
    var progress = document.getElementById('sonicProgress');
    var burger = document.getElementById('sonicBurger');
    var nav = document.getElementById('sonicNav');

    function onScroll() {
        var h = document.documentElement;
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);
        if (progress) {
            var max = h.scrollHeight - h.clientHeight;
            progress.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%';
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    if (burger && nav) burger.addEventListener('click', function () { nav.classList.toggle('open'); });

    /* ===== 2. HERO PHOTO SLIDER (Ken Burns + crossfade) ===== */
    var slides = document.querySelectorAll('.sonic-slide');
    if (slides.length > 0) {
        var dotsWrap = document.getElementById('sliderDots');
        var current = 0, timer = null;

        slides.forEach(function (_, i) {
            var d = document.createElement('button');
            d.type = 'button';
            d.className = 'sonic-dot-btn' + (i === 0 ? ' active' : '');
            d.addEventListener('click', function () { go(i); restart(); });
            if (dotsWrap) dotsWrap.appendChild(d);
        });
        var dots = dotsWrap ? dotsWrap.querySelectorAll('.sonic-dot-btn') : [];

        function go(i) {
            slides[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');
            current = (i + slides.length) % slides.length;
            slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');
        }
        function restart() {
            if (timer) clearInterval(timer);
            timer = setInterval(function () { go(current + 1); }, 6000);
        }

        var prev = document.getElementById('sliderPrev');
        var next = document.getElementById('sliderNext');
        if (prev) prev.addEventListener('click', function () { go(current - 1); restart(); });
        if (next) next.addEventListener('click', function () { go(current + 1); restart(); });

        var hero = document.getElementById('sonicSlider');
        if (hero) {
            hero.addEventListener('mouseenter', function () { if (timer) clearInterval(timer); });
            hero.addEventListener('mouseleave', restart);
        }
        restart();
    }

    /* ===== 3. SEARCH ENGINE: mode tags + recent searches ===== */
    var input = document.getElementById('sonic-keywords');
    var form = document.getElementById('sonicSearchForm');

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function renderRecent() {
        var box = document.getElementById('sonicRecent');
        var list = document.getElementById('sonicRecentList');
        if (!box || !list) return;
        var rec = [];
        try { rec = JSON.parse(localStorage.getItem('sonicRecent') || '[]'); } catch (e) {}
        if (!rec.length) return;
        box.style.display = 'block';
        list.innerHTML = '';
        rec.forEach(function (r) {
            var a = document.createElement('a');
            a.className = 'sonic-tag';
            a.href = 'index.php?' + encodeURIComponent(r.mode) + '=' + encodeURIComponent(r.text) + '&search=search';
            a.innerHTML = '<i class="fas fa-history"></i>' + escapeHtml(r.text);
            list.appendChild(a);
        });
    }

    if (input && form) {
        var modeBtns = document.querySelectorAll('[data-search-mode]');
        modeBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                modeBtns.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                input.name = btn.getAttribute('data-search-mode');
                if (input.value.trim() !== '') {
                    form.submit();          // LANGSUNG CARI!
                } else {
                    input.focus();
                }
            });
        });
        form.addEventListener('submit', function () {
            var v = input.value.trim();
            if (!v) return;
            var rec = [];
            try { rec = JSON.parse(localStorage.getItem('sonicRecent') || '[]'); } catch (e) {}
            rec = rec.filter(function (r) { return r.text !== v; });
            rec.unshift({ mode: input.name, text: v });
            localStorage.setItem('sonicRecent', JSON.stringify(rec.slice(0, 6)));
        });
        renderRecent();
    }

    /* ===== 4. ADVANCED SEARCH BUTTON ===== */
    var advBtn = document.getElementById('sonicAdvBtn');
    if (advBtn) advBtn.addEventListener('click', function () {
        var m = document.getElementById('adv-modal');
        if (m) m.classList.add('active');
    });

    /* ===== 5. TITLE SCRAMBLE-DECODE EFFECT ===== */
    var title = document.getElementById('sonicTitle');
    if (title) {
        var finalText = title.getAttribute('data-text') || title.textContent;
        var glyphs = '#01<>[]{}=+*^?/\\_—';
        var frame = 0;
        var iv = setInterval(function () {
            frame++;
            var revealed = Math.floor(frame / 2);
            var out = '';
            for (var i = 0; i < finalText.length; i++) {
                if (i < revealed || finalText[i] === ' ') out += finalText[i];
                else out += glyphs[Math.floor(Math.random() * glyphs.length)];
            }
            title.textContent = out;
            if (revealed >= finalText.length) { clearInterval(iv); title.textContent = finalText; }
        }, 40);
    }

    /* ===== 6. SPOTLIGHT CARDS ===== */
    document.querySelectorAll('.sonic-book, .sonic-list-card').forEach(function (card) {
        card.addEventListener('mousemove', function (e) {
            var r = card.getBoundingClientRect();
            card.style.setProperty('--mouse-x', ((e.clientX - r.left) / r.width) * 100 + '%');
            card.style.setProperty('--mouse-y', ((e.clientY - r.top) / r.height) * 100 + '%');
        });
    });

    /* ===== 7. MODALS ===== */
    window.closeCitationModal = function () {
        document.querySelectorAll('.sonic-modal, .sonic-adv-modal').forEach(function (m) { m.classList.remove('active'); });
    };
    document.querySelectorAll('.sonic-modal, .sonic-adv-modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('active'); });
    });

    console.log('%c⚡ SONIC OPAC v2 ⚡', 'color:#00f0ff;font-size:28px;font-weight:900;text-shadow:0 0 20px #00f0ff;');
});

/* ===== SMART NAVBAR OVERFLOW (Priority+) — navbar selalu rapi satu baris ===== */
(function () {
    'use strict';
    var nav = document.getElementById('sonicNav');
    if (!nav) return;
    var moreLi = null, moreMenu = null;

    function buildMore() {
        if (moreLi) return;
        moreLi = document.createElement('li');
        moreLi.className = 'dropdown sonic-nav-more';
        moreLi.innerHTML = '<a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Menu lainnya"><i class="fas fa-ellipsis-h"></i></a><div class="dropdown-menu dropdown-menu-right"></div>';
        moreMenu = moreLi.querySelector('.dropdown-menu');
        var prot = nav.querySelector('.sonic-nav-protected');
        if (prot) nav.insertBefore(moreLi, prot); else nav.appendChild(moreLi);
    }
    function menuItems() { return Array.prototype.slice.call(nav.querySelectorAll(':scope > li.sonic-nav-item')); }
    function restoreAll() {
        if (!moreMenu) return;
        var moved = Array.prototype.slice.call(moreMenu.querySelectorAll('li.sonic-nav-item'));
        moved.sort(function (a, b) { return (+a.dataset.idx) - (+b.dataset.idx); });
        moved.forEach(function (li) { nav.insertBefore(li, moreLi); });
        moreMenu.innerHTML = '';
    }
    function layout() {
        if (window.innerWidth <= 900) { if (moreLi) { restoreAll(); moreLi.style.display = 'none'; } return; }
        buildMore();
        moreLi.style.display = '';
        restoreAll();
        menuItems().forEach(function (li, i) { li.dataset.idx = i; });
        var guard = 0;
        while (nav.scrollWidth > nav.clientWidth + 1 && guard < 60) {
            var vis = menuItems().filter(function (li) { return li.parentNode === nav; });
            if (vis.length <= 1) break;
            moreMenu.appendChild(vis[vis.length - 1]);
            guard++;
        }
        moreLi.style.display = moreMenu.children.length ? '' : 'none';
    }
    var rt = null;
    window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(layout, 120); });
    window.addEventListener('load', layout);
    if (document.readyState === 'complete') layout();
    else document.addEventListener('DOMContentLoaded', layout);
})();