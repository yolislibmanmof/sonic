/**
 * SONIC DISCOVERY — BATCH 5 (additif)
 * Halaman detail:
 *   1) Virtual Shelf — "buku di sebelah rak ini" (tetangga call number, buku kini di tengah)
 *   2) Co-loan — "Peminjam buku ini juga meminjam…" (agregat anonim dari data sirkulasi)
 * Data dari api/discovery.php (cache server). Semua di-guard; tanpa data → seksi tidak muncul.
 * Toggle Kustomisasi dihormati via SONIC_CFG.discovery.
 */
(function () {
    'use strict';
    var CFG = window.SONIC_CFG || {};
    var D = CFG.discovery || {};
    var IS_ID = /^id/i.test(String(CFG.lang || document.documentElement.lang || 'en'));

    function base() {
        if (CFG.base) return CFG.base;
        var l = document.querySelector('link[href*="sonic-base.css"]');
        return l ? l.href.replace(/assets\/css\/sonic-base\.css.*$/, '') : 'template/sonic/';
    }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function detailUrl(id) { return 'index.php?p=show_detail&id=' + encodeURIComponent(id); }

    function card(b, extraClass, badge) {
        var el = document.createElement(b.id ? 'a' : 'div');
        el.className = 'sonic-book' + (extraClass ? ' ' + extraClass : '');
        if (b.id) el.href = detailUrl(b.id);
        var cover = b.cover || './images/default/image.png';
        var meta = b.call_number ? b.call_number : (b.author || b.year || '');
        el.innerHTML = '<div class="sonic-book-cover"><img loading="lazy" src="' + esc(cover) + '" alt="' + esc(b.title) + '" onerror="this.src=\'./images/default/image.png\'">' +
            (badge ? '<span class="sonic-rail-badge">' + badge + '</span>' : '') + '</div>' +
            '<h4 class="sonic-book-title">' + esc(b.title) + '</h4>' +
            '<p class="sonic-book-author">' + esc(meta) + '</p>';
        return el;
    }

    function section(id, icon, title, sub) {
        var sec = document.createElement('div');
        sec.className = 'sonic-discovery-section'; sec.id = id;
        sec.innerHTML = '<h5><i class="fas ' + icon + '"></i> ' + esc(title) + '</h5>' +
            (sub ? '<p class="sonic-discovery-sub">' + sub + '</p>' : '');
        return sec;
    }

    function railWrap(rail, labelPrev, labelNext) {
        var wrap = document.createElement('div'); wrap.className = 'sonic-rail-wrap';
        var nav = document.createElement('div'); nav.className = 'sonic-rail-nav';
        function mk(icon, label) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'sonic-mini-btn'; b.setAttribute('aria-label', label);
            b.innerHTML = '<i class="fas ' + icon + '"></i>';
            return b;
        }
        var prev = mk('fa-chevron-left', labelPrev), next = mk('fa-chevron-right', labelNext);
        prev.addEventListener('click', function () { rail.scrollBy({ left: -260, behavior: 'smooth' }); });
        next.addEventListener('click', function () { rail.scrollBy({ left: 260, behavior: 'smooth' }); });
        nav.appendChild(prev); nav.appendChild(next);
        wrap.appendChild(nav); wrap.appendChild(rail);
        function upd() {
            var max = rail.scrollWidth - rail.clientWidth;
            nav.style.display = max > 4 ? 'flex' : 'none';
        }
        rail.addEventListener('scroll', upd, { passive: true });
        window.addEventListener('resize', upd);
        setTimeout(upd, 60);
        return wrap;
    }

    /* ===== 1. Virtual Shelf ===== */
    function initShelf(root, id) {
        if (D.shelf === false) return;
        fetch(base() + 'api/discovery.php?action=shelf&id=' + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || !d.current) return;
                var before = Array.isArray(d.before) ? d.before : [];
                var after = Array.isArray(d.after) ? d.after : [];
                if (!before.length && !after.length) return;
                var main = root.querySelector('.sonic-detail-main') || root;
                var title = IS_ID ? 'Virtual Shelf — buku di sebelah rak ini' : 'Virtual Shelf — books next to this one';
                var sub = (IS_ID ? 'Urut nomor panggil di rak: ' : 'Shelf order by call number: ') + '<b>' + esc(d.key || '') + '</b>';
                var sec = section('sonicShelf', 'fa-book-reader', title, sub);
                var rail = document.createElement('div'); rail.className = 'sonic-rail';
                before.forEach(function (b) { rail.appendChild(card(b)); });
                var cur = card(d.current, 'current', '<i class="fas fa-eye"></i> ' + (IS_ID ? 'Sedang dilihat' : 'Viewing'));
                rail.appendChild(cur);
                after.forEach(function (b) { rail.appendChild(card(b)); });
                sec.appendChild(railWrap(rail, IS_ID ? 'Geser kiri' : 'Scroll left', IS_ID ? 'Geser kanan' : 'Scroll right'));
                if (d.prefix) {
                    var more = document.createElement('a');
                    more.className = 'sonic-mini-btn sonic-shelf-more';
                    more.href = 'index.php?search=search&filter=' + encodeURIComponent(JSON.stringify({ callnumber: d.prefix }));
                    more.innerHTML = '<i class="fas fa-compass"></i> ' + (IS_ID ? 'Jelajahi rak ini' : 'Browse this shelf');
                    sec.appendChild(more);
                }
                main.appendChild(sec);
                /* Posisikan buku kini di tengah rail — tanpa menggeser halaman */
                setTimeout(function () {
                    rail.scrollLeft = Math.max(0, cur.offsetLeft - (rail.clientWidth / 2) + (cur.offsetWidth / 2));
                }, 80);
            })
            .catch(function () {});
    }

    /* ===== 2. Co-loan ===== */
    function initColoan(root, id) {
        if (D.coloan === false) return;
        fetch(base() + 'api/discovery.php?action=coloan&id=' + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (items) {
                if (!Array.isArray(items) || !items.length) return;
                var main = root.querySelector('.sonic-detail-main') || root;
                var title = IS_ID ? 'Peminjam buku ini juga meminjam…' : 'Borrowers of this book also borrowed…';
                var sub = IS_ID ? 'Rekomendasi dari pola sirkulasi — anonim, tanpa data pribadi' : 'Recommendations from circulation patterns — anonymous, no personal data';
                var sec = section('sonicColoan', 'fa-users', title, esc(sub));
                var rail = document.createElement('div'); rail.className = 'sonic-rail';
                items.forEach(function (b) {
                    var badge = '<i class="fas fa-user-friends"></i> ×' + (parseInt(b.score, 10) || 0);
                    rail.appendChild(card(b, '', badge));
                });
                sec.appendChild(railWrap(rail, IS_ID ? 'Geser kiri' : 'Scroll left', IS_ID ? 'Geser kanan' : 'Scroll right'));
                main.appendChild(sec);
            })
            .catch(function () {});
    }

    function init() {
        var root = document.querySelector('.sonic-detail-revolution');
        if (!root) return;
        var id = root.getAttribute('data-biblio-id');
        if (!id) return;
        try { initShelf(root, id); } catch (e) {}
        try { initColoan(root, id); } catch (e) {}
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    if (window.SONIC_DEBUG) console.log('%c🧭 SONIC Discovery (shelf + co-loan): ACTIVE', 'color:#00f0ff;font-size:14px;font-weight:700;');
})();