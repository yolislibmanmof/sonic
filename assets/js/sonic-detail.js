/**
 * SONIC OPAC — Detail Record Revolution
 * 3D Book Interaction, Tab System, Citations, Micro-interactions
 */
(function () {
    'use strict';

    /* ===== 3D BOOK MOUSE TRACKING ===== */
    var book3D = document.getElementById('sonicBook3D');
    if (book3D) {
        var bookInner = book3D.querySelector('.sonic-book-3d-inner');
        var isDragging = false;
        var startX, startY, currentX = -20, currentY = 5;

        book3D.addEventListener('mousemove', function (e) {
            if (isDragging) return;
            var rect = book3D.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width - 0.5;
            var y = (e.clientY - rect.top) / rect.height - 0.5;
            bookInner.style.transform = 'rotateY(' + (x * 40 - 20) + 'deg) rotateX(' + (-y * 30 + 5) + 'deg)';
        });

        book3D.addEventListener('mouseleave', function () {
            if (!isDragging) {
                bookInner.style.transform = 'rotateY(-20deg) rotateX(5deg)';
            }
        });

        book3D.addEventListener('mousedown', function (e) {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            book3D.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            var deltaX = e.clientX - startX;
            var deltaY = e.clientY - startY;
            currentX += deltaX * 0.3;
            currentY -= deltaY * 0.3;
            currentX = Math.max(-60, Math.min(60, currentX));
            currentY = Math.max(-30, Math.min(30, currentY));
            bookInner.style.transform = 'rotateY(' + currentX + 'deg) rotateX(' + currentY + 'deg)';
            startX = e.clientX;
            startY = e.clientY;
        });

        document.addEventListener('mouseup', function () {
            isDragging = false;
            book3D.style.cursor = 'grab';
        });
    }

    /* ===== TAB SYSTEM ===== */
    var tabs = document.querySelectorAll('.sonic-tab');
    var panels = document.querySelectorAll('.sonic-tab-panel');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = this.getAttribute('data-tab');
            tabs.forEach(function (t) { t.classList.remove('active'); });
            panels.forEach(function (p) { p.classList.remove('active'); });
            this.classList.add('active');
            var targetPanel = document.getElementById('tab-' + target);
            if (targetPanel) targetPanel.classList.add('active');
        });
    });

    /* ===== CITATION COPY ===== */
    var copyToast = document.getElementById('copyToast');
    var copyToastText = document.getElementById('copyToastText');

    function showToast(text) {
        if (!copyToast) return;
        copyToastText.textContent = text;
        copyToast.classList.add('show');
        setTimeout(function () { copyToast.classList.remove('show'); }, 2500);
    }

    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                showToast('Teks disalin ke clipboard!');
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showToast('Teks disalin ke clipboard!');
        }
    }

    var citeCopyBtns = document.querySelectorAll('.sonic-cite-copy');
    citeCopyBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var style = this.getAttribute('data-style').toLowerCase();
            var citationBody = document.getElementById('citation-' + style);
            if (citationBody) {
                var text = citationBody.textContent || citationBody.innerText;
                copyToClipboard(text);
            }
        });
    });

    var copyAllBtn = document.getElementById('copyAllCitations');
    if (copyAllBtn) {
        copyAllBtn.addEventListener('click', function () {
            var allCitations = [];
            document.querySelectorAll('.sonic-citation-card').forEach(function (card) {
                var style = card.querySelector('.sonic-citation-style').textContent;
                var body = card.querySelector('.sonic-citation-body');
                if (body) {
                    var text = body.textContent || body.innerText;
                    allCitations.push(style + ':\n' + text);
                }
            });
            if (allCitations.length > 0) {
                copyToClipboard(allCitations.join('\n\n'));
            }
        });
    }

    /* ===== HEART BURST ANIMATION (Bookmark) ===== */
    var bookmarkBtn = document.getElementById('bookmarkBtn');
    var heartBurst = document.getElementById('heartBurst');

    if (bookmarkBtn && heartBurst) {
        bookmarkBtn.addEventListener('click', function (e) {
            if (this.classList.contains('active')) {
                createHeartBurst(e.clientX, e.clientY);
            }
        });

        function createHeartBurst(x, y) {
            heartBurst.style.left = x + 'px';
            heartBurst.style.top = y + 'px';
            for (var i = 0; i < 12; i++) {
                var heart = document.createElement('div');
                heart.className = 'heart-particle';
                heart.innerHTML = '❤️';
                var angle = (i / 12) * Math.PI * 2;
                var distance = 60 + Math.random() * 40;
                heart.style.setProperty('--tx', Math.cos(angle) * distance + 'px');
                heart.style.setProperty('--ty', Math.sin(angle) * distance + 'px');
                heartBurst.appendChild(heart);
                setTimeout(function () {
                    if (heart.parentNode) heart.parentNode.removeChild(heart);
                }, 1000);
            }
        }
    }

    /* ===== SPOTLIGHT EFFECT ON INFO BLOCKS ===== */
    document.querySelectorAll('.sonic-info-block').forEach(function (block) {
        block.addEventListener('mousemove', function (e) {
            var rect = block.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width) * 100;
            var y = ((e.clientY - rect.top) / rect.height) * 100;
            block.style.background = 'radial-gradient(circle at ' + x + '% ' + y + '%, rgba(0,240,255,0.08), var(--sonic-surface) 40%)';
        });
        block.addEventListener('mouseleave', function () {
            block.style.background = 'var(--sonic-surface)';
        });
    });

    console.log('%c📚 SONIC Detail Record Revolution: ACTIVE', 'color:#00f0ff;font-size:14px;font-weight:700;');
})();

/* ===== CITATION EXPORT (RIS / BibTeX / EndNote) — fitur pemustaka akademis ===== */
(function () {
    'use strict';
    var el = document.getElementById('sonicCiteData');
    if (!el) return;
    var d; try { d = JSON.parse(el.textContent); } catch (e) { return; }

    function toast(msg) {
        var t = document.getElementById('copyToast'), tx = document.getElementById('copyToastText');
        if (t && tx) { tx.textContent = msg; t.classList.add('show'); setTimeout(function () { t.classList.remove('show'); }, 2500); }
        else if (window.toastr) toastr.success(msg);
    }
    function buildRIS() {
        var L = ['TY  - BOOK'];
        (d.authors || []).forEach(function (a) { L.push('AU  - ' + a); });
        L.push('TI  - ' + d.title);
        if (d.edition) L.push('ET  - ' + d.edition);
        if (d.place) L.push('CY  - ' + d.place);
        if (d.publisher) L.push('PB  - ' + d.publisher);
        if (d.year) L.push('PY  - ' + d.year);
        if (d.isbn) L.push('SN  - ' + d.isbn);
        L.push('UR  - ' + d.url, 'ER  - ');
        return L.join('\r\n');
    }
    function bibKey() {
        var a = (d.authors && d.authors[0]) ? d.authors[0].split(' ').pop() : 'anon';
        return ((a + (d.year || '')).replace(/[^a-zA-Z0-9]/g, '').toLowerCase()) || 'book';
    }
    function buildBib() {
        var L = ['@book{' + bibKey() + ','];
        L.push('  title     = {' + d.title + '},');
        if (d.authors && d.authors.length) L.push('  author    = {' + d.authors.join(' and ') + '},');
        if (d.year) L.push('  year      = {' + d.year + '},');
        if (d.publisher) L.push('  publisher = {' + d.publisher + '},');
        if (d.place) L.push('  address   = {' + d.place + '},');
        if (d.edition) L.push('  edition   = {' + d.edition + '},');
        if (d.isbn) L.push('  isbn      = {' + d.isbn + '},');
        L.push('  url       = {' + d.url + '}', '}');
        return L.join('\n');
    }
    function buildEN() {
        var L = ['%0 Book'];
        (d.authors || []).forEach(function (a) { L.push('%A ' + a); });
        L.push('%T ' + d.title);
        if (d.edition) L.push('%E ' + d.edition);
        if (d.place) L.push('%C ' + d.place);
        if (d.publisher) L.push('%I ' + d.publisher);
        if (d.year) L.push('%D ' + d.year);
        if (d.isbn) L.push('%@ ' + d.isbn);
        L.push('%U ' + d.url);
        return L.join('\r\n');
    }
    function download(name, content, mime) {
        var blob = new Blob([content], { type: (mime || 'text/plain') + ';charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob); a.download = name;
        document.body.appendChild(a); a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 150);
    }
    document.addEventListener('click', function (e) {
        var b = e.target.closest ? e.target.closest('[data-export]') : null;
        if (!b) return;
        var t = b.getAttribute('data-export'), base = 'sonic-' + (d.id || 'book');
        if (t === 'ris') { download(base + '.ris', buildRIS(), 'application/x-research-info-systems'); toast('RIS diunduh — siap impor ke Mendeley/Zotero'); }
        else if (t === 'bibtex') { download(base + '.bib', buildBib(), 'application/x-bibtex'); toast('BibTeX diunduh — siap dipakai di LaTeX'); }
        else if (t === 'endnote') { download(base + '.enw', buildEN(), 'application/x-endnote-refer'); toast('EndNote diunduh — siap impor ke EndNote'); }
    });
})();