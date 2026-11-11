/**
 * SONIC MARC VIEWER v2 — ISO 2709 decoder (leader + directory aware)
 * Sumber: index.php?p=show_detail&id=X&MARC=true  (application/marc, raw .mrc)
 */
(function () {
    'use strict';
    var RS = '\x1D', FS = '\x1E', SF = '\x1F';

    function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; }

    /* Parse satu record ISO 2709 menjadi array field {tag, ind, subs[]} */
    function parseISO2709(rec) {
        var lines = [];
        if (!rec || rec.length < 24) return lines;
        var base = parseInt(rec.substr(12, 5), 10);           // leader[12..16] = base address
        if (isNaN(base) || base <= 24 || base > rec.length) return lines;
        var dir = rec.substring(24, base - 1);               // directory (12 byte/entry)
        var n = Math.floor(dir.length / 12);
        for (var i = 0; i < n; i++) {
            var e = dir.substr(i * 12, 12);
            if (e.length < 12) break;
            var tag = e.substr(0, 3);
            var len = parseInt(e.substr(3, 4), 10);
            var start = parseInt(e.substr(7, 5), 10);
            if (isNaN(len) || isNaN(start)) continue;
            var field = rec.substr(base + start, len);
            if (field.charAt(field.length - 1) === FS) field = field.slice(0, -1);
            if (tag.indexOf('00') === 0) {                    // control field (001/003/005/008)
                lines.push({ tag: tag, ind: '', subs: [{ code: '', value: field }] });
            } else {                                          // variable field: 2 indicator + subfields
                var ind = field.substr(0, 2).replace(/ /g, '#');
                var parts = field.substr(2).split(SF);
                var subs = [];
                for (var j = 0; j < parts.length; j++) {
                    var p = parts[j];
                    if (p === '') continue;
                    subs.push({ code: p.charAt(0), value: p.substr(1) });
                }
                lines.push({ tag: tag, ind: ind, subs: subs });
            }
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
        }
        modal.classList.add('active');
        var out = modal.querySelector('.sonic-marc-out');
        var bar = modal.querySelector('.sonic-marc-toolbar');
        out.innerHTML = '<span class="sonic-loading"></span> decoding ISO 2709 stream...';
        bar.innerHTML = '';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (txt) {
                if (/^\s*</.test(txt)) { out.innerHTML = '<p class="sonic-marc-empty">// server mengembalikan HTML, bukan stream MARC</p>'; return; }
                var end = txt.indexOf(RS);
                var rec = end > 0 ? txt.substring(0, end + 1) : txt;
                var lines = parseISO2709(rec);
                bar.innerHTML = '<span class="sonic-mini-btn" style="pointer-events:none;"><i class="fas fa-list-ol"></i> ' + lines.length + ' fields</span>' +
                    '<a class="sonic-mini-btn" href="' + esc(url) + '" download><i class="fas fa-download"></i> Download .mrc</a>';
                out.innerHTML = renderLines(lines);
            })
            .catch(function () { out.innerHTML = '<p class="sonic-marc-empty">// gagal memuat MARC stream</p>'; });
    }

    window.openMarcViewer = openMarc;
    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('.sonic-marc-trigger') : null;
        if (t) { e.preventDefault(); openMarc(t.getAttribute('href')); }
    });
})();