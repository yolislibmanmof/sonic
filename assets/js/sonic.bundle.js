/* SONIC bundle — generated 2026-11-12T02:18:47+00:00 */

;/* --- app_jquery.js --- */
/**
 * SONIC OPAC — jQuery Behaviors (FASE 1: kontrak inti SLiMS 9.8)
 * Bookmark & basket memakai endpoint resmi core; share URL detail; boot sekali/sesi; ESC.
 */
jQuery(function ($) {
    'use strict';

    function detailUrl(id) {
        return new URL('index.php?p=show_detail&id=' + encodeURIComponent(id), window.location.href).href;
    }
    function setBadge(count) {
        var $link = $('.sonic-navbar a[href*="sec=title_basket"]');
        var $b = $link.find('.sonic-badge-count');
        if (!$b.length) { $b = $('<span class="sonic-badge-count" id="count-basket"></span>').appendTo($link); }
        $b.text(count);
    }

    /* ===== 1. CITATION POPUP (colorbox) ===== */
    if ($.fn.colorbox) {
        $(document).on('click', '.openPopUp.citationLink', function (e) {
            e.preventDefault();
            $.colorbox({ iframe: true, href: $(this).attr('href'), width: '780px', height: '600px', maxWidth: '95%', className: 'sonic-colorbox' });
        });
    }

    /* ===== 2. BOOKMARK (core: INSERT IGNORE / delete_bookmark — toggle BENAR) ===== */
    $(document).on('click', '.bookMarkBook', function (e) {
        e.preventDefault();
        var $b = $(this), id = $b.data('id');
        var removing = $b.hasClass('active');
        var payload = { bookmark_id: id, callback: 'json' };
        if (removing) payload.delete_bookmark = true;
        $.post('index.php?p=member', payload)
            .done(function (res) {
                if (typeof res === 'string') {
                    try { res = JSON.parse(res); }
                    catch (err) { window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href); return; }
                }
                if (res && res.status) {
                    var on = !removing;
                    $('.bookMarkBook[data-id="' + id + '"]').toggleClass('active', on);
                    var $lbl = $('#label-' + id);
                    if ($lbl.length) $lbl.text(on ? ($b.data('label-on') || res.label || 'Bookmarked') : ($b.data('label-off') || 'Bookmark'));
                    if (window.toastr) toastr.success(res.message || (on ? 'Bookmark disimpan' : 'Bookmark dihapus'));
                } else if (window.toastr) { toastr.error((res && res.message) || 'Gagal menyimpan bookmark'); }
            })
            .fail(function () {
                window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href);
            });
    });

    /* ===== 3. ADD TO BASKET (core: POST biblio[] + callback=json) ===== */
    $(document).on('click', '.add-to-chart-button', function (e) {
        e.preventDefault();
        var id = $(this).data('biblio') || $(this).data('id');
        $.post('index.php?p=member', { biblio: [id], callback: 'json' })
            .done(function (data) {
                if (typeof data === 'string') {
                try { data = JSON.parse(data); }
                catch (err) { window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href); return; }
                }
                if (data && data.status) {
                    if (window.toastr) toastr.success(data.message || 'Ditambahkan ke basket');
                    if (typeof data.count !== 'undefined') setBadge(data.count);
                } else if (window.toastr) { toastr.error((data && data.message) || 'Gagal menambah ke basket'); }
            })
            .fail(function () {
                window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href);
            });
    });

    /* ===== 4. SHARE MODAL (URL detail per item, bukan URL halaman) ===== */
    $(document).on('click', '[data-target="#mediaSocialModal"]', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var title = $(this).data('title') || document.title;
        var url = id ? detailUrl(id) : window.location.href;
        var enc = encodeURIComponent;
        $('#mediaSocialModalBody').html(
            '<div style="display:grid;gap:.6rem;">' +
            '<a class="sonic-mini-btn" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=' + enc(url) + '"><i class="fab fa-facebook-square"></i> Facebook</a>' +
            '<a class="sonic-mini-btn" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=' + enc(title) + '&url=' + enc(url) + '"><i class="fab fa-twitter"></i> Twitter</a>' +
            '<a class="sonic-mini-btn" target="_blank" rel="noopener" href="https://wa.me/?text=' + enc(title + ' ' + url) + '"><i class="fab fa-whatsapp"></i> WhatsApp</a>' +
            '<a class="sonic-mini-btn" target="_blank" rel="noopener" href="https://t.me/share/url?url=' + enc(url) + '&text=' + enc(title) + '"><i class="fab fa-telegram"></i> Telegram</a>' +
            '<button type="button" class="sonic-mini-btn" id="sonicCopyLink"><i class="fas fa-link"></i> Copy Link</button>' +
            '</div>'
        );
        $('#mediaSocialModal').addClass('active');
        $('#sonicCopyLink').on('click', function () {
            var ta = document.createElement('textarea');
            ta.value = url; document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); ta.remove();
            if (window.toastr) toastr.success('Link disalin');
        });
    });

    /* ===== 5. TOASTR ===== */
    if (window.toastr) {
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 3500, newestOnTop: true };
    }

    /* ===== 6. BACK-TO-TOP ===== */
    var $top = $('<button class="sonic-totop" title="Kembali ke atas"><i class="fas fa-rocket"></i></button>').appendTo('body');
    $(window).on('scroll', function () { $top.toggleClass('show', $(window).scrollTop() > 400); });
    $top.on('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== 7. CTRL+K PALETTE ===== */
    var $pal = $(
        '<div class="sonic-palette" id="sonicPalette"><div class="sonic-palette-box"><i class="fas fa-search"></i>' +
        '<input type="text" id="sonicPaletteInput" placeholder="Ketik judul / penulis lalu Enter..." autocomplete="off"/><kbd>ESC</kbd></div></div>'
    ).appendTo('body');
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 'k') { e.preventDefault(); $pal.addClass('active'); $('#sonicPaletteInput').trigger('focus'); }
        if (e.key === 'Escape') {
            $pal.removeClass('active');
            $('.sonic-modal.active, .sonic-adv-modal.active').removeClass('active'); // ESC tutup modal
        }
    });
    $pal.on('click', function (e) { if (e.target === this) $pal.removeClass('active'); });
    $('#sonicPaletteInput').on('keydown', function (e) {
        if (e.key === 'Enter' && this.value.trim()) { window.location = 'index.php?keywords=' + encodeURIComponent(this.value.trim()) + '&search=search'; }
    });

    /* ===== 8. BOOT SCREEN — sekali per sesi ===== */
    function killBoot() { $('#sonicBoot').addClass('done'); setTimeout(function () { $('#sonicBoot').remove(); }, 700); }
    try {
        if (sessionStorage.getItem('sonicBooted')) { $('#sonicBoot').remove(); }
        else { sessionStorage.setItem('sonicBooted', '1'); $(window).on('load', killBoot); setTimeout(killBoot, 2500); }
    } catch (e) { $(window).on('load', killBoot); setTimeout(killBoot, 2500); }
});

;/* --- sonic.js --- */
/**
 * SONIC OPAC v2 — Hyperdrive Engine (FINAL)
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

    /* ===== 2. HERO PHOTO SLIDER (FINAL — stabil, ringan, tahan versi markup apa pun) ===== */
    var slides = document.querySelectorAll('.sonic-slide');
    if (slides.length > 0) {
        var dotsWrap = document.getElementById('sliderDots');
        var current = 0, timer = null;

        // Pengaman: isi background dari data-bg bila ada; biarkan bila sudah inline.
        function ensureBg(el) {
            if (!el) return;
            var bg = el.getAttribute('data-bg');
            if (bg && !el.style.backgroundImage) el.style.backgroundImage = "url('" + bg + "')";
        }
        slides.forEach(function (el) { ensureBg(el); });

        if (slides.length > 1 && dotsWrap) {
            slides.forEach(function (_, i) {
                var d = document.createElement('button');
                d.type = 'button';
                d.className = 'sonic-dot-btn' + (i === 0 ? ' active' : '');
                d.setAttribute('aria-label', 'Slide ' + (i + 1));
                d.addEventListener('click', function () { go(i); restart(); });
                dotsWrap.appendChild(d);
            });
        }
        var dots = dotsWrap ? dotsWrap.querySelectorAll('.sonic-dot-btn') : [];

        function go(i) {
            if (slides.length < 2) return;
            slides[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');
            current = (i + slides.length) % slides.length;
            ensureBg(slides[current]);
            slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');
        }
        function restart() {
            if (timer) { clearInterval(timer); timer = null; }
            if (document.hidden || reduceMotion || slides.length < 2) return;
            timer = setInterval(function () { go(current + 1); }, 6000);
        }

        var prev = document.getElementById('sliderPrev');
        var next = document.getElementById('sliderNext');
        if (prev) prev.addEventListener('click', function () { go(current - 1); restart(); });
        if (next) next.addEventListener('click', function () { go(current + 1); restart(); });

        var hero = document.getElementById('sonicSlider');
        if (hero) {
            hero.addEventListener('mouseenter', function () { if (timer) { clearInterval(timer); timer = null; } });
            hero.addEventListener('mouseleave', restart);
        }
        document.addEventListener('visibilitychange', restart);
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

    /* ===== 5. TITLE SCRAMBLE-DECODE EFFECT (hormati reduced-motion) ===== */
    var title = document.getElementById('sonicTitle');
    if (title) {
        var finalText = title.getAttribute('data-text') || title.textContent;
        if (reduceMotion) {
            title.textContent = finalText;
        } else {
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
    }

    /* ===== 6. SPOTLIGHT CARDS (rAF-throttled — halus, tanpa layout thrash) ===== */
    document.querySelectorAll('.sonic-book, .sonic-list-card').forEach(function (card) {
        var pending = false, mx = 0, my = 0;
        card.addEventListener('mousemove', function (e) {
            mx = e.clientX; my = e.clientY;
            if (pending) return;
            pending = true;
            requestAnimationFrame(function () {
                pending = false;
                var r = card.getBoundingClientRect();
                if (r.width > 0 && r.height > 0) {
                    card.style.setProperty('--mouse-x', ((mx - r.left) / r.width) * 100 + '%');
                    card.style.setProperty('--mouse-y', ((my - r.top) / r.height) * 100 + '%');
                }
            });
        }, { passive: true });
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

;/* --- sonic-marc.js --- */
/**
 * SONIC MARC VIEWER v3 (FINAL — BATCH 1) — ISO 2709 decoder BYTE-ACCURATE
 * Sumber: api/marc.php?id=X (application/marc, raw .mrc)
 * Patch: offset per-byte (UTF-8 aman), SONIC_CFG.base, cek HTTP status,
 *        abort request lama, ESC untuk menutup. Fitur lama utuh.
 */
(function () {
    'use strict';
    var RS = 0x1D, FS = 0x1E, SF = 0x1F;
    var TD = (typeof TextDecoder !== 'undefined') ? new TextDecoder('utf-8') : null;

    function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; }
    function latin1(u8, a, b) { var s = ''; for (var i = a; i < b && i < u8.length; i++) s += String.fromCharCode(u8[i]); return s; }
    function utf8(u8) {
        if (TD) return TD.decode(u8);
        var s = ''; for (var i = 0; i < u8.length; i++) s += String.fromCharCode(u8[i]);
        try { return decodeURIComponent(escape(s)); } catch (e) { return s; }
    }

    /* Parse record ISO 2709 dari Uint8Array — directory & offset dihitung per BYTE,
       sehingga judul/subfield beraksen (multibyte UTF-8) tidak menggeser field. */
    function parseISO2709(bytes) {
        var lines = [];
        if (!bytes || bytes.length < 24) return lines;
        var base = parseInt(latin1(bytes, 12, 17), 10);      // leader[12..16] = base address
        if (isNaN(base) || base <= 24 || base > bytes.length) return lines;
        var n = Math.floor((base - 1 - 24) / 12);            // directory: 12 byte/entry
        for (var i = 0; i < n; i++) {
            var o = 24 + i * 12;
            var tag = latin1(bytes, o, o + 3);
            var len = parseInt(latin1(bytes, o + 3, o + 7), 10);
            var start = parseInt(latin1(bytes, o + 7, o + 12), 10);
            if (isNaN(len) || isNaN(start) || len <= 0) continue;
            var f = bytes.subarray(base + start, Math.min(base + start + len, bytes.length));
            if (f.length && f[f.length - 1] === FS) f = f.subarray(0, f.length - 1);
            if (tag.indexOf('00') === 0) {                   // control field (001/003/005/008)
                lines.push({ tag: tag, ind: '', subs: [{ code: '', value: utf8(f) }] });
                continue;
            }
            var ind = latin1(f, 0, 2).replace(/ /g, '#');    // 2 indicator
            var subs = [], marks = [];
            for (var j = 2; j < f.length; j++) if (f[j] === SF) marks.push(j);
            for (var k = 0; k < marks.length; k++) {
                var s0 = marks[k], e0 = (k + 1 < marks.length) ? marks[k + 1] : f.length;
                if (e0 - s0 < 2) continue;
                subs.push({ code: String.fromCharCode(f[s0 + 1]), value: utf8(f.subarray(s0 + 2, e0)) });
            }
            lines.push({ tag: tag, ind: ind, subs: subs });
        }
        return lines;
    }

    function renderLines(lines) {
        if (!lines.length) return '<p class="sonic-marc-empty">// MARC stream kosong / tidak ter-parse</p>';
        return '<div class="marc-stream">' + lines.map(function (L, i) {
            var body;
            if (L.ind === '') {
                body = '<span class="marc-rest">' + esc(L.subs[0].value) + '</span>';
            } else {
                body = '<span class="marc-ind">' + esc(L.ind) + '</span><span class="marc-rest">' +
                    L.subs.map(function (s) { return '<span class="marc-sf">$' + esc(s.code) + '</span> ' + esc(s.value); }).join(' ') +
                    '</span>';
            }
            return '<div class="marc-line"><span class="marc-idx">' + String(i).padStart(2, '0') + '</span>' +
                '<span class="marc-tag">' + esc(L.tag) + '</span>' + body + '</div>';
        }).join('') + '</div>';
    }

    var ctl = null;
    function openMarc(url) {
        var modal = document.getElementById('sonicMarcModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'sonicMarcModal';
            modal.className = 'sonic-modal';
            modal.innerHTML =
                '<div class="sonic-terminal sonic-terminal-wide">' +
                '<div class="sonic-terminal-head"><span class="sonic-dot r"></span><span class="sonic-dot y"></span><span class="sonic-dot g"></span>' +
                '<span class="sonic-terminal-title">marc_decoder.exe — ISO 2709 stream</span>' +
                '<button type="button" class="sonic-terminal-close" data-close>&times;</button></div>' +
                '<div class="sonic-terminal-body"><div class="sonic-marc-toolbar"></div><div class="sonic-marc-out"></div></div>' +
                '</div>';
            document.body.appendChild(modal);
            modal.addEventListener('click', function (e) {
                if (e.target === modal || e.target.hasAttribute('data-close')) modal.classList.remove('active');
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') modal.classList.remove('active');
            });
        }
        modal.classList.add('active');
        var out = modal.querySelector('.sonic-marc-out');
        var bar = modal.querySelector('.sonic-marc-toolbar');
        out.innerHTML = '<span class="sonic-loading"></span> decoding ISO 2709 stream...';
        bar.innerHTML = '';

        if (ctl) { try { ctl.abort(); } catch (e) {} }
        ctl = (typeof AbortController !== 'undefined') ? new AbortController() : null;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: ctl ? ctl.signal : undefined })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.arrayBuffer(); })
            .then(function (buf) {
                var bytes = new Uint8Array(buf);
                var p = 0; while (p < bytes.length && bytes[p] <= 0x20) p++;
                if (bytes[p] === 0x3C) { out.innerHTML = '<p class="sonic-marc-empty">// server mengembalikan HTML, bukan stream MARC</p>'; return; }
                var end = -1;
                for (var i = 0; i < bytes.length; i++) if (bytes[i] === RS) { end = i; break; }
                var rec = end > 0 ? bytes.subarray(0, end + 1) : bytes;
                var lines = parseISO2709(rec);
                bar.innerHTML = '<span class="sonic-mini-btn" style="pointer-events:none;"><i class="fas fa-list-ol"></i> ' + lines.length + ' fields</span>' +
                    '<a class="sonic-mini-btn" href="' + esc(url) + '" download><i class="fas fa-download"></i> Download .mrc</a>';
                out.innerHTML = renderLines(lines);
            })
            .catch(function (e) {
                if (e && e.name === 'AbortError') return;
                out.innerHTML = '<p class="sonic-marc-empty">// gagal memuat MARC stream' + (e && e.message ? ' (' + esc(e.message) + ')' : '') + '</p>';
            });
    }

    function findBase() {
        if (window.SONIC_CFG && window.SONIC_CFG.base) return window.SONIC_CFG.base;
        var link = document.querySelector('link[href*="sonic-base.css"]');
        return link ? link.href.replace(/assets\/css\/sonic-base\.css.*$/, '') : 'template/sonic/';
    }

    window.openMarcViewer = openMarc;
    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('.sonic-marc-trigger') : null;
        if (!t) return;
        e.preventDefault();
        var href = t.getAttribute('href') || '';
        var m = href.match(/[?&]id=(\d+)/);
        var url = m ? (findBase() + 'api/marc.php?id=' + m[1]) : href;
        openMarc(url);
    });
})();

;/* --- sonic-facet.js --- */
/**
 * SONIC FACET — Fase 2: multi-select + sort, satu kontrak `filter` JSON (core).
 */
jQuery(function ($) {
    'use strict';
    var FIELDS = ['title', 'author', 'subject', 'isbn', 'callnumber', 'publishyear'];
    var MULTI = ['gmd', 'location', 'colltype'];

    function readState() {
        var p = new URLSearchParams(window.location.search);
        var f = {};
        try { f = JSON.parse(p.get('filter') || '{}') || {}; } catch (e) { f = {}; }
        var s = { keywords: p.get('keywords') || '', sort: f.sort || '', publishyear: f.publishyear || p.get('publishyear') || '' };
        FIELDS.forEach(function (k) { if (k !== 'publishyear') s[k] = f[k] || p.get(k) || ''; });
        MULTI.forEach(function (k) {
            var v = f[k];
            if (v == null) v = p.getAll(k + '[]').length ? p.getAll(k + '[]') : (p.get(k) ? [p.get(k)] : []);
            if (!Array.isArray(v)) v = [v];
            s[k] = v.filter(function (x) { return x !== '' && x !== '0'; }).map(String);
        });
        return s;
    }

    function buildUrl(s) {
        var filter = {};
        FIELDS.forEach(function (k) { if (s[k]) filter[k] = s[k]; });
        MULTI.forEach(function (k) { if (s[k] && s[k].length) filter[k] = s[k]; });
        if (s.sort) filter.sort = s.sort;
        var p = new URLSearchParams();
        p.set('search', 'search');
        if (s.keywords) p.set('keywords', s.keywords);
        if (Object.keys(filter).length) p.set('filter', JSON.stringify(filter));
        return 'index.php?' + p.toString();
    }

    function collect() {
        var s = readState();
        $('#sonicFacetForm input[type="checkbox"]').each(function () {
            var name = (this.name || '').replace('[]', '');
            if (MULTI.indexOf(name) < 0) return;
            s[name] = s[name] || [];
            if (this.checked) { if (s[name].indexOf(this.value) < 0) s[name].push(this.value); }
            else { s[name] = s[name].filter(function (x) { return x !== this.value; }, this); }
        });
        var py = $('#sonicFacetForm input[name="publishyear"]').val();
        s.publishyear = (py || '').trim();
        var sort = $('#sonicSort').val();
        s.sort = sort || '';
        return s;
    }

    function apply() { window.location.href = buildUrl(collect()); }

    // Sinkronkan DOM dari URL (checkbox, year, sort)
    var st = readState();
    MULTI.forEach(function (k) {
        $('#sonicFacetForm input[name="' + k + '[]"]').each(function () {
            this.checked = st[k].indexOf(this.value) >= 0;
            $(this).closest('.sonic-facet-check').toggleClass('on', this.checked);
        });
    });
    $('#sonicFacetForm input[name="publishyear"]').val(st.publishyear);
    if ($('#sonicSort').length) $('#sonicSort').val(st.sort);

    // Interaksi
    $(document).on('change', '#sonicFacetForm input[type="checkbox"]', function () {
        $(this).closest('.sonic-facet-check').toggleClass('on', this.checked);
        apply();
    });
    $(document).on('change', '#sonicSort', apply);
    $(document).on('submit', '#sonicFacetForm', function (e) { e.preventDefault(); apply(); });
    $(document).on('keydown', '#sonicFacetForm input[name="publishyear"]', function (e) { if (e.key === 'Enter') { e.preventDefault(); apply(); } });

    // Collapse mobile
    $(document).on('click', '#sonicFacetToggle', function () { $('#sonicFacet').toggleClass('collapsed'); });
    if ($(window).width() < 900) $('#sonicFacet').addClass('collapsed');
});

;/* --- sonic-member.js --- */
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

;/* --- sonic-footer.js --- */
/**
 * SONIC FOOTER — Interactive Elements (FINAL)
 * Ringan: counter pakai requestAnimationFrame (bukan setInterval 16ms),
 * guard bila footer/IO tidak ada, hormati prefers-reduced-motion.
 */
jQuery(function ($) {
    'use strict';

    var statsAnimated = false;
    var $stats = $('.sonic-stat-number');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function animateStats() {
        if (statsAnimated) return;
        statsAnimated = true;
        $stats.each(function () {
            var el = this;
            var target = parseInt(el.getAttribute('data-target'), 10) || 0;
            if (reduceMotion || target <= 0) { el.textContent = target.toLocaleString(); return; }
            var start = null, duration = 1600;
            function step(ts) {
                if (start === null) start = ts;
                var p = Math.min(1, (ts - start) / duration);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.floor(target * eased).toLocaleString();
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }

    var $footer = $('#sonicFooter');
    if ($footer.length && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { animateStats(); observer.disconnect(); }
            });
        }, { threshold: 0.25 });
        observer.observe($footer[0]);
    } else {
        animateStats();
    }

    // Smooth scroll hanya untuk anchor yang benar-benar ada
    $('.sonic-footer-links a').on('click', function (e) {
        var href = $(this).attr('href');
        if (href && href.charAt(0) === '#') {
            var $target = $(href);
            if ($target.length) {
                e.preventDefault();
                $('html, body').animate({ scrollTop: $target.offset().top - 100 }, 600);
            }
        }
    });

    $('.sonic-search-box input').on('focus', function () {
        $(this).closest('.sonic-search-box').addClass('focused');
    }).on('blur', function () {
        $(this).closest('.sonic-search-box').removeClass('focused');
    });
});

;/* --- sonic-librarian.js --- */
/**
 * SONIC LIBRARIAN — Profile Card Enhancement
 * Auto-detect markup bawaan SLiMS dan inject icon Font Awesome
 */
jQuery(function($) {
    'use strict';
    
    var $grid = $('#sonicLibrarianGrid');
    if (!$grid.length) return;
    
    // ===== 1. Inject icon ke setiap field info =====
    $grid.find('li, [class*="field"], p').each(function() {
        var text = $(this).text().toLowerCase();
        var $icon = $('<i class="fas fa-user"></i>');
        
        if (text.includes('nama') || text.includes('name')) {
            $icon = $('<i class="fas fa-user"></i>');
        } else if (text.includes('jabatan') || text.includes('position')) {
            $icon = $('<i class="fas fa-briefcase"></i>');
        } else if (text.includes('surel') || text.includes('email') || text.includes('@')) {
            $icon = $('<i class="fas fa-envelope"></i>');
        } else if (text.includes('media sosial') || text.includes('social')) {
            $icon = $('<i class="fas fa-share-alt"></i>');
        } else if (text.includes('telepon') || text.includes('phone') || text.includes('hp')) {
            $icon = $('<i class="fas fa-phone"></i>');
        } else if (text.includes('alamat') || text.includes('address')) {
            $icon = $('<i class="fas fa-map-marker-alt"></i>');
        } else if (text.includes('biografi') || text.includes('bio')) {
            $icon = $('<i class="fas fa-file-alt"></i>');
        }
        
        // Inject icon di depan text (jika belum ada icon)
        if (!$(this).find('i.fas, i.fab').length) {
            $(this).prepend('<span class="sonic-field-icon">' + $icon.prop('outerHTML') + '</span>');
        }
    });
    
    // ===== 2. Style social media links dengan icon yang sesuai =====
    $grid.find('a').each(function() {
        var href = $(this).attr('href') || '';
        var $link = $(this);
        
        if (href.includes('facebook') || href.includes('fb.com')) {
            $link.html('<i class="fab fa-facebook-f"></i>');
        } else if (href.includes('twitter') || href.includes('x.com')) {
            $link.html('<i class="fab fa-twitter"></i>');
        } else if (href.includes('instagram')) {
            $link.html('<i class="fab fa-instagram"></i>');
        } else if (href.includes('linkedin')) {
            $link.html('<i class="fab fa-linkedin-in"></i>');
        } else if (href.includes('youtube')) {
            $link.html('<i class="fab fa-youtube"></i>');
        } else if (href.includes('tiktok')) {
            $link.html('<i class="fab fa-tiktok"></i>');
        } else if (href.includes('wa.me') || href.includes('whatsapp')) {
            $link.html('<i class="fab fa-whatsapp"></i>');
        } else if (href.includes('t.me') || href.includes('telegram')) {
            $link.html('<i class="fab fa-telegram-plane"></i>');
        } else if (href.startsWith('mailto:')) {
            $link.html('<i class="fas fa-envelope"></i> ' + href.replace('mailto:', ''));
        } else if (href.startsWith('http')) {
            $link.html('<i class="fas fa-globe"></i> Website');
        }
    });
    
    // ===== 3. Tambahkan efek tilt 3D pada card =====
    $grid.find('> div').each(function() {
        var $card = $(this);
        
        $card.on('mousemove', function(e) {
            var rect = this.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width - 0.5;
            var y = (e.clientY - rect.top) / rect.height - 0.5;
            
            $card.css('transform', 'perspective(1000px) rotateY(' + (x * 6) + 'deg) rotateX(' + (-y * 6) + 'deg) translateY(-8px)');
        });
        
        $card.on('mouseleave', function() {
            $card.css('transform', '');
        });
    });
    
    // ===== 4. Count-up animation untuk jumlah pustakawan =====
    var count = $grid.find('> div').length;
    if (count > 0) {
        var $header = $('.sonic-librarian-header span');
        $header.text(count + ' ' + $header.text());
    }
    
    console.log('%c SONIC Librarian Cards Enhanced: ' + count + ' profiles', 'color: #00f0ff; font-size: 14px; font-weight: bold;');
});

;/* --- sonic-info.js --- */
/**
 * SONIC INFO DECK — Auto-enhance Library Information page
 * Inject icons ke heading, wrap contact info jadi grid cards
 */
jQuery(function($) {
    'use strict';
    
    var $deck = $('#sonicInfoDeck');
    if (!$deck.length) return;
    
    var $inner = $deck.find('.sonic-info-deck-inner');
    
    // ===== 1. Inject icons ke setiap heading berdasarkan keyword =====
    $inner.find('h1, h2, h3').each(function() {
        var text = $(this).text().toLowerCase();
        var iconClass = 'fas fa-info-circle';
        
        if (text.includes('contact') || text.includes('kontak')) {
            iconClass = 'fas fa-address-card';
        } else if (text.includes('opening') || text.includes('hours') || text.includes('jam')) {
            iconClass = 'far fa-clock';
        } else if (text.includes('collection') || text.includes('koleksi')) {
            iconClass = 'fas fa-book-open';
        } else if (text.includes('member') || text.includes('anggota')) {
            iconClass = 'fas fa-id-card';
        } else if (text.includes('rule') || text.includes('aturan') || text.includes('regulation')) {
            iconClass = 'fas fa-gavel';
        } else if (text.includes('service') || text.includes('layanan')) {
            iconClass = 'fas fa-concierge-bell';
        } else if (text.includes('fee') || text.includes('biaya') || text.includes('denda')) {
            iconClass = 'fas fa-coins';
        } else if (text.includes('location') || text.includes('lokasi') || text.includes('map')) {
            iconClass = 'fas fa-map-marked-alt';
        } else if (text.includes('history') || text.includes('sejarah')) {
            iconClass = 'fas fa-landmark';
        } else if (text.includes('vision') || text.includes('visi') || text.includes('misi')) {
            iconClass = 'fas fa-eye';
        }
        
        // Inject icon sebelum text heading
        if (!$(this).find('.sonic-section-icon').length) {
            $(this).prepend('<span class="sonic-section-icon"><i class="' + iconClass + '"></i></span>');
        }
    });
    
    // ===== 2. Wrap Contact Information jadi grid cards =====
    // Cari heading "Contact Information" lalu bungkus isinya
    $inner.find('h1, h2, h3').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.includes('contact') || text.includes('kontak')) {
            var $heading = $(this);
            var $cards = $();
            var $next = $heading.next();
            
            // Kumpulkan semua elemen sampai heading berikutnya
            while ($next.length && !$next.is('h1, h2, h3')) {
                $cards = $cards.add($next);
                $next = $next.next();
            }
            
            if ($cards.length > 0) {
                $cards.wrapAll('<div class="sonic-contact-grid"></div>');
            }
        }
    });
    
    // ===== 3. Style individual contact fields jadi cards =====
    $inner.find('.sonic-contact-grid p').each(function() {
        var $p = $(this);
        var text = $p.text().toLowerCase();
        var icon = 'fas fa-info-circle';
        
        if (text.includes('address') || text.includes('alamat')) {
            icon = 'fas fa-map-marker-alt';
        } else if (text.includes('phone') || text.includes('telepon') || text.includes('nomor')) {
            icon = 'fas fa-phone-alt';
        } else if (text.includes('fax')) {
            icon = 'fas fa-fax';
        } else if (text.includes('email') || text.includes('surel') || text.includes('@')) {
            icon = 'fas fa-envelope';
        } else if (text.includes('web') || text.includes('website') || text.includes('url')) {
            icon = 'fas fa-globe';
        }
        
        // Inject icon ke strong label
        $p.find('strong, b').each(function() {
            if (!$(this).find('i').length) {
                $(this).prepend('<i class="' + icon + '" style="margin-right:0.5rem;"></i>');
            }
        });
    });
    
    // ===== 4. Wrap Opening Hours jadi timeline =====
    $inner.find('h1, h2, h3').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.includes('opening') || text.includes('hours') || text.includes('jam')) {
            var $heading = $(this);
            var $hours = $();
            var $next = $heading.next();
            
            while ($next.length && !$next.is('h1, h2, h3')) {
                $hours = $hours.add($next);
                $next = $next.next();
            }
            
            if ($hours.length > 0) {
                $hours.wrapAll('<div class="sonic-hours-timeline"></div>');
            }
        }
    });
    
    // ===== 5. Inject icon ke hari di Opening Hours =====
    $inner.find('.sonic-hours-timeline p strong, .sonic-hours-timeline p b').each(function() {
        var text = $(this).text().toLowerCase();
        var icon = 'far fa-calendar';
        
        if (text.includes('monday') || text.includes('senin')) icon = 'fas fa-calendar-day';
        else if (text.includes('saturday') || text.includes('sabtu')) icon = 'fas fa-calendar-week';
        else if (text.includes('sunday') || text.includes('minggu') || text.includes('ahad')) icon = 'fas fa-calendar-check';
        else if (text.includes('friday') || text.includes('jumat')) icon = 'fas fa-mosque';
        
        if (!$(this).find('i').length) {
            $(this).prepend('<i class="' + icon + '" style="margin-right:0.5rem;"></i>');
        }
    });
    
    // ===== 6. Collections & Membership cards =====
    $inner.find('h1, h2, h3').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.includes('collection') || text.includes('koleksi') || 
            text.includes('member') || text.includes('anggota')) {
            var $heading = $(this);
            var $next = $heading.next('p');
            if ($next.length && !$next.find('.sonic-info-card').length) {
                $next.addClass('sonic-info-card');
            }
        }
    });
    
    console.log('%c SONIC Info Deck Enhanced', 'color: #00f0ff; font-size: 14px; font-weight: bold;');
});

;/* --- sonic-particles.js --- */
/**
 * SONIC OPAC — Floating Particles (configurable via Kustomisasi)
 * window.SONIC_CFG.particle: books | stars | bolts | mixed | none
 */
(function () {
    'use strict';
    var CFG = window.SONIC_CFG || {};
    var PTYPE = CFG.particle || 'books';
    if (PTYPE === 'none') return;

    var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ===== LAYER CANVAS ===== */
    var canvas = document.createElement('canvas');
    canvas.id = 'sonicParticles';
    canvas.setAttribute('aria-hidden', 'true');
    document.body.insertBefore(canvas, document.body.firstChild);
    var ctx = canvas.getContext('2d');

    var PALETTE = {
        books: ['#00f0ff', '#ff00aa', '#7a5cff', '#e6f7ff'],
        stars: ['#ffd700', '#00f0ff', '#ffffff', '#ff00aa'],
        bolts: ['#00f0ff', '#ffe600', '#ff00aa', '#e6f7ff'],
        mixed: ['#00f0ff', '#ff00aa', '#ffd700', '#7a5cff']
    }[PTYPE] || ['#00f0ff', '#ff00aa', '#7a5cff', '#e6f7ff'];
    var TYPES = {
        books: ['page', 'page', 'page', 'book', 'bookopen'],
        stars: ['star', 'star', 'star', 'spark'],
        bolts: ['bolt', 'bolt', 'spark'],
        mixed: ['page', 'book', 'star', 'bolt', 'bookopen']
    }[PTYPE] || ['page', 'page', 'book'];
    var SIZES = [16, 22, 30, 40];

    function rgba(hex, a) {
        var n = parseInt(hex.slice(1), 16);
        return 'rgba(' + (n >> 16 & 255) + ',' + (n >> 8 & 255) + ',' + (n & 255) + ',' + a + ')';
    }

    /* ===== SPRITE PRE-RENDER (glow baked-in => performa tinggi) ===== */
    function makeSprite(type, color, s) {
        var dim = Math.ceil(s * 2);
        var c = document.createElement('canvas');
        c.width = dim; c.height = dim;
        var g = c.getContext('2d');
        g.translate(dim / 2, dim / 2);
        g.strokeStyle = color; g.fillStyle = color;
        g.lineJoin = 'round'; g.lineCap = 'round';
        g.shadowColor = color; g.shadowBlur = s * 0.5;
        g.lineWidth = Math.max(1.1, s * 0.07);

        if (type === 'page') {
            var w = s * 0.78, h = s * 1.0, f = s * 0.26;
            g.beginPath();
            g.moveTo(-w / 2, -h / 2); g.lineTo(w / 2 - f, -h / 2); g.lineTo(w / 2, -h / 2 + f);
            g.lineTo(w / 2, h / 2); g.lineTo(-w / 2, h / 2); g.closePath();
            g.save(); g.globalAlpha = 0.12; g.fill(); g.restore();
            g.stroke();
            g.beginPath();
            g.moveTo(w / 2 - f, -h / 2); g.lineTo(w / 2 - f, -h / 2 + f); g.lineTo(w / 2, -h / 2 + f);
            g.stroke();
            g.save(); g.shadowBlur = s * 0.2; g.globalAlpha = 0.8; g.lineWidth = Math.max(1, s * 0.05);
            for (var i = 0; i < 3; i++) {
                var ly = -h / 2 + f + s * 0.22 + i * s * 0.2;
                g.beginPath();
                g.moveTo(-w / 2 + s * 0.14, ly);
                g.lineTo(w / 2 - s * 0.14 - (i === 2 ? s * 0.22 : 0), ly);
                g.stroke();
            }
            g.restore();
        } else if (type === 'book') {
            var bw = s * 0.82, bh = s * 0.62;
            g.beginPath();
            if (g.roundRect) g.roundRect(-bw / 2, -bh / 2, bw, bh, s * 0.08);
            else g.rect(-bw / 2, -bh / 2, bw, bh);
            g.save(); g.globalAlpha = 0.12; g.fill(); g.restore();
            g.stroke();
            g.beginPath(); g.moveTo(-bw / 2 + s * 0.16, -bh / 2); g.lineTo(-bw / 2 + s * 0.16, bh / 2); g.stroke();
            g.save(); g.globalAlpha = 0.8; g.shadowBlur = s * 0.2;
            g.beginPath(); g.moveTo(-bw / 2 + s * 0.3, -bh / 2 + s * 0.16); g.lineTo(bw / 2 - s * 0.14, -bh / 2 + s * 0.16); g.stroke();
            g.restore();
        } else if (type === 'bookopen') {
            var ow = s * 0.5, oh = s * 0.42;
            g.beginPath();
            g.moveTo(0, -oh * 0.35);
            g.quadraticCurveTo(-ow * 0.55, -oh, -ow, -oh * 0.75);
            g.lineTo(-ow, oh * 0.55);
            g.quadraticCurveTo(-ow * 0.5, oh * 0.3, 0, oh * 0.65);
            g.quadraticCurveTo(ow * 0.5, oh * 0.3, ow, oh * 0.55);
            g.lineTo(ow, -oh * 0.75);
            g.quadraticCurveTo(ow * 0.55, -oh, 0, -oh * 0.35);
            g.closePath();
            g.save(); g.globalAlpha = 0.12; g.fill(); g.restore();
            g.stroke();
            g.beginPath(); g.moveTo(0, -oh * 0.35); g.lineTo(0, oh * 0.65); g.stroke();
        } else if (type === 'star') {
            var R = s * 0.5, r2 = s * 0.22, pts = [];
            for (var k = 0; k < 10; k++) {
                var ang = -Math.PI / 2 + k * Math.PI / 5, rad = (k % 2 === 0) ? R : r2;
                pts.push([Math.cos(ang) * rad, Math.sin(ang) * rad]);
            }
            g.beginPath(); g.moveTo(pts[0][0], pts[0][1]);
            for (var k2 = 1; k2 < 10; k2++) g.lineTo(pts[k2][0], pts[k2][1]);
            g.closePath();
            g.save(); g.globalAlpha = 0.15; g.fill(); g.restore(); g.stroke();
        } else if (type === 'bolt') {
            var u = s / 20;
            g.beginPath();
            g.moveTo(2 * u, -10 * u); g.lineTo(-4 * u, 1 * u); g.lineTo(0, 1 * u);
            g.lineTo(-2 * u, 10 * u); g.lineTo(4 * u, -1 * u); g.lineTo(0, -1 * u); g.closePath();
            g.save(); g.globalAlpha = 0.15; g.fill(); g.restore(); g.stroke();
        } else { /* spark */
            var L = s * 0.5;
            g.beginPath(); g.moveTo(0, -L); g.lineTo(0, L); g.moveTo(-L, 0); g.lineTo(L, 0); g.stroke();
            g.save(); g.globalAlpha = 0.6;
            g.beginPath(); g.moveTo(-L * 0.5, -L * 0.5); g.lineTo(L * 0.5, L * 0.5);
            g.moveTo(L * 0.5, -L * 0.5); g.lineTo(-L * 0.5, L * 0.5); g.stroke();
            g.restore();
        }
        return c;
    }

    function makeGlint(color, s) {
        var dim = Math.ceil(s * 4), c = document.createElement('canvas');
        c.width = c.height = dim;
        var g = c.getContext('2d'), r = dim / 2;
        var grad = g.createRadialGradient(r, r, 0, r, r, r);
        grad.addColorStop(0, rgba(color, 0.9));
        grad.addColorStop(0.3, rgba(color, 0.35));
        grad.addColorStop(1, rgba(color, 0));
        g.fillStyle = grad;
        g.fillRect(0, 0, dim, dim);
        return c;
    }

    /* ===== STATE ===== */
    var W = 0, H = 0, DPR = 1, parts = [], glints = [];
    var scrollY = window.scrollY || 0;
    var mx = 0, my = 0, tmx = 0, tmy = 0;

    function targetCount() { return Math.max(14, Math.min(40, Math.round((W * H) / 42000))); }

    function makePart() {
        var z = 0.45 + Math.random() * 0.75;
        var si = z < 0.68 ? 0 : (z < 0.88 ? 1 : (z < 1.05 ? 2 : 3));
        var ci = Math.random() < 0.55 ? 0 : (Math.random() < 0.6 ? 3 : (Math.random() < 0.5 ? 1 : 2));
        return {
            sp: makeSprite(TYPES[Math.floor(Math.random() * TYPES.length)], PALETTE[ci], SIZES[si]),
            x: Math.random() * W, y: Math.random() * (H + 160) - 80, z: z,
            vy: 6 + Math.random() * 14,
            swA: 12 + Math.random() * 30, swF: 0.25 + Math.random() * 0.5,
            flF: 0.5 + Math.random() * 0.9,
            ph: Math.random() * Math.PI * 2, ph2: Math.random() * Math.PI * 2,
            rot: (Math.random() - 0.5) * 0.9, vr: (Math.random() - 0.5) * 0.25,
            al: 0.10 + z * 0.14
        };
    }

    function makeGlintP() {
        var ci = Math.random() < 0.6 ? 0 : 1;
        return {
            sp: makeGlint(PALETTE[ci], 3 + Math.random() * 3),
            x: Math.random() * W, y: Math.random() * H,
            z: 0.4 + Math.random() * 0.8,
            vy: 4 + Math.random() * 8,
            ph: Math.random() * 6.28,
            tw: 0.6 + Math.random() * 1.6
        };
    }

    function resize() {
        DPR = Math.min(2, window.devicePixelRatio || 1);
        W = window.innerWidth; H = window.innerHeight;
        canvas.width = W * DPR; canvas.height = H * DPR;
        canvas.style.width = W + 'px'; canvas.style.height = H + 'px';
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
        var n = targetCount(), gn = Math.round(n * 0.7);
        while (parts.length < n) parts.push(makePart());
        if (parts.length > n) parts.length = n;
        while (glints.length < gn) glints.push(makeGlintP());
        if (glints.length > gn) glints.length = gn;
    }

    /* ===== RENDER LOOP ===== */
    var last = 0;
    function draw(t) {
        ctx.clearRect(0, 0, W, H);
        var i, p, m = 90, span = H + 2 * m;
        for (i = 0; i < parts.length; i++) {
            p = parts[i];
            var yy = p.y - scrollY * 0.10 * p.z;
            yy = (((yy + m) % span) + span) % span - m;
            var xx = p.x + Math.sin(t * p.swF + p.ph) * p.swA + mx * 14 * p.z;
            yy = yy + my * 10 * p.z;
            var sc = 0.4 + 0.6 * Math.abs(Math.sin(t * p.flF + p.ph2));
            var a = p.al * (0.8 + 0.2 * Math.sin(t * 0.9 + p.ph));
            ctx.save();
            ctx.translate(xx, yy);
            ctx.rotate(p.rot + Math.sin(t * p.flF * 0.7 + p.ph) * 0.25);
            ctx.scale(sc, 1);
            ctx.globalAlpha = a;
            ctx.drawImage(p.sp, -p.sp.width / 2, -p.sp.height / 2);
            ctx.restore();
        }
        for (i = 0; i < glints.length; i++) {
            p = glints[i];
            var gy = (((p.y - scrollY * 0.06 * p.z + m) % span) + span) % span - m;
            var tw = Math.sin(t * p.tw + p.ph);
            ctx.globalAlpha = 0.18 + 0.4 * Math.max(0, tw);
            var gs = 0.7 + 0.3 * tw;
            ctx.drawImage(p.sp, p.x + mx * 8 * p.z - p.sp.width / 2 * gs, gy - p.sp.height / 2 * gs, p.sp.width * gs, p.sp.height * gs);
        }
        ctx.globalAlpha = 1;
    }

    function frame(ts) {
        var t = ts / 1000;
        var dt = Math.min(0.05, (t - last) || 0.016);
        last = t;
        mx += (tmx - mx) * 0.04; my += (tmy - my) * 0.04;
        for (var i = 0; i < parts.length; i++) {
            parts[i].y -= parts[i].vy * parts[i].z * dt;
            parts[i].rot += parts[i].vr * dt;
            if (parts[i].y < -90) { parts[i].y = H + 90; parts[i].x = Math.random() * W; }
        }
        for (var j = 0; j < glints.length; j++) {
            glints[j].y -= glints[j].vy * glints[j].z * dt;
            if (glints[j].y < -40) { glints[j].y = H + 40; glints[j].x = Math.random() * W; }
        }
        draw(t);
        if (!document.hidden) requestAnimationFrame(frame);
    }
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !REDUCED) { last = 0; requestAnimationFrame(frame); }
    });

    /* ===== EVENTS ===== */
    window.addEventListener('scroll', function () { scrollY = window.scrollY || 0; }, { passive: true });
    window.addEventListener('mousemove', function (e) {
        tmx = (e.clientX / W - 0.5) * 2; tmy = (e.clientY / H - 0.5) * 2;
    }, { passive: true });
    window.addEventListener('resize', resize);

    function start() {
        resize();
        canvas.classList.add('on');
        if (REDUCED) { draw(1); return; }
        requestAnimationFrame(frame);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();

;/* --- sonic-detail.js --- */
/**
 * SONIC OPAC — Detail Record Revolution (FINAL — BATCH 1)
 * Modul 1: 3D Book, Tabs, Citations+Export, Cover, Micro-interactions.
 * Modul 2 (Fase 3): Zoom, QR, Reader, Copy Call, Reviews.
 * Patch: token ulasan (new_token), guard respons, reset bintang, anti double-submit,
 *        toast timer, clipboard fallback, ESC modal, hook QR lokal (Batch 3).
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

/* ===== MODUL 2 — FASE 3: Zoom, QR, Reader, Copy Call, Reviews (FINAL BATCH 1) ===== */
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
            /* Hook QR lokal: aktif otomatis bila lib qrcode-generator (Batch 3) tersedia.
               Sebelum itu, fallback persis perilaku lama (api.qrserver.com). */
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
            fetch(base() + 'api/review.php?biblio_id=' + encodeURIComponent(id)).then(function (r) { return r.json(); }).then(function (d) {
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
                /* FIX F-2: Volnix me-rotasi token setelah validasi — sinkronkan token baru ke form */
                if (d.new_token) {
                    var t = form.querySelector('input[name="' + (d.token_name || '_csrf_token_645a83a41868941e4692aa31e7235f2') + '"]');
                    if (t) t.value = d.new_token;
                }
                toast(d.message || 'Tersimpan');
                if (d.status) { form.reset(); if (ratingInput) ratingInput.value = 5; paintStars(5); load(); }
            }).catch(function () { toast('Gagal mengirim ulasan'); }).then(function () { if (sb) sb.disabled = false; });
        });
    }

    function boot3() { initZoom(); initQr(); initCopyCall(); initReader(); initReviews(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot3); else boot3();
})();

;/* --- sonic-suggest.js --- */
/**
 * SONIC OPAC — Smart Autocomplete (Search Experience Upgrade, Fase A)
 * Dropdown saran hidup: judul / pengarang / subjek.
 * Keyboard: ArrowUp/Down navigasi, Enter pilih, Esc tutup.
 */
(function () {
    'use strict';

    /* Endpoint ditemukan otomatis dari URL stylesheet sonic-base.css */
    function findEndpoint() {
        var link = document.querySelector('link[href*="sonic-base.css"]');
        if (link) return link.href.replace(/assets\/css\/sonic-base\.css.*$/, '') + 'api/suggest.php';
        return 'template/sonic/api/suggest.php';
    }
    var ENDPOINT = findEndpoint();

    var ICONS = { title: 'fa-book', author: 'fa-user-edit', subject: 'fa-tags' };
    var _tagAuthor = document.querySelector('[data-search-mode="author"]');
    var _isId = _tagAuthor ? /pengarang/i.test(_tagAuthor.textContent) : false;
    var LABEL = _isId
        ? { title: 'Judul', author: 'Pengarang', subject: 'Subjek' }
        : { title: 'Title', author: 'Author', subject: 'Subject' };
    var MODE  = { title: 'keywords', author: 'author', subject: 'subject' };

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function highlight(label, q) {
        var safe = escapeHtml(label);
        var idx = label.toLowerCase().indexOf(q.toLowerCase());
        if (idx < 0) return safe;
        var before = escapeHtml(label.slice(0, idx));
        var match = escapeHtml(label.slice(idx, idx + q.length));
        var after = escapeHtml(label.slice(idx + q.length));
        return before + '<mark>' + match + '</mark>' + after;
    }

    function attach(input) {
        if (!input || input.dataset.sonicSuggest) return;
        input.dataset.sonicSuggest = '1';

        var wrap = input.closest('.sonic-input-wrap') || input.closest('.sonic-search-box') || input.parentElement;
        var box = document.createElement('div');
        box.className = 'sonic-suggest';
        wrap.appendChild(box);

        var items = [], sel = -1, ctrl = null, timer = null;

        function close() { box.classList.remove('open', 'loading'); sel = -1; items = []; }

        function render(q) {
            if (!items.length) { close(); return; }
            box.innerHTML = '';
            items.forEach(function (it, i) {
                var el = document.createElement('div');
                el.className = 'sonic-suggest-item' + (i === sel ? ' sel' : '');
                el.innerHTML = '<i class="fas ' + (ICONS[it.t] || 'fa-book') + '"></i>' +
                               '<span class="s-label">' + highlight(it.v, q) + '</span>' +
                               '<span class="s-type">' + LABEL[it.t] + '</span>';
                el.addEventListener('mousedown', function (e) { e.preventDefault(); pick(it); });
                box.appendChild(el);
            });
            box.classList.add('open');
        }

        function pick(it) {
            input.value = it.v;
            input.name = MODE[it.t] || 'keywords';
            /* sinkronkan chip mode di hero bila ada */
            var tags = document.querySelectorAll('[data-search-mode]');
            if (tags.length) {
                tags.forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-search-mode') === input.name); });
            }
            close();
            var form = input.closest('form');
            if (form) form.submit();
        }

        function fetchSuggest(q) {
            if (ctrl) ctrl.abort();
            ctrl = new AbortController();
            box.classList.add('loading');
            fetch(ENDPOINT + '?q=' + encodeURIComponent(q), { signal: ctrl.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    box.classList.remove('loading');
                    items = Array.isArray(data) ? data : [];
                    sel = -1;
                    render(q);
                })
                .catch(function () { box.classList.remove('loading'); });
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 2) { close(); return; }
            timer = setTimeout(function () { fetchSuggest(q); }, 220);
        });

        input.addEventListener('keydown', function (e) {
            if (!box.classList.contains('open')) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!items.length) return;
                sel = e.key === 'ArrowDown' ? (sel + 1) % items.length : (sel - 1 + items.length) % items.length;
                render(input.value.trim());
            } else if (e.key === 'Enter' && sel >= 0) {
                e.preventDefault();
                pick(items[sel]);
            } else if (e.key === 'Escape') {
                close();
            }
        });

        input.addEventListener('blur', function () { setTimeout(close, 140); });
        input.addEventListener('focus', function () {
            var q = input.value.trim();
            if (q.length >= 2 && items.length) render(q);
        });
    }

    function init() {
        attach(document.getElementById('sonic-keywords'));
        document.querySelectorAll('.sonic-footer-search input[name="keywords"]').forEach(attach);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    console.log('%c🔮 SONIC Smart Autocomplete: ACTIVE', 'color:#00f0ff;font-size:14px;font-weight:700;');
})();

;/* --- sonic-result.js --- */
/**
 * SONIC OPAC — Search Experience (Fase 2 FINAL)
 * Summary bar, stagger, empty-state, clear per grup facet, badge jumlah,
 * saved searches, ekspor RSS/JSON-LD/XML. Sort ditangani sonic-facet.js.
 */
(function () {
    'use strict';

    function qs(s) { return document.querySelector(s); }
    function qsa(s) { return Array.prototype.slice.call(document.querySelectorAll(s)); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function findBase() {
        var link = document.querySelector('link[href*="sonic-base.css"]');
        return link ? link.href.replace(/assets\/css\/sonic-base\.css.*$/, '') : 'template/sonic/';
    }
    function readFilter() {
        try { return JSON.parse(new URLSearchParams(window.location.search).get('filter') || '{}') || {}; }
        catch (e) { return {}; }
    }

    function emptyStateHTML(qLabels) {
        return '<div class="sonic-empty">' +
            '<svg viewBox="0 0 200 180" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<g class="float" stroke="#00f0ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M40 120 Q70 105 100 118 Q130 105 160 120 L160 62 Q130 50 100 62 Q70 50 40 62 Z"/><path d="M100 62 L100 118"/></g>' +
            '<g stroke="#ff00aa" stroke-width="4" stroke-linecap="round"><circle cx="100" cy="86" r="34"/><path d="M125 111 L152 138"/></g>' +
            '<line class="scan" x1="74" y1="86" x2="126" y2="86" stroke="#00f0ff" stroke-width="2"/></svg>' +
            '<h3>No results found</h3>' +
            '<p>We couldn\'t find any collection matching your query' + (qLabels.length ? ' for <strong style="color:var(--sonic-neon)">' + esc(qLabels.join(' · ')) + '</strong>' : '') + '. Try different keywords or remove some filters.</p>' +
            '<div class="sonic-empty-actions"><a class="sonic-btn" href="index.php?search=search"><i class="fas fa-undo"></i> Reset Filters</a><a class="sonic-btn-outline" href="index.php"><i class="fas fa-home"></i> Back to Home</a></div></div>';
    }

    /* ===== Saved searches (localStorage) ===== */
    var SKEY = 'sonicSavedSearches';
    function loadSaved() { try { return JSON.parse(localStorage.getItem(SKEY) || '[]'); } catch (e) { return []; } }
    function storeSaved(a) { try { localStorage.setItem(SKEY, JSON.stringify(a.slice(0, 20))); } catch (e) {} }
    function renderSaved() {
        var list = qs('#sonicSavedList'); if (!list) return;
        var arr = loadSaved(); list.innerHTML = '';
        if (!arr.length) { list.innerHTML = '<p class="sonic-saved-empty">Belum ada pencarian tersimpan.</p>'; return; }
        arr.forEach(function (it, i) {
            var row = document.createElement('div'); row.className = 'sonic-saved-item';
            row.innerHTML = '<a href="' + esc(it.url) + '"><i class="fas fa-search"></i>' + esc(it.label) + '</a><button type="button" data-i="' + i + '" class="sonic-facet-clear" title="Hapus">&times;</button>';
            list.appendChild(row);
        });
    }
    function initSaved() {
        var btn = qs('#sonicSaveSearch'), panel = qs('#sonicSavedPanel'), close = qs('#sonicSavedClose'), list = qs('#sonicSavedList');
        if (btn) btn.addEventListener('click', function () {
            var arr = loadSaved();
            var label = (new URLSearchParams(window.location.search).get('keywords') || 'Pencarian tersimpan');
            arr.unshift({ url: window.location.href, label: label, ts: Date.now() });
            storeSaved(arr); renderSaved(); if (panel) panel.style.display = 'block';
            if (window.toastr) toastr.success('Pencarian disimpan');
        });
        if (close) close.addEventListener('click', function () { if (panel) panel.style.display = 'none'; });
        if (list) list.addEventListener('click', function (e) {
            var b = e.target.closest('button[data-i]'); if (!b) return;
            var arr = loadSaved(); arr.splice(parseInt(b.getAttribute('data-i'), 10), 1); storeSaved(arr); renderSaved();
        });
        renderSaved();
    }

    /* ===== Export links (kontrak core) ===== */
    function initExport() {
        var base = window.location.href.split('#')[0];
        function withParam(k) { return base + (base.indexOf('?') >= 0 ? '&' : '?') + k + '=true'; }
        var rss = qs('#sonicRss'), jl = qs('#sonicJsonLd'), xml = qs('#sonicXml');
        if (rss) rss.href = withParam('rss');
        if (jl) jl.href = withParam('JSONLD');
        if (xml) xml.href = withParam('resultXML');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var main = qs('.sonic-result-main');
        if (!main) return;
        var params = new URLSearchParams(window.location.search);
        var filter = readFilter();

        /* ===== 1. SUMMARY BAR + STAGGER / EMPTY STATE ===== */
        var cards = qsa('.sonic-result-main .sonic-list-card, .sonic-result-main .sonic-book');
        var qLabels = [];
        ['keywords', 'title', 'author', 'subject', 'isbn', 'callnumber'].forEach(function (k) { if (params.get(k)) qLabels.push(k + ': ' + params.get(k)); });
        ['gmd', 'location', 'colltype'].forEach(function (k) {
            var v = filter[k]; if (Array.isArray(v)) v.forEach(function (x) { qLabels.push(x); }); else if (v) qLabels.push(v);
        });

        var hasPaging = qs('.sonic-result-main .biblioPaging') || qs('.sonic-result-main .sonic-paging');
        if (cards.length === 0 && !hasPaging) {
            var holder = document.createElement('div'); holder.innerHTML = emptyStateHTML(qLabels);
            main.appendChild(holder.firstChild);
        } else {
            var total = null; var foundStrong = qs('.search-found-info strong');
            if (foundStrong) { var n = parseInt(foundStrong.textContent.replace(/[^\d]/g, ''), 10); if (!isNaN(n)) total = n; }
            var bar = document.createElement('div'); bar.className = 'sonic-result-bar';
            bar.innerHTML = '<span><i class="fas fa-bolt"></i> <b>' + (total !== null ? total : cards.length) + '</b> ' + (total !== null ? 'total results' : 'result' + (cards.length === 1 ? '' : 's') + ' on this page') + '</span>' +
                (qLabels.length ? '<span class="sonic-result-q"><i class="fas fa-search"></i> ' + esc(qLabels.join(' · ')) + '</span>' : '');
            var toolbar = main.querySelector('.sonic-result-toolbar');
            if (toolbar) toolbar.insertAdjacentElement('afterend', bar); else main.insertBefore(bar, main.firstChild);
            cards.forEach(function (c, i) { c.style.animationDelay = (i * 60) + 'ms'; c.classList.add('sonic-rise'); });
        }

        /* ===== 2. FACET: grup aktif + clear per grup (multi-select) ===== */
        qsa('#sonicFacet .sonic-facet-group').forEach(function (g) {
            var boxes = qsa('input[type="checkbox"]', g);
            if (!boxes.length) return;
            var anyOn = boxes.some(function (b) { return b.checked; });
            if (!anyOn) return;
            g.classList.add('active');
            var label = g.querySelector('label'); if (!label) return;
            var clr = document.createElement('button');
            clr.type = 'button'; clr.className = 'sonic-facet-clear'; clr.title = 'Remove this filter'; clr.innerHTML = '&times;';
            clr.addEventListener('click', function () {
                boxes.forEach(function (b) { b.checked = false; var w = b.closest('.sonic-facet-check'); if (w) w.classList.remove('on'); });
                // Picu satu change agar sonic-facet.js membangun ulang URL (satu kali navigasi).
                boxes[0].dispatchEvent(new Event('change', { bubbles: true }));
            });
            label.appendChild(clr);
        });

        /* ===== 3. BADGE JUMLAH OPSI FACET ===== */
        fetch(findBase() + 'api/facet-counts.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                qsa('.sonic-facet-count').forEach(function (el) {
                    var f = el.getAttribute('data-facet'), v = el.getAttribute('data-value');
                    var map = data[f] || {};
                    if (map[v] != null) el.textContent = map[v];
                });
            })
            .catch(function () { /* silent */ });

        initSaved();
        initExport();
    });

    console.log('%c🎛 SONIC Search Experience Fase 2: ACTIVE', 'color:#00f0ff;font-size:14px;font-weight:700;');
})();

;/* --- sonic-premium.js --- */
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
