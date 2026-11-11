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