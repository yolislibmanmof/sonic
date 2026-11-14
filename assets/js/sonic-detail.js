/**
 * SONIC OPAC — Detail Record Revolution (FINAL — BATCH 4)
 * Modul 1: 3D Book, Tabs, Citations+Export, Cover, Micro-interactions.
 * Modul 2 (Fase 3): Zoom, QR, Reader, Copy Call, Reviews.
 * BATCH 4: token ulasan rotasi Tahan form.reset() (fix submit ke-2 = 403),
 *          selector token tanpa hash hardcode, guard respons JSON, QR lokal otomatis.
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

/* ===== MODUL 2 — FASE 3: Zoom, QR, Reader, Copy Call, Reviews (BATCH 4) ===== */
(function () {
    'use strict';
    function $(s, c) { return (c || document).querySelector(s); }
    function $all(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function base() { if (window.SONIC_CFG && window.SONIC_CFG.base) return window.SONIC_CFG.base; var l = $('link[href*="sonic-base.css"]'); return l ? l.href.replace(/assets\/css\/sonic-base\.css.*$/, '') : 'template/sonic/'; }

    var toastTimer2 = null;
    function toast(m) { var t = $('#copyToast'), x = $('#copyToastText'); if (t && x) { x.textContent = m; t.classList.add('show'); clearTimeout(toastTimer2); toastTimer2 = setTimeout(function () { t.classList.remove('show'); }, 2200); } else if (window.toastr) toastr.success(m); }
    function copy(text) {
        function fb() { var ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); toast('Disalin!'); } catch (e) {} document.body.removeChild(ta); }
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(function () { toast('Disalin!'); }, fb); else fb();
    }

    var escBound = false;
    function modal(id, html) {
        var m = document.getElementById(id);
        if (!m) {
            m = document.createElement('div'); m.id = id; m.className = 'sonic-modal';
            m.innerHTML = '<div class="sonic-terminal sonic-terminal-wide"><div class="sonic-terminal-head"><span class="sonic-dot r"></span><span class="sonic-dot y"></span><span class="sonic-dot g"></span><span class="sonic-terminal-title"></span><button type="button" class="sonic-terminal-close" data-close>&times;</button></div><div class="sonic-terminal-body"></div></div>';
            document.body.appendChild(m);
            m.addEventListener('click', function (e) { if (e.target === m || e.target.hasAttribute('data-close')) m.classList.remove('active'); });
        }
        if (!escBound) { escBound = true; document.addEventListener('keydown', function (e) { if (e.key === 'Escape') $all('.sonic-modal.active').forEach(function (x) { x.classList.remove('active'); }); }); }
        m.querySelector('.sonic-terminal-title').textContent = id;
        m.querySelector('.sonic-terminal-body').innerHTML = html;
        m.classList.add('active'); return m;
    }

    function initZoom() { var b = $('#sonicZoomBtn'); if (!b) return; b.addEventListener('click', function () { modal('sonicZoomModal', '<div class="sonic-zoom-wrap"><img src="' + esc(b.getAttribute('data-large')) + '" alt="cover"></div>'); }); }

    function initQr() {
        var b = $('#sonicQrBtn'); if (!b) return;
        b.addEventListener('click', function () {
            var url = b.getAttribute('data-url') || window.location.href;
            var html = null;
            /* QR lokal otomatis bila assets/plugin/qrcode/qrcode.min.js dimuat (lihat patch footer + tools/fetch_qr.php).
               Tanpa itu, fallback online tetap berfungsi persis seperti sebelumnya. */
            if (typeof window.qrcode === 'function') {
                try {
                    var qr = window.qrcode(0, 'M'); qr.addData(url); qr.make();
                    html = '<div class="sonic-qr-wrap"><div style="display:flex;justify-content:center;background:#fff;padding:10px;border-radius:10px;">' + qr.createSvgTag(4, 2) + '</div><p class="sonic-qr-url">' + esc(url) + '</p><button type="button" class="sonic-mini-btn" id="sonicQrCopy"><i class="fas fa-link"></i> Salin URL</button></div>';
                } catch (e) { html = null; }
            }
            if (!html) {
                var img = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' + encodeURIComponent(url);
                html = '<div class="sonic-qr-wrap"><img src="' + img + '" alt="QR" onerror="this.style.display=\'none\'"><p class="sonic-qr-url">' + esc(url) + '</p><button type="button" class="sonic-mini-btn" id="sonicQrCopy"><i class="fas fa-link"></i> Salin URL</button></div>';
            }
            modal('sonicQrModal', html);
            var c = $('#sonicQrCopy'); if (c) c.addEventListener('click', function () { copy(url); });
        });
    }

    function initCopyCall() { var b = $('#sonicCopyCall'); if (!b) return; b.addEventListener('click', function () { copy(b.getAttribute('data-call')); }); }

    /* ===== MODUL 2b — FLIPBOOK READER (Batch 7): Standar utuh + Flip Buku ===== */
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function pdfUrlFrom(href) {
        var m = href.match(/[?&]file=([^&]+)/);
        if (m) { try { return decodeURIComponent(m[1]); } catch (e) { return m[1]; } }
        return href;
    }
    function pdfjsCandidates(href) {
        var list = [base() + 'assets/plugin/pdfjs/build/pdf.js', base() + 'assets/plugin/pdfjs/build/pdf.min.js'];
        var v = href.match(/^(.*?)(?:web\/)?viewer\.html/);
        if (v) { list.push(v[1] + 'build/pdf.js', v[1] + 'build/pdf.min.js', v[1] + 'pdf.js'); }
        list.push('lib/pdfjs/build/pdf.js', 'lib/pdfjs/build/pdf.min.js', 'lib/pdfjs/pdf.js', 'lib/pdf.js/build/pdf.js');
        return list;
    }
    function loadPdfJs(href) {
        if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
        if (window.PDFJS) return Promise.resolve(window.PDFJS);
        var cands = pdfjsCandidates(href);
        return new Promise(function (resolve, reject) {
            var i = 0;
            (function next() {
                if (i >= cands.length) { reject(new Error('pdfjs-not-found')); return; }
                var url = cands[i++];
                var s = document.createElement('script');
                s.src = url;
                s.onload = function () {
                    var lib = window.pdfjsLib || window.PDFJS;
                    if (!lib) { next(); return; }
                    var worker = url.replace(/pdf(\.min)?\.js(\?.*)?$/, 'pdf.worker$1.js');
                    try { if (lib.GlobalWorkerOptions) lib.GlobalWorkerOptions.workerSrc = worker; else lib.workerSrc = worker; } catch (e) {}
                    resolve(lib);
                };
                s.onerror = function () { s.remove(); next(); };
                document.head.appendChild(s);
            })();
        });
    }

    function openReader(href) {
        var pdfUrl = pdfUrlFrom(href);
        var root = $('.sonic-detail-revolution');
        var biblioId = root ? root.getAttribute('data-biblio-id') : '0';
        var posKey = 'sonicFlipPos:' + biblioId + ':' + pdfUrl;
        var shell =
            '<div class="sonic-reader-bar">' +
              '<div class="sonic-reader-modes">' +
                '<button type="button" class="sonic-mini-btn sonic-reader-mode" data-mode="flip" aria-pressed="false"><i class="fas fa-book"></i> Flip Buku</button>' +
                '<button type="button" class="sonic-mini-btn sonic-reader-mode" data-mode="std" aria-pressed="false"><i class="fas fa-file-pdf"></i> Standar</button>' +
              '</div>' +
              '<a class="sonic-mini-btn" href="' + esc(href) + '" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Tab baru</a>' +
            '</div>' +
            '<div class="sonic-reader-flip" hidden>' +
              '<div class="sonic-flip-toolbar">' +
                '<button type="button" class="sonic-mini-btn" data-flip="first" title="Awal"><i class="fas fa-angle-double-left"></i></button>' +
                '<button type="button" class="sonic-mini-btn" data-flip="prev" title="Sebelumnya"><i class="fas fa-angle-left"></i></button>' +
                '<span class="sonic-flip-indicator">—</span>' +
                '<button type="button" class="sonic-mini-btn" data-flip="next" title="Berikutnya"><i class="fas fa-angle-right"></i></button>' +
                '<button type="button" class="sonic-mini-btn" data-flip="last" title="Akhir"><i class="fas fa-angle-double-right"></i></button>' +
                '<span class="sonic-flip-progress"><i></i></span>' +
                '<button type="button" class="sonic-mini-btn" data-flip="zoomout" title="Perkecil"><i class="fas fa-search-minus"></i></button>' +
                '<button type="button" class="sonic-mini-btn" data-flip="zoomin" title="Perbesar"><i class="fas fa-search-plus"></i></button>' +
                '<button type="button" class="sonic-mini-btn" data-flip="full" title="Layar penuh"><i class="fas fa-expand"></i></button>' +
              '</div>' +
              '<div class="sonic-flip-stage">' +
                '<div class="sonic-flip-book">' +
                  '<div class="sonic-flip-page left"><img alt="" hidden><span class="blank"></span></div>' +
                  '<div class="sonic-flip-page right"><img alt="" hidden><span class="blank"></span></div>' +
                  '<div class="sonic-flip-leaf" hidden><div class="face front"><img alt=""></div><div class="face back"><img alt=""></div></div>' +
                '</div>' +
              '</div>' +
              '<div class="sonic-flip-status"></div>' +
            '</div>' +
            '<div class="sonic-reader-std" hidden><iframe class="sonic-reader-frame" title="reader" src="about:blank"></iframe></div>';
        var m = modal('sonicReaderModal', shell);

        var els = {
            flip: m.querySelector('.sonic-reader-flip'),
            std: m.querySelector('.sonic-reader-std'),
            frame: m.querySelector('.sonic-reader-frame'),
            stage: m.querySelector('.sonic-flip-stage'),
            book: m.querySelector('.sonic-flip-book'),
            left: m.querySelector('.sonic-flip-page.left'),
            right: m.querySelector('.sonic-flip-page.right'),
            leaf: m.querySelector('.sonic-flip-leaf'),
            ind: m.querySelector('.sonic-flip-indicator'),
            prog: m.querySelector('.sonic-flip-progress i'),
            status: m.querySelector('.sonic-flip-status'),
            modes: $all('.sonic-reader-mode', m)
        };
        var st = { pdf: null, num: 0, leaves: 0, f: 0, zoom: 1, cache: {}, pending: {}, busy: false, ready: false, loading: false, mode: 'flip' };

        function status(msg, html) { if (html) els.status.innerHTML = msg; else els.status.textContent = msg; }
        function savePos() { try { localStorage.setItem(posKey, String(st.f)); } catch (e) {} }
        function setPage(el, url) {
            var img = el.querySelector('img'), blank = el.querySelector('.blank');
            if (url) { img.src = url; img.hidden = false; if (blank) blank.textContent = ''; }
            else { img.hidden = true; if (blank) blank.textContent = ''; }
        }
        /* Antrean render maks 2 agar tidak berebut CPU dengan animasi */
        var rq = [], rActive = 0;
        function pump() {
            while (rActive < 2 && rq.length) {
                var job = rq.shift(); rActive++;
                job(function () { rActive--; pump(); });
            }
        }
        function pageImg(i) {
            if (i < 0 || i >= st.num) return Promise.resolve(null);
            var key = 'p' + i;
            if (st.cache[key]) return Promise.resolve(st.cache[key]);
            if (st.pending[key]) return st.pending[key];
            var p = new Promise(function (resolve) {
                rq.push(function (done) {
                    st.pdf.getPage(i + 1).then(function (page) {
                        var h = els.stage.clientHeight || 480;
                        var bv = page.getViewport({ scale: 1 });
                        var dpr = Math.min(2, window.devicePixelRatio || 1);
                        var vp = page.getViewport({ scale: (h / bv.height) * dpr });
                        var cv = document.createElement('canvas');
                        cv.width = Math.max(1, Math.floor(vp.width)); cv.height = Math.max(1, Math.floor(vp.height));
                        var ctx = cv.getContext('2d', { alpha: false });
                        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, cv.width, cv.height);
                        return page.render({ canvasContext: ctx, viewport: vp }).promise.then(function () {
                            var url = cv.toDataURL('image/jpeg', 0.85);
                            st.cache[key] = url;
                            var ks = Object.keys(st.cache);
                            if (ks.length > 48) delete st.cache[ks[0]];
                            resolve(url);
                        });
                    }).catch(function () { resolve(null); }).then(function () { delete st.pending[key]; done(); });
                });
                pump();
            });
            st.pending[key] = p;
            return p;
        }
        function updateHud() {
            var L = 2 * st.f - 1, R = 2 * st.f;
            var lt = (L >= 0 && L < st.num) ? (L + 1) : null;
            var rt = (R >= 0 && R < st.num) ? (R + 1) : null;
            els.ind.textContent = lt && rt ? ('Halaman ' + lt + '–' + rt + ' / ' + st.num) : (rt ? ('Halaman ' + rt + ' / ' + st.num) : (lt ? ('Halaman ' + lt + ' / ' + st.num) : '—'));
            els.prog.style.width = (st.leaves ? (st.f / st.leaves) * 100 : 0) + '%';
        }
        function renderSpread() {
            if (!st.ready) return;
            status('Merender halaman…');
            els.book.style.transform = 'scale(' + st.zoom + ')';
            var L = 2 * st.f - 1, R = 2 * st.f;
            Promise.all([pageImg(L), pageImg(R)]).then(function (r) {
                setPage(els.left, r[0]); setPage(els.right, r[1]);
                updateHud();
                if (!r[0] && !r[1] && st.num > 0) {
                    status('Render halaman gagal (kemungkinan CSP blob). Coba mode Standar atau muat ulang.', false);
                    try { console.warn('[sonic-flip] render kosong — cek CSP img-src blob:'); } catch (e) {}
                } else status('');
            });
        }
        /* Prefetch dua arah saat idle → flip maju/mundur selalu dari cache */
        function prefetch() {
            if (!st.ready) return;
            var idle = window.requestIdleCallback || function (fn) { return setTimeout(fn, 120); };
            idle(function () {
                [st.f + 1, st.f - 1].forEach(function (n) {
                    if (n < 0 || n > st.leaves) return;
                    pageImg(2 * n - 1); pageImg(2 * n);
                });
            });
        }
        function commitTo(target) {
            st.f = target; st.busy = false;
            savePos(); renderSpread(); prefetch();
        }
        function flip(dir) {
            if (!st.ready || st.busy) return;
            var target = st.f + dir;
            if (target < 0 || target > st.leaves) return;
            st.busy = true;
            var side, frontIdx, backIdx, underIdx;
            if (dir > 0) { side = 'right'; frontIdx = 2 * st.f; backIdx = 2 * st.f + 1; underIdx = 2 * st.f + 2; }
            else { side = 'left'; frontIdx = 2 * st.f - 1; backIdx = 2 * st.f - 2; underIdx = 2 * st.f - 3; }
            Promise.all([pageImg(frontIdx), pageImg(backIdx), pageImg(underIdx)]).then(function (r) {
                els.leaf.className = 'sonic-flip-leaf ' + side;
                els.leaf.querySelector('.front img').src = r[0] || '';
                els.leaf.querySelector('.back img').src = r[1] || '';
                if (dir > 0) setPage(els.right, r[2]); else setPage(els.left, r[2]);
                els.leaf.hidden = false;
                void els.leaf.offsetWidth;
                var done = false;
                function commit() {
                    if (done) return; done = true;
                    els.leaf.removeEventListener('transitionend', commit);
                    els.leaf.hidden = true; els.leaf.classList.remove('go');
                    commitTo(target);
                }
                if (reducedMotion) { commit(); return; }
                els.leaf.classList.add('go');
                els.leaf.addEventListener('transitionend', commit);
                setTimeout(commit, 950);
            });
        }
        function jump(target) {
            if (!st.ready || st.busy) return;
            target = Math.max(0, Math.min(st.leaves, target));
            commitTo(target);
        }
        /* Zoom = transform CSS (GPU, instan), bukan render ulang */
        function setZoom(z) {
            st.zoom = Math.max(0.6, Math.min(2.4, z));
            els.book.style.transform = 'scale(' + st.zoom + ')';
        }
        function toggleFull() {
            if (document.fullscreenElement) { if (document.exitFullscreen) document.exitFullscreen(); }
            else if (els.stage.requestFullscreen) els.stage.requestFullscreen();
        }
        /* Resolve URL PDF asli (wrapper fstream mengembalikan HTML), lalu parse &
           render dengan PDF.js vendored 3.x — decode JPEG tanpa blob-Image → aman CSP. */
        function coreWin() { try { return els.frame.contentWindow; } catch (e) { return null; } }
        function absUrl(u) { try { return new URL(u, window.location.href).href; } catch (e) { return u; } }
         /* Ambil byte PDF sendiri: validasi magic %PDF-, fallback Range, multi-URL.
           Lalu serahkan byte mentah ke PDF.js (data) → bypass lapisan jaringan PDF.js. */
        function fetchPdfBytes(url) {
            function attempt(headers) {
                return fetch(url, { credentials: 'same-origin', headers: headers || {} }).then(function (r) {
                    if (!r.ok && r.status !== 206) return null;
                    return r.arrayBuffer().then(function (buf) {
                        if (!buf || buf.byteLength < 5) return null;
                        var u = new Uint8Array(buf, 0, 5);
                        if (String.fromCharCode(u[0], u[1], u[2], u[3], u[4]) !== '%PDF-') return null;
                        return buf;
                    });
                }).catch(function () { return null; });
            }
            return attempt({ Range: 'bytes=0-' }).then(function (b) { return b || attempt(); });
        }
        function candidateUrls() {
            var list = [];
            function push(u) { if (u && list.indexOf(u) < 0) list.push(u); }
            push(absUrl(pdfUrl));
            push(absUrl(pdfUrl.replace(/p=fstream(?![\w-])/, 'p=fstream-pdf')));
            try {
                var w = coreWin(); var app = w && w.PDFViewerApplication;
                var u = app && (app.url || (app.pdfLoadingTask && app.pdfLoadingTask.url) || app.baseUrl);
                if (u) push(absUrl(u));
            } catch (e) {}
            return fetch(pdfUrl, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (txt) {
                var re = /["']([^"'\s<>]*(?:p=fstream[^"'\s<>]*|\.pdf(?:\?[^"'\s<>]*)?))["']/gi, m;
                while ((m = re.exec(txt))) push(absUrl(decodeURIComponent(m[1])));
                var d = txt.match(/defaultUrl\s*=\s*["']([^"']+)["']/i);
                if (d) push(absUrl(decodeURIComponent(d[1])));
                return list;
            }).catch(function () { return list; });
        }
        function ensureBook() {
            if (st.ready || st.loading) return;
            st.loading = true;
            status('Memuat mesin PDF & dokumen…');
            candidateUrls().then(function (urls) {
                var i = 0;
                (function next() {
                    if (i >= urls.length) return Promise.reject(new Error('pdf-bytes-not-found'));
                    var u = urls[i++];
                    return fetchPdfBytes(u).then(function (buf) { return buf || next(); });
                })();
                return (function loop() {
                    if (i >= urls.length) return Promise.reject(new Error('pdf-bytes-not-found'));
                    var u = urls[i++];
                    return fetchPdfBytes(u).then(function (buf) { return buf || loop(); });
                })();
            }).then(function (buf) {
                return loadPdfJs(pdfUrl).then(function (lib) {
                    return lib.getDocument({ data: buf, withCredentials: true }).promise;
                });
            }).then(function (doc) {
                st.pdf = doc; st.num = doc.numPages; st.leaves = Math.ceil(st.num / 2);
                var saved = 0; try { saved = parseInt(localStorage.getItem(posKey) || '0', 10) || 0; } catch (e) {}
                st.f = Math.max(0, Math.min(st.leaves, saved));
                st.ready = true; st.loading = false;
                status(''); renderSpread(); prefetch();
            }).catch(function (err) {
                st.loading = false;
                try { console.error('[sonic-flip]', err); } catch (e) {}
                status('Gagal memuat mode flip: ' + esc(err && err.message ? err.message : String(err)) +
                    ' <button type="button" class="sonic-mini-btn" data-flip="retry"><i class="fas fa-redo"></i> Coba lagi</button>' +
                    ' <button type="button" class="sonic-mini-btn" data-flip="std"><i class="fas fa-file-pdf"></i> Standar</button>', true);
                if (!els.status.dataset.bound) {
                    els.status.dataset.bound = '1';
                    els.status.addEventListener('click', function (e) {
                        var b = e.target.closest('[data-flip]'); if (!b) return;
                        var act = b.getAttribute('data-flip');
                        if (act === 'retry') { status(''); ensureBook(); }
                        else if (act === 'std') setMode('std');
                    });
                }
            });
        }
        function setMode(mode) {
            st.mode = mode;
            try { localStorage.setItem('sonicReaderMode', mode); } catch (e) {}
            els.modes.forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-mode') === mode)); });
            els.flip.hidden = mode !== 'flip';
            els.std.hidden = mode !== 'std';
            if (mode === 'std') { if (els.frame.src === 'about:blank' || !els.frame.src) els.frame.src = href; }
            else ensureBook();
        }

        els.modes.forEach(function (b) { b.addEventListener('click', function () { setMode(b.getAttribute('data-mode')); }); });
        m.querySelector('.sonic-flip-toolbar').addEventListener('click', function (e) {
            var b = e.target.closest('[data-flip]'); if (!b) return;
            var act = b.getAttribute('data-flip');
            if (act === 'next') flip(1);
            else if (act === 'prev') flip(-1);
            else if (act === 'first') jump(0);
            else if (act === 'last') jump(st.leaves);
            else if (act === 'zoomin') setZoom(st.zoom + 0.2);
            else if (act === 'zoomout') setZoom(st.zoom - 0.2);
            else if (act === 'full') toggleFull();
            else if (act === 'std') setMode('std');
        });
        els.right.addEventListener('click', function () { flip(1); });
        els.left.addEventListener('click', function () { flip(-1); });
        var sx = 0, sy = 0, pid = null;
        els.stage.addEventListener('pointerdown', function (e) { pid = e.pointerId; sx = e.clientX; sy = e.clientY; });
        els.stage.addEventListener('pointerup', function (e) {
            if (pid === null || e.pointerId !== pid) return; pid = null;
            var dx = e.clientX - sx, dy = e.clientY - sy;
            if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.4) flip(dx < 0 ? 1 : -1);
        });
        els.stage.addEventListener('pointercancel', function () { pid = null; });
        document.addEventListener('keydown', function (e) {
            if (!m.classList.contains('active') || st.mode !== 'flip') return;
            if (e.key === 'ArrowRight') { e.preventDefault(); flip(1); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); flip(-1); }
            else if (e.key === 'Home') { e.preventDefault(); jump(0); }
            else if (e.key === 'End') { e.preventDefault(); jump(st.leaves); }
            else if (e.key === '+' || e.key === '=') setZoom(st.zoom + 0.2);
            else if (e.key === '-') setZoom(st.zoom - 0.2);
            else if (e.key === 'f' || e.key === 'F') toggleFull();
        });
        document.addEventListener('fullscreenchange', function () {
            var b = m.querySelector('[data-flip="full"] i');
            if (b) b.className = document.fullscreenElement ? 'fas fa-compress' : 'fas fa-expand';
        });

        var pref = 'flip'; try { pref = localStorage.getItem('sonicReaderMode') || 'flip'; } catch (e) {}
        setMode(pref === 'std' ? 'std' : 'flip');
    }

    function initReader() {
        var deck = $('.sonic-attach-deck');
        $all('.sonic-attach-deck a').forEach(function (a) {
            var href = a.getAttribute('href') || ''; var txt = (a.textContent || '') + ' ' + href;
            var isPdf = /\.pdf(\?|#|$)/i.test(txt) || /pdfjs|viewer\.html/i.test(href) ||
                (/fstream/i.test(href) && !/\.(docx?|xlsx?|pptx?|zip|rar|7z|mp4|mp3|avi|jpg|jpeg|png|gif|epub|mobi)(\?|#|$)/i.test(txt));
            if (!isPdf) return; if (a.dataset.sonicRead) return; a.dataset.sonicRead = '1';
            var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'sonic-mini-btn sonic-read-btn'; btn.innerHTML = '<i class="fas fa-book-open"></i> Baca';
            btn.addEventListener('click', function (e) { e.preventDefault(); openReader(href); });
            a.parentNode.insertBefore(btn, a.nextSibling);
        });
        /* Intercept klik link PDF pada FASE CAPTURE — berjalan SEBELUM handler colorbox core,
           sehingga link nama file membuka reader SONIC (flip/standar), bukan colorbox.
           Lampiran non-PDF tidak disentuh (tidak punya dataset.sonicRead). */
        if (deck && !deck.dataset.sonicFlipBind) {
            deck.dataset.sonicFlipBind = '1';
            deck.addEventListener('click', function (e) {
                var a = e.target.closest ? e.target.closest('a') : null;
                if (!a || !a.dataset.sonicRead) return;
                e.preventDefault();
                e.stopPropagation();
                openReader(a.getAttribute('href') || '');
            }, true);
        }
    }

    function stars(n) { var s = ''; for (var i = 1; i <= 5; i++) s += '<i class="' + (i <= Math.round(n) ? 'fas' : 'far') + ' fa-star"></i>'; return s; }

    /* BATCH 4: cari field token tanpa hash hardcode; dukung nama apa pun dari server. */
    function findTokenField(form, name) {
        if (name) { var byName = form.querySelector('input[name="' + name + '"]'); if (byName) return byName; }
        return form.querySelector('input[type="hidden"][name^="_csrf_token_"]') || form.querySelector('input[type="hidden"][name="csrf_token"]');
    }

    function initReviews() {
        var root = $('.sonic-detail-revolution'); if (!root) return;
        var id = root.getAttribute('data-biblio-id');
        var sum = $('#sonicRatingSummary'), list = $('#sonicReviewList');
        function load() {
            fetch(base() + 'api/review.php?biblio_id=' + encodeURIComponent(id)).then(function (r) { return r.json(); }).then(function (d) {
                if (!d || typeof d !== 'object') return;
                var avg = Number(d.avg) || 0, count = Number(d.count) || 0;
                if (sum) sum.innerHTML = '<span class="sonic-stars">' + stars(avg) + '</span> <b>' + avg + '</b> / 5 · ' + count + ' ulasan';
                var items = Array.isArray(d.items) ? d.items : [];
                if (list) {
                    list.innerHTML = items.length ? '' : '<p class="sonic-review-empty">Belum ada ulasan. Jadilah yang pertama!</p>';
                    items.forEach(function (it) { var el = document.createElement('div'); el.className = 'sonic-review-item'; el.innerHTML = '<div class="sonic-review-head"><span class="sonic-stars">' + stars(it.rating) + '</span><strong>' + esc(it.member_name || 'Pemustaka') + '</strong><em>' + esc((it.created_at || '').slice(0, 10)) + '</em></div><p>' + esc(it.review) + '</p>'; list.appendChild(el); });
                }
            }).catch(function () {});
        }
        load();
        var starBox = $('#sonicReviewStars'), ratingInput = $('#sonicRatingInput');
        function paintStars(v) { if (!starBox) return; $all('button', starBox).forEach(function (x) { var on = parseInt(x.getAttribute('data-star'), 10) <= v; x.innerHTML = '<i class="' + (on ? 'fas' : 'far') + ' fa-star"></i>'; }); }
        if (starBox) starBox.addEventListener('click', function (e) { var b = e.target.closest('button[data-star]'); if (!b) return; var v = parseInt(b.getAttribute('data-star'), 10); if (ratingInput) ratingInput.value = v; paintStars(v); });
        var form = $('#sonicReviewForm');
        if (form) form.addEventListener('submit', function (e) {
            e.preventDefault();
            var sb = form.querySelector('button[type="submit"],input[type="submit"]');
            if (sb) sb.disabled = true;
            var fd = new FormData(form);
            fetch(base() + 'api/review.php', { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (d) {
                if (!d || typeof d !== 'object') { toast('Respons tidak valid'); return; }
                /* FIX BATCH 4: setAttribute ikut memperbarui defaultValue, sehingga form.reset()
                   TIDAK membuang token rotasi → ulasan ke-2/ketiga tetap lolos CSRF. */
                var tokenEl = findTokenField(form, d.token_name);
                if (d.new_token && tokenEl) { tokenEl.setAttribute('value', d.new_token); tokenEl.value = d.new_token; }
                toast(d.message || 'Tersimpan');
                if (d.status) {
                    form.reset();
                    if (d.new_token && tokenEl) tokenEl.value = d.new_token; // jaga-jaga pasca reset
                    if (ratingInput) ratingInput.value = 5;
                    paintStars(5); load();
                }
            }).catch(function () { toast('Gagal mengirim ulasan'); }).then(function () { if (sb) sb.disabled = false; });
        });
    }

    function boot3() { initZoom(); initQr(); initCopyCall(); initReader(); initReviews(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot3); else boot3();
})();