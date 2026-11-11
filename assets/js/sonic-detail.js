/**
 * SONIC OPAC — Detail Record Revolution (clean single-module, rAF-throttled)
 * 3D Book, Tabs (ARIA+keyboard), Citations + Export, Cover instant-load, Micro-interactions.
 */
(function () {
    'use strict';
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hoverCapable = window.matchMedia && window.matchMedia('(hover: hover)').matches;

    function $(s, c) { return (c || document).querySelector(s); }
    function $all(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
    function raf(fn) { var t = false, args; return function () { args = arguments; if (!t) { t = true; requestAnimationFrame(function () { t = false; fn.apply(null, args); }); } }; }

    /* ===== Unified toast + clipboard ===== */
    var toastEl = $('#copyToast'), toastText = $('#copyToastText'), toastTimer = null;
    function toast(msg) {
        if (toastEl && toastText) {
            toastText.textContent = msg; toastEl.classList.add('show');
            clearTimeout(toastTimer); toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2500);
        } else if (window.toastr) { toastr.success(msg); }
    }
    function copyText(text) {
        function fallback() {
            var ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); toast('Teks disalin ke clipboard!'); } catch (e) {}
            document.body.removeChild(ta);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { toast('Teks disalin ke clipboard!'); }, fallback);
        } else { fallback(); }
    }

    /* ===== 1. COVER INSTANT-LOAD ===== */
    function enhanceCover() {
        var book = $('#sonicBook3D'); if (!book) return;
        var img = book.querySelector('img'); if (!img || img.dataset.sonicCover) return;
        img.dataset.sonicCover = '1';
        img.classList.add('sonic-cover-img');
        img.setAttribute('loading', 'eager'); img.setAttribute('decoding', 'async'); img.setAttribute('fetchpriority', 'high');
        var sk = document.createElement('div'); sk.className = 'sonic-cover-skeleton'; sk.innerHTML = '<i class="fas fa-book"></i>';
        book.appendChild(sk);
        var done = false;
        function ready() { if (done) return; done = true; img.classList.add('ready'); sk.classList.add('hide'); setTimeout(function () { if (sk.parentNode) sk.parentNode.removeChild(sk); }, 500); }
        if (img.complete && img.naturalWidth > 0) { ready(); }
        else {
            img.addEventListener('load', ready, { once: true });
            img.addEventListener('error', function () { if (!/image\.png/.test(img.src)) img.src = './images/default/image.png'; ready(); }, { once: true });
            setTimeout(ready, 6000);
        }
    }

    /* ===== 2. 3D BOOK (pointer + rAF) ===== */
    function initBook() {
        var book = $('#sonicBook3D'); if (!book) return;
        var inner = $('.sonic-book-3d-inner', book); if (!inner) return;
        var dragging = false, sx = 0, sy = 0, cx = -20, cy = 5, rect = null;
        var write = raf(function (t) { inner.style.transform = t; });
        function getRect() { if (!rect) rect = book.getBoundingClientRect(); return rect; }
        window.addEventListener('resize', function () { rect = null; }, { passive: true });
        window.addEventListener('scroll', function () { rect = null; }, { passive: true });

        book.addEventListener('pointerenter', function () { rect = book.getBoundingClientRect(); });
        book.addEventListener('pointermove', function (e) {
            if (dragging) {
                cx = clamp(cx + (e.clientX - sx) * 0.3, -60, 60);
                cy = clamp(cy - (e.clientY - sy) * 0.3, -30, 30);
                sx = e.clientX; sy = e.clientY;
                write('rotateY(' + cx + 'deg) rotateX(' + cy + 'deg)');
            } else if (hoverCapable && !reduced) {
                var r = getRect();
                var x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
                write('rotateY(' + (x * 40 - 20) + 'deg) rotateX(' + (-y * 30 + 5) + 'deg)');
            }
        });
        book.addEventListener('pointerdown', function (e) { dragging = true; sx = e.clientX; sy = e.clientY; book.classList.add('grabbing'); if (book.setPointerCapture) book.setPointerCapture(e.pointerId); });
        function endDrag(e) { dragging = false; book.classList.remove('grabbing'); if (book.releasePointerCapture && e.pointerId != null) { try { book.releasePointerCapture(e.pointerId); } catch (err) {} } }
        book.addEventListener('pointerup', endDrag);
        book.addEventListener('pointercancel', endDrag);
        book.addEventListener('pointerleave', function () { if (!dragging) write('rotateY(-20deg) rotateX(5deg)'); });
    }

    /* ===== 3. TABS (ARIA + keyboard + hash) ===== */
    function initTabs() {
        var list = $('.sonic-detail-tabs'); if (!list) return;
        var tabs = $all('.sonic-tab', list), panels = $all('.sonic-tab-panel');
        list.setAttribute('role', 'tablist');
        tabs.forEach(function (tab, i) {
            var target = tab.getAttribute('data-tab');
            tab.setAttribute('role', 'tab'); tab.id = 'sonic-tab-' + target;
            tab.setAttribute('aria-controls', 'tab-' + target);
            tab.setAttribute('aria-selected', tab.classList.contains('active') ? 'true' : 'false');
            tab.tabIndex = tab.classList.contains('active') ? 0 : -1;
            var panel = $('#tab-' + target);
            if (panel) { panel.setAttribute('role', 'tabpanel'); panel.setAttribute('aria-labelledby', tab.id); }
            tab.addEventListener('click', function () { activate(tab); });
            tab.addEventListener('keydown', function (e) {
                var idx = tabs.indexOf(tab), n = null;
                if (e.key === 'ArrowRight') n = (idx + 1) % tabs.length;
                else if (e.key === 'ArrowLeft') n = (idx - 1 + tabs.length) % tabs.length;
                else if (e.key === 'Home') n = 0;
                else if (e.key === 'End') n = tabs.length - 1;
                if (n !== null) { e.preventDefault(); tabs[n].focus(); activate(tabs[n]); }
            });
        });
        function activate(tab) {
            var target = tab.getAttribute('data-tab');
            tabs.forEach(function (t) { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); t.tabIndex = -1; });
            panels.forEach(function (p) { p.classList.remove('active'); });
            tab.classList.add('active'); tab.setAttribute('aria-selected', 'true'); tab.tabIndex = 0;
            var panel = $('#tab-' + target); if (panel) panel.classList.add('active');
        }
        function applyHash() {
            var h = (window.location.hash || '').replace('#', '');
            var map = { attachment: 'digital', comment: 'comments' };
            if (map[h]) { var btn = $('.sonic-tab[data-tab="' + map[h] + '"]'); if (btn) activate(btn); }
        }
        window.addEventListener('load', applyHash);
        window.addEventListener('hashchange', applyHash);
    }

    /* ===== 4. CITATION COPY + EXPORT ===== */
    function initCitations() {
        document.addEventListener('click', function (e) {
            var copyBtn = e.target.closest ? e.target.closest('.sonic-cite-copy') : null;
            if (copyBtn) {
                var body = $('#citation-' + copyBtn.getAttribute('data-style').toLowerCase());
                if (body) copyText(body.textContent || body.innerText);
                return;
            }
            if (e.target.closest && e.target.closest('#copyAllCitations')) {
                var all = [];
                $all('.sonic-citation-card').forEach(function (card) {
                    var s = $('.sonic-citation-style', card), b = $('.sonic-citation-body', card);
                    if (s && b) all.push(s.textContent + ':\n' + (b.textContent || b.innerText));
                });
                if (all.length) copyText(all.join('\n\n'));
                return;
            }
            var exp = e.target.closest ? e.target.closest('[data-export]') : null;
            if (exp) { exportCite(exp.getAttribute('data-export')); }
        });
    }
    function exportCite(type) {
        var el = $('#sonicCiteData'); if (!el) return;
        var d; try { d = JSON.parse(el.textContent); } catch (e) { return; }
        function buildRIS() {
            var L = ['TY  - BOOK']; (d.authors || []).forEach(function (a) { L.push('AU  - ' + a); });
            L.push('TI  - ' + d.title); if (d.edition) L.push('ET  - ' + d.edition); if (d.place) L.push('CY  - ' + d.place);
            if (d.publisher) L.push('PB  - ' + d.publisher); if (d.year) L.push('PY  - ' + d.year); if (d.isbn) L.push('SN  - ' + d.isbn);
            L.push('UR  - ' + d.url, 'ER  - '); return L.join('\r\n');
        }
        function bibKey() { var a = (d.authors && d.authors[0]) ? d.authors[0].split(' ').pop() : 'anon'; return ((a + (d.year || '')).replace(/[^a-zA-Z0-9]/g, '').toLowerCase()) || 'book'; }
        function buildBib() {
            var L = ['@book{' + bibKey() + ',']; L.push('  title     = {' + d.title + '},');
            if (d.authors && d.authors.length) L.push('  author    = {' + d.authors.join(' and ') + '},');
            if (d.year) L.push('  year      = {' + d.year + '},'); if (d.publisher) L.push('  publisher = {' + d.publisher + '},');
            if (d.place) L.push('  address   = {' + d.place + '},'); if (d.edition) L.push('  edition   = {' + d.edition + '},');
            if (d.isbn) L.push('  isbn      = {' + d.isbn + '},'); L.push('  url       = {' + d.url + '}', '}'); return L.join('\n');
        }
        function buildEN() {
            var L = ['%0 Book']; (d.authors || []).forEach(function (a) { L.push('%A ' + a); });
            L.push('%T ' + d.title); if (d.edition) L.push('%E ' + d.edition); if (d.place) L.push('%C ' + d.place);
            if (d.publisher) L.push('%I ' + d.publisher); if (d.year) L.push('%D ' + d.year); if (d.isbn) L.push('%@ ' + d.isbn);
            L.push('%U ' + d.url); return L.join('\r\n');
        }
        function download(name, content, mime) {
            var blob = new Blob([content], { type: (mime || 'text/plain') + ';charset=utf-8' });
            var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name;
            document.body.appendChild(a); a.click();
            setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 150);
        }
        var base = 'sonic-' + (d.id || 'book');
        if (type === 'ris') { download(base + '.ris', buildRIS(), 'application/x-research-info-systems'); toast('RIS diunduh — siap impor ke Mendeley/Zotero'); }
        else if (type === 'bibtex') { download(base + '.bib', buildBib(), 'application/x-bibtex'); toast('BibTeX diunduh — siap dipakai di LaTeX'); }
        else if (type === 'endnote') { download(base + '.enw', buildEN(), 'application/x-endnote-refer'); toast('EndNote diunduh — siap impor ke EndNote'); }
    }

    /* ===== 5. HEART BURST ===== */
    function initHeart() {
        var btn = $('#bookmarkBtn'), burst = $('#heartBurst');
        if (!btn || !burst || reduced) return;
        btn.addEventListener('click', function (e) {
            if (!btn.classList.contains('active')) return;
            burst.style.left = e.clientX + 'px'; burst.style.top = e.clientY + 'px';
            for (var i = 0; i < 12; i++) {
                var h = document.createElement('div'); h.className = 'heart-particle'; h.textContent = '❤️';
                var ang = (i / 12) * Math.PI * 2, dist = 60 + Math.random() * 40;
                h.style.setProperty('--tx', Math.cos(ang) * dist + 'px');
                h.style.setProperty('--ty', Math.sin(ang) * dist + 'px');
                burst.appendChild(h);
                setTimeout(function (node) { return function () { if (node.parentNode) node.parentNode.removeChild(node); }; }(h), 1000);
            }
        });
    }

    /* ===== 6. SPOTLIGHT (rAF + CSS vars, hover-only) ===== */
    function initSpotlight() {
        if (!hoverCapable || reduced) return;
        $all('.sonic-info-block').forEach(function (block) {
            var move = raf(function (e) {
                var r = block.getBoundingClientRect();
                block.style.setProperty('--mx', ((e.clientX - r.left) / r.width) * 100 + '%');
                block.style.setProperty('--my', ((e.clientY - r.top) / r.height) * 100 + '%');
            });
            block.addEventListener('mouseenter', function () { block.classList.add('spotlight'); });
            block.addEventListener('mousemove', move);
            block.addEventListener('mouseleave', function () { block.classList.remove('spotlight'); });
        });
    }

    /* ===== BOOT ===== */
    function boot() { enhanceCover(); initBook(); initTabs(); initCitations(); initHeart(); initSpotlight(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();