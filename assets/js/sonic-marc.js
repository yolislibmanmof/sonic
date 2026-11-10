/**
 * SONIC MARC VIEWER — terminal decoder untuk record MARC
 * Memakai URL bawaan SLiMS: index.php?p=show_detail&id=X&MARC=true
 */
(function () {
    'use strict';

    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function parseMarcLine(line, i) {
        var tag = '', ind = '', rest = line;
        var mm = line.match(/^([0-9A-Za-z]{3})\s(.*)$/);
        if (mm) {
            tag = mm[1]; rest = mm[2];
            var im = rest.match(/^(\S{1,2})\s(.*)$/);
            if (im && /^[0-9a-z\-]{1,2}$/.test(im[1])) { ind = im[1]; rest = im[2]; }
        }
        var safe = esc(rest).replace(/\$([a-z0-9])/g, '<span class="marc-sf">$$1</span>');
        return '<div class="marc-line"><span class="marc-idx">' + String(i).padStart(2, '0') + '</span>' +
            (tag ? '<span class="marc-tag">' + esc(tag) + '</span>' : '') +
            (ind ? '<span class="marc-ind">' + esc(ind) + '</span>' : '') +
            '<span class="marc-rest">' + safe + '</span></div>';
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
                '<div class="sonic-terminal-body"><div class="sonic-marc-out"></div></div>' +
                '</div>';
            document.body.appendChild(modal);
            modal.addEventListener('click', function (e) {
                if (e.target === modal || e.target.hasAttribute('data-close')) modal.classList.remove('active');
            });
        }
        modal.classList.add('active');
        var out = modal.querySelector('.sonic-marc-out');
        out.innerHTML = '<span class="sonic-loading"></span> decoding MARC stream...';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (txt) {
                var plain = txt.replace(/<[^>]*>/g, ''); // buang HTML bila ada
                var lines = plain.split(/\r?\n/).filter(function (l) { return l.trim() !== ''; });
                if (!lines.length) { out.innerHTML = '<p class="sonic-marc-empty">// MARC stream kosong</p>'; return; }
                out.innerHTML = '<div class="marc-stream">' + lines.map(parseMarcLine).join('') + '</div>';
            })
            .catch(function () {
                out.innerHTML = '<p class="sonic-marc-empty">// gagal memuat MARC stream</p>';
            });
    }

    window.openMarcViewer = openMarc;

    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('.sonic-marc-trigger') : null;
        if (t) { e.preventDefault(); openMarc(t.getAttribute('href')); }
    });
})();