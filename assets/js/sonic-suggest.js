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