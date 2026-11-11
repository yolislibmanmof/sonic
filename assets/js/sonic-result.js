/**
 * SONIC OPAC — Search Experience Upgrade (Fase B)
 * Result summary bar, stagger entrance, empty state animasi,
 * clear-filter per grup facet, badge jumlah opsi facet.
 * 100% aditif: tidak mengubah markup existing, hanya menyuntik lapisan.
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

    function emptyStateHTML(qLabels) {
        return '' +
        '<div class="sonic-empty">' +
        '  <svg viewBox="0 0 200 180" fill="none" xmlns="http://www.w3.org/2000/svg">' +
        '    <g class="float" stroke="#00f0ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">' +
        '      <path d="M40 120 Q70 105 100 118 Q130 105 160 120 L160 62 Q130 50 100 62 Q70 50 40 62 Z"/>' +
        '      <path d="M100 62 L100 118"/>' +
        '    </g>' +
        '    <g stroke="#ff00aa" stroke-width="4" stroke-linecap="round">' +
        '      <circle cx="100" cy="86" r="34"/>' +
        '      <path d="M125 111 L152 138"/>' +
        '    </g>' +
        '    <line class="scan" x1="74" y1="86" x2="126" y2="86" stroke="#00f0ff" stroke-width="2"/>' +
        '  </svg>' +
        '  <h3>No results found</h3>' +
        '  <p>We couldn\'t find any collection matching your query' + (qLabels.length ? ' for <strong style="color:var(--sonic-neon)">' + esc(qLabels.join(' · ')) + '</strong>' : '') + '. Try different keywords or remove some filters.</p>' +
        '  <div class="sonic-empty-actions">' +
        '    <a class="sonic-btn" href="index.php?search=search"><i class="fas fa-undo"></i> Reset Filters</a>' +
        '    <a class="sonic-btn-outline" href="index.php"><i class="fas fa-home"></i> Back to Home</a>' +
        '  </div>' +
        '</div>';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var main = qs('.sonic-result-main');
        if (!main) return;
        var params = new URLSearchParams(window.location.search);

        /* ===== 1. SUMMARY BAR + STAGGER ENTRANCE ===== */
        var cards = qsa('.sonic-result-main .sonic-list-card, .sonic-result-main .sonic-book');
        var qLabels = [];
        ['keywords', 'title', 'author', 'subject', 'isbn', 'callnumber'].forEach(function (k) {
            if (params.get(k)) qLabels.push(k + ': ' + params.get(k));
        });

        if (cards.length === 0 && !qs('.sonic-result-main .sonic-paging')) {
            main.innerHTML = emptyStateHTML(qLabels);
        } else {
            var total = null;
            var foundStrong = document.querySelector('.search-found-info strong');
            if (foundStrong) { var n = parseInt(foundStrong.textContent.replace(/[^\d]/g, ''), 10); if (!isNaN(n)) total = n; }
            var bar = document.createElement('div');
            bar.className = 'sonic-result-bar';
            bar.innerHTML = '<span><i class="fas fa-bolt"></i> <b>' + (total !== null ? total : cards.length) + '</b> ' + (total !== null ? 'total results' : 'result' + (cards.length === 1 ? '' : 's') + ' on this page') + '</span>' +
                (qLabels.length ? '<span class="sonic-result-q"><i class="fas fa-search"></i> ' + esc(qLabels.join(' · ')) + '</span>' : '');
            var toolbar = main.querySelector('.sonic-result-toolbar');
            if (toolbar) { toolbar.insertAdjacentElement('afterend', bar); } else { main.insertBefore(bar, main.firstChild); }
            cards.forEach(function (c, i) {
                c.style.animationDelay = (i * 70) + 'ms';
                c.classList.add('sonic-rise');
            });
        }

        /* ===== 2. FACET: clear per grup + badge jumlah ===== */
        var facet = qs('#sonicFacet');
        if (!facet) return;

        qsa('#sonicFacet .sonic-facet-group').forEach(function (g) {
            var field = g.querySelector('select,input');
            if (!field || !field.name) return;
            if (!params.get(field.name)) return;
            g.classList.add('active');
            var label = g.querySelector('label');
            if (!label) return;
            var clr = document.createElement('button');
            clr.type = 'button';
            clr.className = 'sonic-facet-clear';
            clr.title = 'Remove this filter';
            clr.innerHTML = '&times;';
            clr.addEventListener('click', function () {
                var p = new URLSearchParams(window.location.search);
                p.delete(field.name);
                if (!p.has('search')) p.set('search', 'search');
                window.location.search = p.toString();
            });
            label.appendChild(clr);
        });

        function mapCounts(sel, map) {
            qsa('#sonicFacet ' + sel + ' option').forEach(function (op) {
                var t = op.textContent.trim();
                if (map[t] != null) op.textContent = t + ' (' + map[t] + ')';
            });
        }
        fetch(findBase() + 'api/facet-counts.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                mapCounts('select[name="gmd"]', data.gmd || {});
                mapCounts('select[name="location"]', data.location || {});
                mapCounts('select[name="colltype"]', data.colltype || {});
            })
            .catch(function () { /* silent */ });
    });

        /* ===== SORT (filter[sort] JSON sesuai core) ===== */
        var sortSel = document.getElementById('sonicSort');
        if (sortSel) {
            try {
                var f = JSON.parse(params.get('filter') || '{}');
                if (f.sort) sortSel.value = f.sort;
            } catch (e) {}
            sortSel.addEventListener('change', function () {
                var p = new URLSearchParams(window.location.search);
                var filter = {};
                try { filter = JSON.parse(p.get('filter') || '{}'); } catch (e) {}
                if (this.value) { filter.sort = this.value; } else { delete filter.sort; }
                if (Object.keys(filter).length) { p.set('filter', JSON.stringify(filter)); } else { p.delete('filter'); }
                if (!p.has('search')) p.set('search', 'search');
                window.location.search = p.toString();
            });
        }

    console.log('%c🎛 SONIC Search Experience Fase B: ACTIVE', 'color:#00f0ff;font-size:14px;font-weight:700;');
})();