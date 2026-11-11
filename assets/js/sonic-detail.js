/**
 * SONIC OPAC — Detail Record Revolution (FINAL)
 * Modul 1: 3D Book, Tabs, Citations+Export, Cover, Micro-interactions.
 * Modul 2 (Fase 3): Zoom, QR, Reader, Copy Call, Reviews.
 */
(function () {
    'use strict';
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hoverCapable = window.matchMedia && window.matchMedia('(hover: hover)').matches;
    function $(s, c) { return (c || document).querySelector(s); }
    function $all(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
    function raf(fn) { var t = false, args; return function () { args = arguments; if (!t) { t = true; requestAnimationFrame(function () { t = false; fn.apply(null, args); }); } }; }

    var toastEl = $('#copyToast'), toastText = $('#copyToastText'), toastTimer = null;
    function toast(msg) { if (toastEl && toastText) { toastText.textContent = msg; toastEl.classList.add('show'); clearTimeout(toastTimer); toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2500); } else if (window.toastr) toastr.success(msg); }
    function copyText(text) {
        function fb() { var ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); toast('Teks disalin ke clipboard!'); } catch (e) {} document.body.removeChild(ta); }
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(function () { toast('Teks disalin ke clipboard!'); }, fb); else fb();
    }

    function enhanceCover() {
        var book = $('#sonicBook3D'); if (!book) return;
        var img = book.querySelector('img'); if (!img || img.dataset.sonicCover) return;
        img.dataset.sonicCover = '1'; img.classList.add('sonic-cover-img');
        img.setAttribute('loading', 'eager'); img.setAttribute('decoding', 'async'); img.setAttribute('fetchpriority', 'high');
        var sk = document.createElement('div'); sk.className = 'sonic-cover-skeleton'; sk.innerHTML = '<i class="fas fa-book"></i>'; book.appendChild(sk);
        var done = false;
        function ready() { if (done) return; done = true; img.classList.add('ready'); sk.classList.add('hide'); setTimeout(function () { if (sk.parentNode) sk.parentNode.removeChild(sk); }, 500); }
        if (img.complete && img.naturalWidth > 0) ready();
        else { img.addEventListener('load', ready, { once: true }); img.addEventListener('error', function () { if (!/image\.png/.test(img.src)) img.src = './images/default/image.png'; ready(); }, { once: true }); setTimeout(ready, 6000); }
    }

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
            if (dragging) { cx = clamp(cx + (e.clientX - sx) * 0.3, -60, 60); cy = clamp(cy - (e.clientY - sy) * 0.3, -30, 30); sx = e.clientX; sy = e.clientY; write('rotateY(' + cx + 'deg) rotateX(' + cy + 'deg)'); }
            else if (hoverCapable && !reduced) { var r = getRect(); var x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5; write('rotateY(' + (x * 40 - 20) + 'deg) rotateX(' + (-y * 30 + 5) + 'deg)'); }
        });
        book.addEventListener('pointerdown', function (e) { dragging = true; sx = e.clientX; sy = e.clientY; book.classList.add('grabbing'); if (book.setPointerCapture) book.setPointerCapture(e.pointerId); });
        function endDrag(e) { dragging = false; book.classList.remove('grabbing'); if (book.releasePointerCapture && e.pointerId != null) { try { book.releasePointerCapture(e.pointerId); } catch (err) {} } }
        book.addEventListener('pointerup', endDrag); book.addEventListener('pointercancel', endDrag);
        book.addEventListener('pointerleave', function () { if (!dragging) write('rotateY(-20deg) rotateX(5deg)'); });
    }

    function initTabs() {
        var list = $('.sonic-detail-tabs'); if (!list) return;
        var tabs = $all('.sonic-tab', list), panels = $all('.sonic-tab-panel');
        list.setAttribute('role', 'tablist');
        function activate(tab) {
            var target = tab.getAttribute('data-tab');
            tabs.forEach(function (t) { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); t.tabIndex = -1; });
            panels.forEach(function (p) { p.classList.remove('active'); });
            tab.classList.add('active'); tab.setAttribute('aria-selected', 'true'); tab.tabIndex = 0;
            var panel = $('#tab-' + target); if (panel) panel.classList.add('active');
        }
        tabs.forEach(function (tab) {
            var target = tab.getAttribute('data-tab');
            tab.setAttribute('role', 'tab'); tab.id = 'sonic-tab-' + target;
            tab.setAttribute('aria-controls', 'tab-' + target);
            tab.setAttribute('aria-selected', tab.classList.contains('active') ? 'true' : 'false');
            tab.tabIndex = tab.classList.contains('active') ? 0 : -1;
            var panel = $('#tab-' + target); if (panel) { panel.setAttribute('role', 'tabpanel'); panel.setAttribute('aria-labelledby', tab.id); }
            tab.addEventListener('click', function () { activate(tab); });
            tab.addEventListener('keydown', function (e) {
                var idx = tabs.indexOf(tab), n = null;
                if (e.key === 'ArrowRight') n = (idx + 1) % tabs.length; else if (e.key === 'ArrowLeft') n = (idx - 1 + tabs.length) % tabs.length; else if (e.key === 'Home') n = 0; else if (e.key === 'End') n = tabs.length - 1;
                if (n !== null) { e.preventDefault(); tabs[n].focus(); activate(tabs[n]); }
            });
        });
        function applyHash() { var h = (window.location.hash || '').replace('#', ''); var map = { attachment: 'digital', comment: 'comments' }; if (map[h]) { var btn = $('.sonic-tab[data-tab="' + map[h] + '"]'); if (btn) activate(btn); } }
        window.addEventListener('load', applyHash); window.addEventListener('hashchange', applyHash);
    }

    function initCitations() {
        document.addEventListener('click', function (e) {
            var copyBtn = e.target.closest ? e.target.closest('.sonic-cite-copy') : null;
            if (copyBtn) { var body = $('#citation-' + copyBtn.getAttribute('data-style').toLowerCase()); if (body) copyText(body.textContent || body.innerText); return; }
            if (e.target.closest && e.target.closest('#copyAllCitations')) {
                var all = []; $all('.sonic-citation-card').forEach(function (card) { var s = $('.sonic-citation-style', card), b = $('.sonic-citation-body', card); if (s && b) all.push(s.textContent + ':\n' + (b.textContent || b.innerText)); });
                if (all.length) copyText(all.join('\n\n')); return;
            }
            var exp = e.target.closest ? e.target.closest('[data-export]') : null;
            if (exp) exportCite(exp.getAttribute('data-export'));
        });
    }
    function exportCite(type) {
        var el = $('#sonicCiteData'); if (!el) return;
        var d; try { d = JSON.parse(el.textContent); } catch (e) { return; }
        function buildRIS() { var L = ['TY  - BOOK']; (d.authors || []).forEach(function (a) { L.push('AU  - ' + a); }); L.push('TI  - ' + d.title); if (d.edition) L.push('ET  - ' + d.edition); if (d.place) L.push('CY  - ' + d.place); if (d.publisher) L.push('PB  - ' + d.publisher); if (d.year) L.push('PY  - ' + d.year); if (d.isbn) L.push('SN  - ' + d.isbn); L.push('UR  - ' + d.url, 'ER  - '); return L.join('\r\n'); }
        function bibKey() { var a = (d.authors && d.authors[0]) ? d.authors[0].split(' ').pop() : 'anon'; return ((a + (d.year || '')).replace(/[^a-zA-Z0-9]/g, '').toLowerCase()) || 'book'; }
        function buildBib() { var L = ['@book{' + bibKey() + ',']; L.push('  title     = {' + d.title + '},'); if (d.authors && d.authors.length) L.push('  author    = {' + d.authors.join(' and ') + '},'); if (d.year) L.push('  year      = {' + d.year + '},'); if (d.publisher) L.push('  publisher = {' + d.publisher + '},'); if (d.place) L.push('  address   = {' + d.place + '},'); if (d.edition) L.push('  edition   = {' + d.edition + '},'); if (d.isbn) L.push('  isbn      = {' + d.isbn + '},'); L.push('  url       = {' + d.url + '}', '}'); return L.join('\n'); }
        function buildEN() { var L = ['%0 Book']; (d.authors || []).forEach(function (a) { L.push('%A ' + a); }); L.push('%T ' + d.title); if (d.edition) L.push('%E ' + d.edition); if (d.place) L.push('%C ' + d.place); if (d.publisher) L.push('%I ' + d.publisher); if (d.year) L.push('%D ' + d.year); if (d.isbn) L.push('%@ ' + d.isbn); L.push('%U ' + d.url); return L.join('\r\n'); }
        function download(name, content, mime) { var blob = new Blob([content], { type: (mime || 'text/plain') + ';charset=utf-8' }); var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name; document.body.appendChild(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 150); }
        var base = 'sonic-' + (d.id || 'book');
        if (type === 'ris') { download(base + '.ris', buildRIS(), 'application/x-research-info-systems'); toast('RIS diunduh — siap impor ke Mendeley/Zotero'); }
        else if (type === 'bibtex') { download(base + '.bib', buildBib(), 'application/x-bibtex'); toast('BibTeX diunduh — siap dipakai di LaTeX'); }
        else if (type === 'endnote') { download(base + '.enw', buildEN(), 'application/x-endnote-refer'); toast('EndNote diunduh — siap impor ke EndNote'); }
    }

    function initHeart() {
        var btn = $('#bookmarkBtn'), burst = $('#heartBurst'); if (!btn || !burst || reduced) return;
        btn.addEventListener('click', function (e) {
            if (!btn.classList.contains('active')) return;
            burst.style.left = e.clientX + 'px'; burst.style.top = e.clientY + 'px';
            for (var i = 0; i < 12; i++) { var h = document.createElement('div'); h.className = 'heart-particle'; h.textContent = '❤️'; var ang = (i / 12) * Math.PI * 2, dist = 60 + Math.random() * 40; h.style.setProperty('--tx', Math.cos(ang) * dist + 'px'); h.style.setProperty('--ty', Math.sin(ang) * dist + 'px'); burst.appendChild(h); setTimeout(function (n) { return function () { if (n.parentNode) n.parentNode.removeChild(n); }; }(h), 1000); }
        });
    }
    function initSpotlight() {
        if (!hoverCapable || reduced) return;
        $all('.sonic-info-block').forEach(function (block) {
            var move = raf(function (e) { var r = block.getBoundingClientRect(); block.style.setProperty('--mx', ((e.clientX - r.left) / r.width) * 100 + '%'); block.style.setProperty('--my', ((e.clientY - r.top) / r.height) * 100 + '%'); });
            block.addEventListener('mouseenter', function () { block.classList.add('spotlight'); });
            block.addEventListener('mousemove', move);
            block.addEventListener('mouseleave', function () { block.classList.remove('spotlight'); });
        });
    }

    function boot() { enhanceCover(); initBook(); initTabs(); initCitations(); initHeart(); initSpotlight(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();

/* ===== MODUL 2 — FASE 3: Zoom, QR, Reader, Copy Call, Reviews ===== */
(function () {
    'use strict';
    function $(s, c) { return (c || document).querySelector(s); }
    function $all(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function base() { var l = $('link[href*="sonic-base.css"]'); return l ? l.href.replace(/assets\/css\/sonic-base\.css.*$/, '') : 'template/sonic/'; }
    function toast(m) { var t = $('#copyToast'), x = $('#copyToastText'); if (t && x) { x.textContent = m; t.classList.add('show'); setTimeout(function () { t.classList.remove('show'); }, 2200); } else if (window.toastr) toastr.success(m); }
    function copy(text) { if (navigator.clipboard) navigator.clipboard.writeText(text).then(function () { toast('Disalin!'); }); else { var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); toast('Disalin!'); } }
    function modal(id, html) {
        var m = document.getElementById(id);
        if (!m) { m = document.createElement('div'); m.id = id; m.className = 'sonic-modal'; m.innerHTML = '<div class="sonic-terminal sonic-terminal-wide"><div class="sonic-terminal-head"><span class="sonic-dot r"></span><span class="sonic-dot y"></span><span class="sonic-dot g"></span><span class="sonic-terminal-title"></span><button type="button" class="sonic-terminal-close" data-close>&times;</button></div><div class="sonic-terminal-body"></div></div>'; document.body.appendChild(m); m.addEventListener('click', function (e) { if (e.target === m || e.target.hasAttribute('data-close')) m.classList.remove('active'); }); }
        m.querySelector('.sonic-terminal-title').textContent = id;
        m.querySelector('.sonic-terminal-body').innerHTML = html;
        m.classList.add('active'); return m;
    }
    function initZoom() { var b = $('#sonicZoomBtn'); if (!b) return; b.addEventListener('click', function () { modal('sonicZoomModal', '<div class="sonic-zoom-wrap"><img src="' + esc(b.getAttribute('data-large')) + '" alt="cover"></div>'); }); }
    function initQr() { var b = $('#sonicQrBtn'); if (!b) return; b.addEventListener('click', function () { var url = b.getAttribute('data-url'); var img = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' + encodeURIComponent(url); modal('sonicQrModal', '<div class="sonic-qr-wrap"><img src="' + img + '" alt="QR" onerror="this.style.display=\'none\'"><p class="sonic-qr-url">' + esc(url) + '</p><button type="button" class="sonic-mini-btn" id="sonicQrCopy"><i class="fas fa-link"></i> Salin URL</button></div>'); var c = $('#sonicQrCopy'); if (c) c.addEventListener('click', function () { copy(url); }); }); }
    function initCopyCall() { var b = $('#sonicCopyCall'); if (!b) return; b.addEventListener('click', function () { copy(b.getAttribute('data-call')); }); }
    function initReader() {
        $all('.sonic-attach-deck a').forEach(function (a) {
            var href = a.getAttribute('href') || ''; var txt = (a.textContent || '') + ' ' + href;
            if (!/\.pdf|pdf/i.test(txt)) return; if (a.dataset.sonicRead) return; a.dataset.sonicRead = '1';
            var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'sonic-mini-btn sonic-read-btn'; btn.innerHTML = '<i class="fas fa-book-open"></i> Baca';
            btn.addEventListener('click', function (e) { e.preventDefault(); modal('sonicReaderModal', '<div class="sonic-reader-bar"><a class="sonic-mini-btn" href="' + esc(href) + '" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Tab baru</a></div><iframe class="sonic-reader-frame" src="' + esc(href) + '" title="reader"></iframe>'); });
            a.parentNode.insertBefore(btn, a.nextSibling);
        });
    }
    function stars(n) { var s = ''; for (var i = 1; i <= 5; i++) s += '<i class="' + (i <= Math.round(n) ? 'fas' : 'far') + ' fa-star"></i>'; return s; }
    function initReviews() {
        var root = $('.sonic-detail-revolution'); if (!root) return;
        var id = root.getAttribute('data-biblio-id');
        var sum = $('#sonicRatingSummary'), list = $('#sonicReviewList');
        function load() {
            fetch(base() + 'api/review.php?biblio_id=' + id).then(function (r) { return r.json(); }).then(function (d) {
                if (sum) sum.innerHTML = '<span class="sonic-stars">' + stars(d.avg) + '</span> <b>' + d.avg + '</b> / 5 · ' + d.count + ' ulasan';
                if (list) { list.innerHTML = d.items.length ? '' : '<p class="sonic-review-empty">Belum ada ulasan. Jadilah yang pertama!</p>';
                    d.items.forEach(function (it) { var el = document.createElement('div'); el.className = 'sonic-review-item'; el.innerHTML = '<div class="sonic-review-head"><span class="sonic-stars">' + stars(it.rating) + '</span><strong>' + esc(it.member_name || 'Pemustaka') + '</strong><em>' + esc((it.created_at || '').slice(0, 10)) + '</em></div><p>' + esc(it.review) + '</p>'; list.appendChild(el); }); }
            }).catch(function () {});
        }
        load();
        var starBox = $('#sonicReviewStars'), ratingInput = $('#sonicRatingInput');
        if (starBox) starBox.addEventListener('click', function (e) { var b = e.target.closest('button[data-star]'); if (!b) return; var v = parseInt(b.getAttribute('data-star'), 10); ratingInput.value = v; $all('button', starBox).forEach(function (x) { var on = parseInt(x.getAttribute('data-star'), 10) <= v; x.innerHTML = '<i class="' + (on ? 'fas' : 'far') + ' fa-star"></i>'; }); });
        var form = $('#sonicReviewForm');
        if (form) form.addEventListener('submit', function (e) { e.preventDefault(); var fd = new FormData(form); fetch(base() + 'api/review.php', { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (d) { toast(d.message || 'Tersimpan'); if (d.status) { form.reset(); load(); } }).catch(function () { toast('Gagal mengirim ulasan'); }); });
    }
    function boot3() { initZoom(); initQr(); initCopyCall(); initReader(); initReviews(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot3); else boot3();
})();