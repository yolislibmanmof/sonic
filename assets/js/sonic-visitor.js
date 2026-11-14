/**
 * SONIC KIOSK VISITOR — Fase 5.1 Ultimate + BATCH 6 (Scan QR kartu anggota).
 * Kontrak core tetap: POST counter/memberID/institution + CSRF → JSON {message,image,new_token}.
 * BATCH 6: html5-qrcode lokal → decode ID anggota → isi form → check-in otomatis.
 *          Kamera hanya di HTTPS/localhost; input manual selalu tersedia.
 */
(function () {
    'use strict';
    var cfg = window.SONIC_VISITOR_CFG || {};
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var root = document.getElementById('sonicKiosk'); if (!root) return;
    function $(id) { return document.getElementById(id); }
    var clock = $('kioskClock'), dateEl = $('kioskDate'), quote = $('kioskQuote'), marquee = $('kioskMarquee');
    var form = $('sonicVisitorForm'), memberID = $('sonicMemberID'), token = $('sonicVisitorToken');
    var success = $('kioskSuccess'), photo = $('kioskPhoto'), msg = $('kioskMsg');
    if (!form || !memberID) return;
    var latestToken = token ? token.value : '';
    var stopScan = null; // BATCH 6: diisi oleh initScan bila aktif
    var idleMs = (parseInt(cfg.idle, 10) || 20) * 1000;
    var lastAct = Date.now(), successTimer = null;

    /* ===== JAM + TANGGAL ===== */
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function tick() {
        var d = new Date();
        if (clock) clock.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        if (dateEl) { try { dateEl.textContent = d.toLocaleDateString(cfg.lang || 'id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }); } catch (e) { dateEl.textContent = d.toDateString(); } }
    }
    tick(); setInterval(tick, 1000);

    /* ===== QUOTE ===== */
    var quotes = (cfg.quotes && cfg.quotes.length) ? cfg.quotes : [
        'Buku adalah jendela dunia.', 'Membaca hari ini, memimpin esok hari.',
        'Perpustakaan adalah jantung pengetahuan.', 'Satu buku, satu petualangan.'
    ];
    var qi = 0;
    function rotateQuote() { if (!quote) return; quote.classList.remove('in'); setTimeout(function () { quote.textContent = '“' + quotes[qi % quotes.length] + '”'; qi++; quote.classList.add('in'); }, 300); }
    rotateQuote(); setInterval(rotateQuote, 7000);

    if (marquee) { var w = cfg.welcome || 'SELAMAT DATANG DI LAYANAN OPAC PERPUSTAKAAN'; marquee.textContent = w + '  •  ' + w + '  •  '; }

    /* ===== COUNT-UP STATISTIK ===== */
    function countUp(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (reduced) { el.textContent = target.toLocaleString(); return; }
        var t0 = null, dur = 1300;
        function step(ts) { if (!t0) t0 = ts; var p = Math.min(1, (ts - t0) / dur); var e = 1 - Math.pow(1 - p, 3); el.textContent = Math.round(target * e).toLocaleString(); if (p < 1) requestAnimationFrame(step); }
        requestAnimationFrame(step);
    }
    Array.prototype.forEach.call(document.querySelectorAll('.sonic-kstat-num'), countUp);
    function bumpStat(key) {
        var el = $('kstat-' + key); if (!el) return;
        var v = parseInt((el.textContent || '0').replace(/[^\d]/g, ''), 10) || 0;
        el.textContent = (v + 1).toLocaleString();
        el.classList.remove('pop'); void el.offsetWidth; el.classList.add('pop');
    }

    /* ===== STARFIELD BACKGROUND ===== */
    (function bgfx() {
        var cv = $('kioskFx'); if (!cv || reduced) return;
        var ctx = cv.getContext('2d');
        function size() { cv.width = cv.offsetWidth; cv.height = cv.offsetHeight; }
        size(); window.addEventListener('resize', size);
        var dots = []; for (var i = 0; i < 42; i++) dots.push({ x: Math.random(), y: Math.random(), z: 0.3 + Math.random() * 0.7, v: 0.00015 + Math.random() * 0.0004 });
        (function loop() {
            ctx.clearRect(0, 0, cv.width, cv.height);
            for (var i = 0; i < dots.length; i++) { var d = dots[i]; d.y -= d.v * d.z; if (d.y < 0) d.y = 1;
                ctx.globalAlpha = 0.12 + d.z * 0.28; ctx.fillStyle = (i % 5 === 0) ? '#ff00aa' : '#00f0ff';
                ctx.beginPath(); ctx.arc(d.x * cv.width, d.y * cv.height, d.z * 2, 0, 6.283); ctx.fill(); }
            ctx.globalAlpha = 1; requestAnimationFrame(loop);
        })();
    })();

    /* ===== CONFETTI ===== */
    function confetti() {
        var cv = $('kioskConfetti'); if (!cv || reduced) return;
        var ctx = cv.getContext('2d'); cv.width = cv.offsetWidth; cv.height = cv.offsetHeight;
        var colors = ['#00f0ff', '#ff00aa', '#5b00ff', '#00ff88', '#ffd700'];
        var parts = []; for (var i = 0; i < 90; i++) parts.push({ x: cv.width / 2, y: cv.height / 2, vx: (Math.random() - 0.5) * 12, vy: (Math.random() - 1) * 11 - 2, g: 0.28, s: 3 + Math.random() * 5, c: colors[i % colors.length], r: Math.random() * Math.PI, vr: (Math.random() - 0.5) * 0.35 });
        var frames = 0;
        (function draw() {
            ctx.clearRect(0, 0, cv.width, cv.height);
            for (var i = 0; i < parts.length; i++) { var p = parts[i]; p.vy += p.g; p.x += p.vx; p.y += p.vy; p.r += p.vr;
                ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.r); ctx.fillStyle = p.c; ctx.fillRect(-p.s / 2, -p.s / 2, p.s, p.s); ctx.restore(); }
            frames++; if (frames < 130) requestAnimationFrame(draw); else ctx.clearRect(0, 0, cv.width, cv.height);
        })();
    }

    /* ===== IDLE ===== */
    function act() { lastAct = Date.now(); }
    ['pointerdown', 'keydown', 'touchstart'].forEach(function (ev) { document.addEventListener(ev, act, { passive: true }); });
    setInterval(function () {
        if (Date.now() - lastAct > idleMs && success && !success.classList.contains('show')) {
            form.reset(); if (token) token.value = latestToken; memberID.blur();
            if (stopScan) stopScan(); // BATCH 6: matikan kamera saat idle
        }
    }, 1000);

    /* ===== SUARA ===== */
    function speak(t) {
        if (!cfg.voice || !('speechSynthesis' in window)) return;
        try { var u = new SpeechSynthesisUtterance(String(t).replace(/<[^>]+>/g, '')); u.lang = cfg.lang || 'id-ID'; u.volume = 1; u.rate = 1; speechSynthesis.cancel(); speechSynthesis.speak(u); } catch (e) {}
    }
    /* Sanitasi pesan core: escape semua, lalu izinkan kembali tag formatting kecil */
    function cleanMsg(html) {
        var s = String(html == null ? '' : html);
        s = s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        s = s.replace(/&lt;(\/?)(b|strong|i|em|br)\s*\/?&gt;/gi, '<$1$2>');
        s = s.replace(/&lt;div class="error[^"&]*"&gt;/gi, '<div class="error">').replace(/&lt;\/div&gt;/gi, '</div>');
        return s;
    }
    function showSuccess(html, isError) {
        if (!success) return;
        if (stopScan) stopScan(); // BATCH 6: matikan kamera saat tampil hasil
        msg.innerHTML = isError ? '<span class="error">' + cleanMsg(html) + '</span>' : cleanMsg(html);
        success.classList.add('show');
        if (!isError) confetti();
        var bar = success.querySelector('.sonic-kiosk-success-bar span');
        if (bar) { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = ''; }
        clearTimeout(successTimer);
        successTimer = setTimeout(function () { success.classList.remove('show'); memberID.focus(); lastAct = Date.now(); }, isError ? 3000 : 5000);
    }

    /* ===== SUBMIT ===== */
    form.addEventListener('submit', function (e) {
        e.preventDefault(); act();
        var mid = memberID.value.trim(); if (mid === '') { memberID.focus(); return; }
        var btn = form.querySelector('button[type="submit"]'); if (btn) btn.disabled = true;
        var fd = new FormData(form);
        fetch(cfg.action || form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.new_token) { latestToken = res.new_token; if (token) token.value = res.new_token; }
                if (photo) { photo.src = './images/persons/' + encodeURIComponent(res.image || 'person.png'); photo.onerror = function () { this.src = './images/persons/person.png'; }; }
                var isErr = !!res.status; // core: status = getError() → error bila terisi
                showSuccess(res.message || (isErr ? 'Terjadi kesalahan.' : 'Terima kasih!'), isErr);
                speak(res.message || '');
                if (!isErr) { bumpStat('today'); bumpStat('total'); }
                form.reset(); if (token) token.value = latestToken;
            })
            .catch(function () { showSuccess('Terjadi kesalahan. Coba lagi.', true); })
            .finally(function () { if (btn) btn.disabled = false; });
    });

    /* ===== BATCH 6: SCAN QR KARTU ANGGOTA (html5-qrcode lokal) ===== */
    (function initScan() {
        if (!cfg.qr || typeof Html5Qrcode === 'undefined') return;
        var btn = $('sonicScanBtn'), panel = $('sonicQrPanel'), statusEl = $('sonicQrStatus'), closeBtn = $('sonicScanClose');
        if (!btn || !panel || !$('sonicQrReader')) return;
        var scanner = null, scanning = false, handled = false;
        function setStatus(t) { if (statusEl) statusEl.textContent = t; }
        function stop() {
            if (scanner && scanning) {
                try {
                    scanner.stop().then(function () { scanning = false; try { scanner.clear(); } catch (e) {} }).catch(function () { scanning = false; });
                } catch (e) { scanning = false; }
            }
            panel.hidden = true;
        }
        stopScan = stop; // dipakai idle & showSuccess
        function onDecoded(text) {
            if (handled) return;
            var id = String(text || '').trim();
            // Hanya terima ID anggota polos; tolak QR buku/URL agar tidak salah check-in
            if (/^https?:\/\//i.test(id) || id.length < 2 || id.length > 40 || !/^[A-Za-z0-9._\-]+$/.test(id)) {
                setStatus('QR ini bukan kartu anggota. Arahkan QR kartu anggota.');
                return;
            }
            handled = true;
            stop();
            memberID.value = id;
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
        btn.addEventListener('click', function () {
            act(); handled = false;
            panel.hidden = false;
            if (!window.isSecureContext) {
                setStatus('Kamera butuh HTTPS atau localhost. Silakan ketik ID anggota manual.');
                return;
            }
            setStatus('Memulai kamera…');
            try {
                scanner = new Html5Qrcode('sonicQrReader');
                scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } }, onDecoded, function () {})
                    .then(function () { scanning = true; setStatus('Arahkan QR kartu anggota ke kamera'); })
                    .catch(function (err) { setStatus('Tidak bisa mengakses kamera. Silakan ketik ID anggota manual.'); });
            } catch (e) { setStatus('Scanner tidak tersedia. Silakan ketik ID anggota manual.'); }
        });
        if (closeBtn) closeBtn.addEventListener('click', function () { stop(); memberID.focus(); });
    })();

    memberID.focus();
})();