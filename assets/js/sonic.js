/**
 * SONIC OPAC v2 — Hyperdrive Engine (FINAL + HYBRID SLIDER foto/video)
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ===== 1. NAVBAR: scroll state + progress bar + burger ===== */
    var navbar = document.getElementById('sonicNavbar');
    var progress = document.getElementById('sonicProgress');
    var burger = document.getElementById('sonicBurger');
    var nav = document.getElementById('sonicNav');

    function onScroll() {
        var h = document.documentElement;
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);
        if (progress) {
            var max = h.scrollHeight - h.clientHeight;
            progress.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%';
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    if (burger && nav) burger.addEventListener('click', function () { nav.classList.toggle('open'); });

    /* ===== 2. HERO HYBRID SLIDER (foto 6 dtk + video maks 30 dtk, slide TANPA BATAS) =====
     * - Foto: ganti tiap 6 detik (ritme lama dipertahankan).
     * - Video: diputar muted (autoplay aman), lanjut saat selesai ATAU di detik ke-30
     *   (mana yang lebih dulu). Video > 30 dtk otomatis dipotong di 30.
     * - Pause saat hover / tab hidden / hero keluar layar; hormati prefers-reduced-motion.
     * - Tombol suara (muncul bila ada slide video), badge VIDEO + durasi, swipe di mobile.
     * - Video korup/hilang → slide降级 jadi foto poster / latar, slider tidak macet.
     */
    var slides = document.querySelectorAll('.sonic-slide');
    if (slides.length > 0) {
        var dotsWrap = document.getElementById('sliderDots');
        var badge = document.getElementById('sliderBadge');
        var badgeTime = document.getElementById('sliderBadgeTime');
        var soundBtn = document.getElementById('sliderSound');
        var hero = document.getElementById('sonicSlider');
        var current = 0, last = 0, t = 0, dur = 6;
        var hover = false, inView = true;
        var muted = true;
        try { var sv = localStorage.getItem('sonicSliderMuted'); if (sv !== null) muted = sv === '1'; } catch (e) {}

        var videos = [];
        slides.forEach(function (el) { videos.push(el.querySelector('video')); });

        // Pengaman lama: isi background dari data-bg bila ada; biarkan bila sudah inline.
        function ensureBg(el) {
            if (!el) return;
            var bg = el.getAttribute('data-bg');
            if (bg && !el.style.backgroundImage) el.style.backgroundImage = "url('" + bg + "')";
        }
        slides.forEach(function (el) { ensureBg(el); });

        function fmtTime(s) {
            s = Math.max(0, Math.round(s));
            return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        }
        function slideDur(i) {
            var el = slides[i], v = videos[i];
            var cap = parseFloat(el.getAttribute('data-duration')) || 30;
            if (v && isFinite(v.duration) && v.duration > 0.2) return Math.min(v.duration, cap);
            return v ? cap : 6;
        }
        function running() {
            return !reduceMotion && !document.hidden && !hover && inView;
        }
        function saveMuted() {
            try { localStorage.setItem('sonicSliderMuted', muted ? '1' : '0'); } catch (e) {}
        }
        function playActive() {
            var v = videos[current];
            if (!v) return;
            v.muted = muted;
            var p = v.play();
            if (p && p.catch) p.catch(function () {
                if (!v.muted) {
                    // Autoplay bersuara ditolak browser → ulangi tanpa suara.
                    v.muted = true; muted = true; saveMuted(); syncSound();
                    var p2 = v.play();
                    if (p2 && p2.catch) p2.catch(function () {});
                }
            });
        }
        function pauseActive() { var v = videos[current]; if (v) v.pause(); }
        function sync() { if (running()) playActive(); else pauseActive(); }

        function syncSound() {
            if (!soundBtn) return;
            var v = videos[current];
            soundBtn.disabled = !v;
            soundBtn.setAttribute('aria-pressed', String(!muted));
            soundBtn.setAttribute('aria-label', v ? (muted ? 'Aktifkan suara video' : 'Matikan suara video') : 'Suara hanya untuk slide video');
            var ic = soundBtn.querySelector('i');
            if (ic) ic.className = (v && !muted) ? 'fas fa-volume-up' : 'fas fa-volume-mute';
            soundBtn.classList.toggle('on', !!v && !muted);
        }
        function syncBadge() {
            if (!badge) return;
            if (videos[current]) {
                badge.hidden = false;
                if (badgeTime) badgeTime.textContent = fmtTime(slideDur(current));
            } else {
                badge.hidden = true;
            }
        }

        if (slides.length > 1 && dotsWrap) {
            slides.forEach(function (el, i) {
                var d = document.createElement('button');
                d.type = 'button';
                d.className = 'sonic-dot-btn' + (i === 0 ? ' active' : '') + (videos[i] ? ' is-video' : '');
                d.setAttribute('aria-label', 'Slide ' + (i + 1) + (videos[i] ? ' (video)' : ''));
                d.addEventListener('click', function () { go(i); });
                dotsWrap.appendChild(d);
            });
        }
        var dots = dotsWrap ? dotsWrap.querySelectorAll('.sonic-dot-btn') : [];

        function go(i) {
            if (slides.length < 2) {
                // Satu slide saja: video tetap diputar & di-loop, foto diam.
                t = 0; dur = slideDur(0);
                var v0 = videos[0];
                if (v0) { try { v0.currentTime = 0; } catch (e) {} }
                syncBadge(); syncSound(); sync();
                return;
            }
            var old = current;
            slides[old].classList.remove('active');
            if (dots[old]) dots[old].classList.remove('active');
            var ov = videos[old];
            if (ov) { ov.pause(); try { ov.currentTime = 0; } catch (e) {} }

            current = (i + slides.length) % slides.length;
            ensureBg(slides[current]);
            slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');

            t = 0; dur = slideDur(current);
            var v = videos[current];
            if (v) { try { v.currentTime = 0; } catch (e) {} }
            var nv = videos[(current + 1) % slides.length];
            if (nv && nv.preload !== 'auto') nv.preload = 'auto';

            syncBadge(); syncSound(); sync();
        }

        /* Jam utama: satu loop rAF (foto pakai timer, video pakai currentTime asli) */
        function tick(now) {
            requestAnimationFrame(tick);
            var dt = Math.min(0.1, (now - last) / 1000);
            last = now;
            if (!running()) return;
            var v = videos[current];
            if (v && !v.paused && v.readyState >= 2 && isFinite(v.duration)) t = v.currentTime;
            else t += dt;
            if (t >= dur - 0.05) {
                if (slides.length > 1) { go(current + 1); }
                else if (v) { try { v.currentTime = 0; } catch (e) {} t = 0; playActive(); }
            }
        }

        /* Listener media: sekali per elemen, anti duplikat */
        videos.forEach(function (v, i) {
            if (!v) return;
            v.muted = true;
            v.addEventListener('loadedmetadata', function () {
                if (i === current) { dur = slideDur(i); syncBadge(); }
            });
            v.addEventListener('ended', function () {
                if (i !== current || !running()) return;
                if (slides.length > 1) go(current + 1);
                else { try { v.currentTime = 0; } catch (e) {} t = 0; playActive(); }
            });
            v.addEventListener('error', function () {
                if (v.__failed) return;
                v.__failed = true;
                var el = slides[i];
                var poster = v.getAttribute('poster');
                videos[i] = null;
                if (v.parentNode) v.parentNode.removeChild(v);
                el.classList.remove('sonic-slide-video');
                el.classList.add('sonic-slide-failed');
                el.setAttribute('data-type', 'image');
                if (poster) el.style.backgroundImage = "url('" + poster + "')";
                if (dots[i]) dots[i].classList.remove('is-video');
                if (i === current) { dur = slideDur(i); t = 0; syncBadge(); syncSound(); }
            });
        });

        var prev = document.getElementById('sliderPrev');
        var next = document.getElementById('sliderNext');
        if (prev) prev.addEventListener('click', function () { go(current - 1); });
        if (next) next.addEventListener('click', function () { go(current + 1); });

        if (soundBtn) soundBtn.addEventListener('click', function () {
            muted = !muted; saveMuted();
            var v = videos[current];
            if (v) { v.muted = muted; if (!muted && v.paused && running()) playActive(); }
            syncSound();
        });

        if (hero) {
            hero.addEventListener('mouseenter', function () { hover = true; sync(); });
            hero.addEventListener('mouseleave', function () { hover = false; sync(); });
            // Swipe (sentuh & drag mouse) — navigasi di mobile yang panahnya disembunyikan CSS
            var px = 0, py = 0, pid = null;
            hero.addEventListener('pointerdown', function (e) {
                if (e.target.closest('a,button')) return;
                pid = e.pointerId; px = e.clientX; py = e.clientY;
            });
            hero.addEventListener('pointerup', function (e) {
                if (pid === null || e.pointerId !== pid) return;
                pid = null;
                var dx = e.clientX - px, dy = e.clientY - py;
                if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.4) go(current + (dx < 0 ? 1 : -1));
            });
            hero.addEventListener('pointercancel', function () { pid = null; });
        }

        document.addEventListener('visibilitychange', function () { last = performance.now(); sync(); });
        if ('IntersectionObserver' in window && hero) {
            new IntersectionObserver(function (en) {
                inView = en[0].isIntersecting;
                last = performance.now(); sync();
            }, { threshold: 0.15 }).observe(hero);
        }

        /* Start */
        dur = slideDur(0);
        syncBadge(); syncSound();
        var nv0 = videos[1 % slides.length];
        if (nv0 && nv0.preload !== 'auto') nv0.preload = 'auto';
        last = performance.now();
        requestAnimationFrame(tick);
        sync();
    }

    /* ===== 3. SEARCH ENGINE: mode tags + recent searches ===== */
    var input = document.getElementById('sonic-keywords');
    var form = document.getElementById('sonicSearchForm');

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function renderRecent() {
        var box = document.getElementById('sonicRecent');
        var list = document.getElementById('sonicRecentList');
        if (!box || !list) return;
        var rec = [];
        try { rec = JSON.parse(localStorage.getItem('sonicRecent') || '[]'); } catch (e) {}
        if (!rec.length) return;
        box.style.display = 'block';
        list.innerHTML = '';
        rec.forEach(function (r) {
            var a = document.createElement('a');
            a.className = 'sonic-tag';
            a.href = 'index.php?' + encodeURIComponent(r.mode) + '=' + encodeURIComponent(r.text) + '&search=search';
            a.innerHTML = '<i class="fas fa-history"></i>' + escapeHtml(r.text);
            list.appendChild(a);
        });
    }

    if (input && form) {
        var modeBtns = document.querySelectorAll('[data-search-mode]');
        modeBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                modeBtns.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                input.name = btn.getAttribute('data-search-mode');
                if (input.value.trim() !== '') {
                    form.submit();          // LANGSUNG CARI!
                } else {
                    input.focus();
                }
            });
        });
        form.addEventListener('submit', function () {
            var v = input.value.trim();
            if (!v) return;
            var rec = [];
            try { rec = JSON.parse(localStorage.getItem('sonicRecent') || '[]'); } catch (e) {}
            rec = rec.filter(function (r) { return r.text !== v; });
            rec.unshift({ mode: input.name, text: v });
            localStorage.setItem('sonicRecent', JSON.stringify(rec.slice(0, 6)));
        });
        renderRecent();
    }

    /* ===== 4. ADVANCED SEARCH BUTTON ===== */
    var advBtn = document.getElementById('sonicAdvBtn');
    if (advBtn) advBtn.addEventListener('click', function () {
        var m = document.getElementById('adv-modal');
        if (m) m.classList.add('active');
    });

    /* ===== 5. TITLE SCRAMBLE-DECODE EFFECT (hormati reduced-motion) ===== */
    var title = document.getElementById('sonicTitle');
    if (title) {
        var finalText = title.getAttribute('data-text') || title.textContent;
        if (reduceMotion) {
            title.textContent = finalText;
        } else {
            var glyphs = '#01<>[]{}=+*^?/_—';
            var frame = 0;
            var iv = setInterval(function () {
                frame++;
                var revealed = Math.floor(frame / 2);
                var out = '';
                for (var i = 0; i < finalText.length; i++) {
                    if (i < revealed || finalText[i] === ' ') out += finalText[i];
                    else out += glyphs[Math.floor(Math.random() * glyphs.length)];
                }
                title.textContent = out;
                if (revealed >= finalText.length) { clearInterval(iv); title.textContent = finalText; }
            }, 40);
        }
    }

    /* ===== 6. SPOTLIGHT CARDS (rAF-throttled — halus, tanpa layout thrash) ===== */
    document.querySelectorAll('.sonic-book, .sonic-list-card').forEach(function (card) {
        var pending = false, mx = 0, my = 0;
        card.addEventListener('mousemove', function (e) {
            mx = e.clientX; my = e.clientY;
            if (pending) return;
            pending = true;
            requestAnimationFrame(function () {
                pending = false;
                var r = card.getBoundingClientRect();
                if (r.width > 0 && r.height > 0) {
                    card.style.setProperty('--mouse-x', ((mx - r.left) / r.width) * 100 + '%');
                    card.style.setProperty('--mouse-y', ((my - r.top) / r.height) * 100 + '%');
                }
            });
        }, { passive: true });
    });

    /* ===== 7. MODALS ===== */
    window.closeCitationModal = function () {
        document.querySelectorAll('.sonic-modal, .sonic-adv-modal').forEach(function (m) { m.classList.remove('active'); });
    };
    document.querySelectorAll('.sonic-modal, .sonic-adv-modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('active'); });
    });

    console.log('%c⚡ SONIC OPAC v2 ⚡', 'color:#00f0ff;font-size:28px;font-weight:900;text-shadow:0 0 20px #00f0ff;');
});

/* ===== SMART NAVBAR OVERFLOW (Priority+) — navbar selalu rapi satu baris ===== */
(function () {
    'use strict';
    var nav = document.getElementById('sonicNav');
    if (!nav) return;
    var moreLi = null, moreMenu = null;

    function buildMore() {
        if (moreLi) return;
        moreLi = document.createElement('li');
        moreLi.className = 'dropdown sonic-nav-more';
        moreLi.innerHTML = '<a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Menu lainnya"><i class="fas fa-ellipsis-h"></i></a><div class="dropdown-menu dropdown-menu-right"></div>';
        moreMenu = moreLi.querySelector('.dropdown-menu');
        var prot = nav.querySelector('.sonic-nav-protected');
        if (prot) nav.insertBefore(moreLi, prot); else nav.appendChild(moreLi);
    }
    function menuItems() { return Array.prototype.slice.call(nav.querySelectorAll(':scope > li.sonic-nav-item')); }
    function restoreAll() {
        if (!moreMenu) return;
        var moved = Array.prototype.slice.call(moreMenu.querySelectorAll('li.sonic-nav-item'));
        moved.sort(function (a, b) { return (+a.dataset.idx) - (+b.dataset.idx); });
        moved.forEach(function (li) { nav.insertBefore(li, moreLi); });
        moreMenu.innerHTML = '';
    }
    function layout() {
        if (window.innerWidth <= 900) { if (moreLi) { restoreAll(); moreLi.style.display = 'none'; } return; }
        buildMore();
        moreLi.style.display = '';
        restoreAll();
        menuItems().forEach(function (li, i) { li.dataset.idx = i; });
        var guard = 0;
        while (nav.scrollWidth > nav.clientWidth + 1 && guard < 60) {
            var vis = menuItems().filter(function (li) { return li.parentNode === nav; });
            if (vis.length <= 1) break;
            moreMenu.appendChild(vis[vis.length - 1]);
            guard++;
        }
        moreLi.style.display = moreMenu.children.length ? '' : 'none';
    }
    var rt = null;
    window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(layout, 120); });
    window.addEventListener('load', layout);
    if (document.readyState === 'complete') layout();
    else document.addEventListener('DOMContentLoaded', layout);
})();

/* ===== 8. VOICE SEARCH (Web Speech API — graceful fallback, tanpa dependensi) ===== */
(function () {
    'use strict';
    function init() {
        var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        var btn = document.getElementById('sonicVoiceBtn');
        var input = document.getElementById('sonic-keywords');
        var form = document.getElementById('sonicSearchForm');
        var status = document.getElementById('sonicVoiceStatus');
        var text = document.getElementById('sonicVoiceText');
        var cancel = document.getElementById('sonicVoiceCancel');
        if (!SR || !btn || !input || !form || !status || !text || !cancel) return;

        btn.hidden = false;
        var wrap = btn.closest('.sonic-input-wrap');
        if (wrap) wrap.classList.add('sonic-voice-on');

        var isID = (window.SONIC_CFG && SONIC_CFG.lang === 'id');
        var L = isID ? {
            listen: 'Mendengarkan… sebut judul, penulis, atau subjek.',
            heard: 'Terdengar: ',
            searching: 'Mencari koleksi…',
            denied: 'Izin mikrofon ditolak. Aktifkan izin situs lalu coba lagi.',
            nospeech: 'Tidak ada suara terdeteksi. Coba lagi.',
            network: 'Layanan suara browser memerlukan koneksi. Silakan ketik manual.',
            mic: 'Mikrofon tidak ditemukan.',
            generic: 'Pencarian suara gagal. Silakan ketik manual.'
        } : {
            listen: 'Listening… say a title, author, or subject.',
            heard: 'Heard: ',
            searching: 'Searching collections…',
            denied: 'Microphone permission denied. Allow site access and retry.',
            nospeech: 'No speech detected. Try again.',
            network: 'Browser speech service needs a connection. Please type instead.',
            mic: 'No microphone found.',
            generic: 'Voice search failed. Please type instead.'
        };

        var rec = null, listening = false, submitting = false, submitTimer = null;

        function setStatus(msg, mode) {
            status.hidden = false;
            status.classList.toggle('listening', mode === 'listen');
            status.classList.toggle('err', mode === 'err');
            text.textContent = msg;
        }
        function toast(msg, type) {
            if (window.toastr) toastr[type || 'warning'](msg);
            else setStatus(msg, 'err');
        }
        function cleanup() {
            listening = false;
            btn.setAttribute('aria-pressed', 'false');
            status.classList.remove('listening');
            if (submitTimer) { clearTimeout(submitTimer); submitTimer = null; }
        }
        function stop() {
            if (rec) { try { rec.abort(); } catch (e) {} }
            cleanup();
            status.hidden = true;
        }
        function start() {
            if (listening) return;
            if (submitTimer) { clearTimeout(submitTimer); submitTimer = null; }
            if (rec) { try { rec.abort(); } catch (e) {} }
            rec = new SR();
            rec.lang = isID ? 'id-ID' : 'en-US';
            rec.interimResults = true;
            rec.maxAlternatives = 1;
            rec.continuous = false;

            rec.onstart = function () {
                listening = true;
                btn.setAttribute('aria-pressed', 'true');
                setStatus(L.listen, 'listen');
            };
            rec.onresult = function (e) {
                var interim = '', final = '';
                for (var i = e.resultIndex; i < e.results.length; i++) {
                    if (e.results[i].isFinal) final += e.results[i][0].transcript;
                    else interim += e.results[i][0].transcript;
                }
                if (final) {
                    input.value = final.trim();
                    setStatus(L.heard + '"' + input.value + '" — ' + L.searching, 'listen');
                    submitting = true;
                    cleanup();
                    submitTimer = setTimeout(function () {
                        if (form.requestSubmit) form.requestSubmit();
                        else form.submit();
                    }, 700);
                } else if (interim) {
                    setStatus('"' + interim.trim() + '"', 'listen');
                }
            };
            rec.onerror = function (e) {
                cleanup();
                if (e.error === 'aborted') { status.hidden = true; return; }
                var m = L.generic;
                if (e.error === 'not-allowed' || e.error === 'service-not-allowed') m = L.denied;
                else if (e.error === 'no-speech') m = L.nospeech;
                else if (e.error === 'network') m = L.network;
                else if (e.error === 'audio-capture') m = L.mic;
                toast(m, 'error');
                setStatus(m, 'err');
            };
            rec.onend = function () {
                if (!submitting) cleanup();
                submitting = false;
            };
            try { rec.start(); } catch (e) { toast(L.generic, 'error'); }
        }

        btn.addEventListener('click', function () { listening ? stop() : start(); });
        cancel.addEventListener('click', stop);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && listening) stop(); });
        window.addEventListener('pagehide', function () { if (rec) { try { rec.abort(); } catch (e) {} } });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();

/* ===== 10. HOTFIX BASIS URL COVER (kompensasi rail "Terakhir Dilihat") =====
   Beberapa modul lama menyusun URL cover dengan prefix keliru
   ".../template/<tpl>/api/lib/minigalnano/...". Listener capture ini
   menulis ulang ke path core yang benar saat error 404, sekali per gambar.
   Aditif: tidak menghapus fitur rail; akar masalah tetap di sonic-premium.js. */
(function () {
    'use strict';
    document.addEventListener('error', function (e) {
        var img = e.target;
        if (!img || img.tagName !== 'IMG' || img.dataset.sonicImgFix) return;
        var src = img.getAttribute('src') || '';
        if (!/\/template\/[^/]+\/api\/lib\/minigalnano\//.test(src)) return;
        img.dataset.sonicImgFix = '1';
        img.src = src.replace(/\/template\/[^/]+\/api\/lib\/minigalnano\//, '/lib/minigalnano/');
    }, true);
})();