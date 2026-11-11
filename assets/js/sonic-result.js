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