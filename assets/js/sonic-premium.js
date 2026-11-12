/**
 * SONIC PREMIUM — Batch 3 FINAL (additif, tanpa dependensi baru)
 * 1) Terakhir Dilihat (client-side) — rail snap + navigasi + tanggal lokal
 * 2) Mungkin maksud Anda… (did-you-mean via api/suggest.php saat hasil kosong)
 * Tidak mengubah fitur lama; semua di-guard try/catch.
 */
(function () {
    'use strict';
    var LS = 'sonicViewed';
    var LANG = (window.SONIC_CFG && window.SONIC_CFG.lang) || (document.documentElement.lang || 'en');
    var IS_ID = /^id/i.test(String(LANG));
    var DYM_LABEL = IS_ID ? 'Mungkin maksud Anda' : 'Did you mean';

    function base() {
        if (window.SONIC_CFG && window.SONIC_CFG.base) return window.SONIC_CFG.base;
        var l = document.querySelector('link[href*="sonic-base.css"]');
        return l ? l.href.replace(/assets\/css\/sonic-base\.css.*$/, '') : 'template/sonic/';
    }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function read() { try { var a = JSON.parse(localStorage.getItem(LS) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function write(a) { try { localStorage.setItem(LS, JSON.stringify(a.slice(0, 12))); } catch (e) {} }
    function fmtDate(ts) {
        try { return new Date(ts || Date.now()).toLocaleDateString(IS_ID ? 'id-ID' : 'en-US', { day: '2-digit', month: 'short', year: 'numeric' }); }
        catch (e) { return ''; }
    }

    /* ===== 1. Catat buku yang dilihat (halaman detail) ===== */
    function recordView() {
        var root = document.querySelector('.sonic-detail-revolution'); if (!root) return;
        var id = root.getAttribute('data-biblio-id'); if (!id) return;
        var tEl = document.querySelector('.sonic-detail-title');
        var img = document.querySelector('.sonic-book-face.front img');
        var a = read().filter(function (x) { return String(x.id) !== String(id); });
        a.unshift({
            id: id,
            title: tEl ? tEl.textContent.trim() : '',
            cover: img ? img.getAttribute('src') : '',
            url: location.href.split('#')[0],
            ts: Date.now()
        });
        write(a);
    }

    /* ===== 1b. Rak "Terakhir Dilihat" di home ===== */
    function renderViewed() {
        var sec = document.getElementById('sonicViewedSection');
        var grid = document.getElementById('sonicViewedGrid');
        if (!sec || !grid) return;
        var a = read();
        if (!a.length) { sec.style.display = 'none'; return; }
        grid.innerHTML = '';
        a.slice(0, 8).forEach(function (it) {
            var el = document.createElement('a');
            el.className = 'sonic-book';
            el.href = it.url || ('index.php?p=show_detail&id=' + encodeURIComponent(it.id));
            var cover = it.cover || './images/default/image.png';
            el.innerHTML = '<div class="sonic-book-cover"><img loading="lazy" src="' + esc(cover) + '" alt="' + esc(it.title) + '" onerror="this.src=\'./images/default/image.png\'"></div>' +
                '<h4 class="sonic-book-title">' + esc(it.title) + '</h4>' +
                '<p class="sonic-book-author"><i class="fas fa-history"></i> ' + esc(fmtDate(it.ts)) + '</p>';
            grid.appendChild(el);
        });
        sec.style.display = '';

        var clr = document.getElementById('sonicViewedClear');
        if (clr) clr.addEventListener('click', function () {
            try { localStorage.removeItem(LS); } catch (e) {}
            grid.innerHTML = ''; sec.style.display = 'none';
        });

        /* Navigasi rail (hanya muncul bila memang bisa discroll) */
        var sub = sec.querySelector('.sonic-section-sub');
        if (sub && !sub.querySelector('.sonic-viewed-nav')) {
            var nav = document.createElement('span'); nav.className = 'sonic-viewed-nav';
            function mk(icon, label) {
                var b = document.createElement('button');
                b.type = 'button'; b.className = 'sonic-mini-btn'; b.setAttribute('aria-label', label);
                b.innerHTML = '<i class="fas ' + icon + '"></i>';
                return b;
            }
            var prev = mk('fa-chevron-left', IS_ID ? 'Geser kiri' : 'Scroll left');
            var next = mk('fa-chevron-right', IS_ID ? 'Geser kanan' : 'Scroll right');
            prev.addEventListener('click', function () { grid.scrollBy({ left: -240, behavior: 'smooth' }); });
            next.addEventListener('click', function () { grid.scrollBy({ left: 240, behavior: 'smooth' }); });
            nav.appendChild(prev); nav.appendChild(next); sub.appendChild(nav);
            function upd() {
                var max = grid.scrollWidth - grid.clientWidth;
                nav.style.display = max > 4 ? 'inline-flex' : 'none';
            }
            grid.addEventListener('scroll', upd, { passive: true });
            window.addEventListener('resize', upd);
            upd();
        }
    }

    /* ===== 2. Did-you-mean saat hasil kosong ===== */
    function didYouMean() {
        var main = document.querySelector('.sonic-result-main'); if (!main) return;
        var kw = (new URLSearchParams(location.search).get('keywords') || '').trim();
        if (!kw) return;
        var hasCards = main.querySelector('.sonic-book, .sonic-list-card');
        var empty = main.querySelector('.sonic-empty');
        if (hasCards && !empty) return;
        if (document.getElementById('sonicDYM')) return;
        fetch(base() + 'api/suggest.php?q=' + encodeURIComponent(kw), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var items = Array.isArray(data) ? data : [];
                var seen = {}, sugg = [];
                items.forEach(function (it) {
                    if (it.t !== 'title') return;
                    var v = (it.v || '').trim(), k = v.toLowerCase();
                    if (!v || k === kw.toLowerCase() || seen[k]) return;
                    seen[k] = 1; sugg.push(v);
                });
                if (!sugg.length) return;
                var box = document.createElement('div');
                box.id = 'sonicDYM'; box.className = 'sonic-dym';
                var html = '<span class="sonic-dym-label"><i class="fas fa-lightbulb"></i> ' + esc(DYM_LABEL) + ': </span>';
                sugg.slice(0, 5).forEach(function (v) {
                    html += '<a class="sonic-tag sonic-dym-chip" href="index.php?keywords=' + encodeURIComponent(v) + '&search=search">' + esc(v) + '</a>';
                });
                box.innerHTML = html;
                main.insertBefore(box, main.firstChild);
            })
            .catch(function () {});
    }

    function init() { recordView(); renderViewed(); didYouMean(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();