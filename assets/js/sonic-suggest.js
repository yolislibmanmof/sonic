/**
 * SONIC OPAC — Smart Autocomplete (BATCH 5: instant search kaya hasil)
 * Dropdown kini menampilkan COVER + JUDUL + PENGARANG + TAHUN untuk item judul;
 * klik item judul → langsung ke halaman detail. Baris "lihat semua hasil" tetap
 * menjalankan pencarian penuh (perilaku lama dipertahankan). Author/subjek tetap
 * mengisi input + submit seperti sebelumnya. ARIA combobox (Batch 4) dipertahankan.
 */
(function () {
    'use strict';

    function findEndpoint() {
        if (window.SONIC_CFG && window.SONIC_CFG.base) return window.SONIC_CFG.base + 'api/suggest.php';
        var link = document.querySelector('link[href*="sonic-base.css"]');
        if (link) return link.href.replace(/assets\/css\/sonic-base\.css.*$/, '') + 'api/suggest.php';
        return 'template/sonic/api/suggest.php';
    }
    var ENDPOINT = findEndpoint();
    var RICH = !(window.SONIC_CFG && window.SONIC_CFG.discovery && window.SONIC_CFG.discovery.rich === false);

    var ICONS = { title: 'fa-book', author: 'fa-user-edit', subject: 'fa-tags' };
    var _tagAuthor = document.querySelector('[data-search-mode="author"]');
    var _isId = _tagAuthor ? /pengarang/i.test(_tagAuthor.textContent) : !!(window.SONIC_CFG && window.SONIC_CFG.lang === 'id');
    var LABEL = _isId
        ? { title: 'Judul', author: 'Pengarang', subject: 'Subjek' }
        : { title: 'Title', author: 'Author', subject: 'Subject' };
    var ALL_LABEL = _isId ? 'Lihat semua hasil untuk' : 'See all results for';
    var MODE  = { title: 'keywords', author: 'author', subject: 'subject' };

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    function highlight(label, q) {
        var safe = escapeHtml(label);
        var idx = label.toLowerCase().indexOf(q.toLowerCase());
        if (idx < 0) return safe;
        var before = escapeHtml(label.slice(0, idx));
        var match = escapeHtml(label.slice(idx, idx + q.length));
        var after = escapeHtml(label.slice(idx + q.length));
        return before + '<mark>' + match + '</mark>' + after;
    }

    var uid = 0;
    function attach(input) {
        if (!input || input.dataset.sonicSuggest) return;
        input.dataset.sonicSuggest = '1';

        var wrap = input.closest('.sonic-input-wrap') || input.closest('.sonic-search-box') || input.parentElement;
        var box = document.createElement('div');
        box.className = 'sonic-suggest';
        box.id = 'sonicSuggestList' + (++uid);
        box.setAttribute('role', 'listbox');
        wrap.appendChild(box);

        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', box.id);

        var items = [], sel = -1, ctrl = null, timer = null;

        function close() { box.classList.remove('open', 'loading'); sel = -1; items = []; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); }

        function render(q) {
            if (!items.length) { close(); return; }
            box.innerHTML = '';
            items.forEach(function (it, i) {
                var el = document.createElement('div');
                el.className = 'sonic-suggest-item' + (i === sel ? ' sel' : '');
                el.id = box.id + '-opt' + i;
                el.setAttribute('role', 'option');
                el.setAttribute('aria-selected', i === sel ? 'true' : 'false');
                if (RICH && it.t === 'title' && it.id) {
                    el.classList.add('rich');
                    var meta = [it.author || '', it.year || ''].filter(Boolean).join(' · ');
                    var cover = it.cover
                        ? '<img class="s-cover" src="' + escapeHtml(it.cover) + '" alt="" loading="lazy" onerror="this.style.visibility=\'hidden\'">'
                        : '<i class="fas fa-book s-cover-fallback"></i>';
                    el.innerHTML = cover +
                        '<span class="s-body"><span class="s-label">' + highlight(it.v, q) + '</span>' +
                        (meta ? '<span class="s-meta">' + escapeHtml(meta) + '</span>' : '') + '</span>' +
                        '<span class="s-type">' + LABEL.title + '</span>';
                } else {
                    el.innerHTML = '<i class="fas ' + (ICONS[it.t] || 'fa-book') + '"></i>' +
                                   '<span class="s-label">' + highlight(it.v, q) + '</span>' +
                                   '<span class="s-type">' + (LABEL[it.t] || '') + '</span>';
                }
                el.addEventListener('mousedown', function (e) { e.preventDefault(); pick(it); });
                box.appendChild(el);
            });
            /* Baris "lihat semua hasil" — mempertahankan jalur pencarian lama */
            var all = document.createElement('div');
            all.className = 'sonic-suggest-all';
            all.innerHTML = '<i class="fas fa-bolt"></i> ' + ALL_LABEL + ' &ldquo;' + escapeHtml(q) + '&rdquo;';
            all.addEventListener('mousedown', function (e) {
                e.preventDefault(); close();
                var form = input.closest('form'); if (form) form.submit();
            });
            box.appendChild(all);
            box.classList.add('open');
            input.setAttribute('aria-expanded', 'true');
            if (sel >= 0) input.setAttribute('aria-activedescendant', box.id + '-opt' + sel);
            else input.removeAttribute('aria-activedescendant');
        }

        function pick(it) {
            /* BATCH 5: judul dengan id → langsung ke detail (instant search) */
            if (RICH && it.t === 'title' && it.id) {
                close();
                window.location.href = 'index.php?p=show_detail&id=' + encodeURIComponent(it.id);
                return;
            }
            input.value = it.v;
            input.name = MODE[it.t] || 'keywords';
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

    if (window.SONIC_DEBUG) console.log('%c🔮 SONIC Smart Autocomplete (rich): ACTIVE', 'color:#00f0ff;font-size:14px;font-weight:700;');
})();