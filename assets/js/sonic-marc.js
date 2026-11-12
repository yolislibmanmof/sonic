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