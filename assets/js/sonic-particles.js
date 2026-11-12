/**
 * SONIC OPAC — Floating Particles (configurable via Kustomisasi)
 * window.SONIC_CFG.particle: books | stars | bolts | mixed | none
 */
(function () {
    'use strict';
    var CFG = window.SONIC_CFG || {};
    var PTYPE = CFG.particle || 'books';
    if (PTYPE === 'none') return;

    var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ===== LAYER CANVAS ===== */
    var canvas = document.createElement('canvas');
    canvas.id = 'sonicParticles';
    canvas.setAttribute('aria-hidden', 'true');
    document.body.insertBefore(canvas, document.body.firstChild);
    var ctx = canvas.getContext('2d');

    var PALETTE = {
        books: ['#00f0ff', '#ff00aa', '#7a5cff', '#e6f7ff'],
        stars: ['#ffd700', '#00f0ff', '#ffffff', '#ff00aa'],
        bolts: ['#00f0ff', '#ffe600', '#ff00aa', '#e6f7ff'],
        mixed: ['#00f0ff', '#ff00aa', '#ffd700', '#7a5cff']
    }[PTYPE] || ['#00f0ff', '#ff00aa', '#7a5cff', '#e6f7ff'];
    var TYPES = {
        books: ['page', 'page', 'page', 'book', 'bookopen'],
        stars: ['star', 'star', 'star', 'spark'],
        bolts: ['bolt', 'bolt', 'spark'],
        mixed: ['page', 'book', 'star', 'bolt', 'bookopen']
    }[PTYPE] || ['page', 'page', 'book'];
    var SIZES = [16, 22, 30, 40];

    function rgba(hex, a) {
        var n = parseInt(hex.slice(1), 16);
        return 'rgba(' + (n >> 16 & 255) + ',' + (n >> 8 & 255) + ',' + (n & 255) + ',' + a + ')';
    }

    /* ===== SPRITE PRE-RENDER (glow baked-in => performa tinggi) ===== */
    function makeSprite(type, color, s) {
        var dim = Math.ceil(s * 2);
        var c = document.createElement('canvas');
        c.width = dim; c.height = dim;
        var g = c.getContext('2d');
        g.translate(dim / 2, dim / 2);
        g.strokeStyle = color; g.fillStyle = color;
        g.lineJoin = 'round'; g.lineCap = 'round';
        g.shadowColor = color; g.shadowBlur = s * 0.5;
        g.lineWidth = Math.max(1.1, s * 0.07);

        if (type === 'page') {
            var w = s * 0.78, h = s * 1.0, f = s * 0.26;
            g.beginPath();
            g.moveTo(-w / 2, -h / 2); g.lineTo(w / 2 - f, -h / 2); g.lineTo(w / 2, -h / 2 + f);
            g.lineTo(w / 2, h / 2); g.lineTo(-w / 2, h / 2); g.closePath();
            g.save(); g.globalAlpha = 0.12; g.fill(); g.restore();
            g.stroke();
            g.beginPath();
            g.moveTo(w / 2 - f, -h / 2); g.lineTo(w / 2 - f, -h / 2 + f); g.lineTo(w / 2, -h / 2 + f);
            g.stroke();
            g.save(); g.shadowBlur = s * 0.2; g.globalAlpha = 0.8; g.lineWidth = Math.max(1, s * 0.05);
            for (var i = 0; i < 3; i++) {
                var ly = -h / 2 + f + s * 0.22 + i * s * 0.2;
                g.beginPath();
                g.moveTo(-w / 2 + s * 0.14, ly);
                g.lineTo(w / 2 - s * 0.14 - (i === 2 ? s * 0.22 : 0), ly);
                g.stroke();
            }
            g.restore();
        } else if (type === 'book') {
            var bw = s * 0.82, bh = s * 0.62;
            g.beginPath();
            if (g.roundRect) g.roundRect(-bw / 2, -bh / 2, bw, bh, s * 0.08);
            else g.rect(-bw / 2, -bh / 2, bw, bh);
            g.save(); g.globalAlpha = 0.12; g.fill(); g.restore();
            g.stroke();
            g.beginPath(); g.moveTo(-bw / 2 + s * 0.16, -bh / 2); g.lineTo(-bw / 2 + s * 0.16, bh / 2); g.stroke();
            g.save(); g.globalAlpha = 0.8; g.shadowBlur = s * 0.2;
            g.beginPath(); g.moveTo(-bw / 2 + s * 0.3, -bh / 2 + s * 0.16); g.lineTo(bw / 2 - s * 0.14, -bh / 2 + s * 0.16); g.stroke();
            g.restore();
        } else if (type === 'bookopen') {
            var ow = s * 0.5, oh = s * 0.42;
            g.beginPath();
            g.moveTo(0, -oh * 0.35);
            g.quadraticCurveTo(-ow * 0.55, -oh, -ow, -oh * 0.75);
            g.lineTo(-ow, oh * 0.55);
            g.quadraticCurveTo(-ow * 0.5, oh * 0.3, 0, oh * 0.65);
            g.quadraticCurveTo(ow * 0.5, oh * 0.3, ow, oh * 0.55);
            g.lineTo(ow, -oh * 0.75);
            g.quadraticCurveTo(ow * 0.55, -oh, 0, -oh * 0.35);
            g.closePath();
            g.save(); g.globalAlpha = 0.12; g.fill(); g.restore();
            g.stroke();
            g.beginPath(); g.moveTo(0, -oh * 0.35); g.lineTo(0, oh * 0.65); g.stroke();
        } else if (type === 'star') {
            var R = s * 0.5, r2 = s * 0.22, pts = [];
            for (var k = 0; k < 10; k++) {
                var ang = -Math.PI / 2 + k * Math.PI / 5, rad = (k % 2 === 0) ? R : r2;
                pts.push([Math.cos(ang) * rad, Math.sin(ang) * rad]);
            }
            g.beginPath(); g.moveTo(pts[0][0], pts[0][1]);
            for (var k2 = 1; k2 < 10; k2++) g.lineTo(pts[k2][0], pts[k2][1]);
            g.closePath();
            g.save(); g.globalAlpha = 0.15; g.fill(); g.restore(); g.stroke();
        } else if (type === 'bolt') {
            var u = s / 20;
            g.beginPath();
            g.moveTo(2 * u, -10 * u); g.lineTo(-4 * u, 1 * u); g.lineTo(0, 1 * u);
            g.lineTo(-2 * u, 10 * u); g.lineTo(4 * u, -1 * u); g.lineTo(0, -1 * u); g.closePath();
            g.save(); g.globalAlpha = 0.15; g.fill(); g.restore(); g.stroke();
        } else { /* spark */
            var L = s * 0.5;
            g.beginPath(); g.moveTo(0, -L); g.lineTo(0, L); g.moveTo(-L, 0); g.lineTo(L, 0); g.stroke();
            g.save(); g.globalAlpha = 0.6;
            g.beginPath(); g.moveTo(-L * 0.5, -L * 0.5); g.lineTo(L * 0.5, L * 0.5);
            g.moveTo(L * 0.5, -L * 0.5); g.lineTo(-L * 0.5, L * 0.5); g.stroke();
            g.restore();
        }
        return c;
    }

    function makeGlint(color, s) {
        var dim = Math.ceil(s * 4), c = document.createElement('canvas');
        c.width = c.height = dim;
        var g = c.getContext('2d'), r = dim / 2;
        var grad = g.createRadialGradient(r, r, 0, r, r, r);
        grad.addColorStop(0, rgba(color, 0.9));
        grad.addColorStop(0.3, rgba(color, 0.35));
        grad.addColorStop(1, rgba(color, 0));
        g.fillStyle = grad;
        g.fillRect(0, 0, dim, dim);
        return c;
    }

    /* ===== STATE ===== */
    var W = 0, H = 0, DPR = 1, parts = [], glints = [];
    var scrollY = window.scrollY || 0;
    var mx = 0, my = 0, tmx = 0, tmy = 0;

    function targetCount() { return Math.max(14, Math.min(40, Math.round((W * H) / 42000))); }

    function makePart() {
        var z = 0.45 + Math.random() * 0.75;
        var si = z < 0.68 ? 0 : (z < 0.88 ? 1 : (z < 1.05 ? 2 : 3));
        var ci = Math.random() < 0.55 ? 0 : (Math.random() < 0.6 ? 3 : (Math.random() < 0.5 ? 1 : 2));
        return {
            sp: makeSprite(TYPES[Math.floor(Math.random() * TYPES.length)], PALETTE[ci], SIZES[si]),
            x: Math.random() * W, y: Math.random() * (H + 160) - 80, z: z,
            vy: 6 + Math.random() * 14,
            swA: 12 + Math.random() * 30, swF: 0.25 + Math.random() * 0.5,
            flF: 0.5 + Math.random() * 0.9,
            ph: Math.random() * Math.PI * 2, ph2: Math.random() * Math.PI * 2,
            rot: (Math.random() - 0.5) * 0.9, vr: (Math.random() - 0.5) * 0.25,
            al: 0.10 + z * 0.14
        };
    }

    function makeGlintP() {
        var ci = Math.random() < 0.6 ? 0 : 1;
        return {
            sp: makeGlint(PALETTE[ci], 3 + Math.random() * 3),
            x: Math.random() * W, y: Math.random() * H,
            z: 0.4 + Math.random() * 0.8,
            vy: 4 + Math.random() * 8,
            ph: Math.random() * 6.28,
            tw: 0.6 + Math.random() * 1.6
        };
    }

    function resize() {
        DPR = Math.min(2, window.devicePixelRatio || 1);
        W = window.innerWidth; H = window.innerHeight;
        canvas.width = W * DPR; canvas.height = H * DPR;
        canvas.style.width = W + 'px'; canvas.style.height = H + 'px';
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
        var n = targetCount(), gn = Math.round(n * 0.7);
        while (parts.length < n) parts.push(makePart());
        if (parts.length > n) parts.length = n;
        while (glints.length < gn) glints.push(makeGlintP());
        if (glints.length > gn) glints.length = gn;
    }

    /* ===== RENDER LOOP ===== */
    var last = 0;
    function draw(t) {
        ctx.clearRect(0, 0, W, H);
        var i, p, m = 90, span = H + 2 * m;
        for (i = 0; i < parts.length; i++) {
            p = parts[i];
            var yy = p.y - scrollY * 0.10 * p.z;
            yy = (((yy + m) % span) + span) % span - m;
            var xx = p.x + Math.sin(t * p.swF + p.ph) * p.swA + mx * 14 * p.z;
            yy = yy + my * 10 * p.z;
            var sc = 0.4 + 0.6 * Math.abs(Math.sin(t * p.flF + p.ph2));
            var a = p.al * (0.8 + 0.2 * Math.sin(t * 0.9 + p.ph));
            ctx.save();
            ctx.translate(xx, yy);
            ctx.rotate(p.rot + Math.sin(t * p.flF * 0.7 + p.ph) * 0.25);
            ctx.scale(sc, 1);
            ctx.globalAlpha = a;
            ctx.drawImage(p.sp, -p.sp.width / 2, -p.sp.height / 2);
            ctx.restore();
        }
        for (i = 0; i < glints.length; i++) {
            p = glints[i];
            var gy = (((p.y - scrollY * 0.06 * p.z + m) % span) + span) % span - m;
            var tw = Math.sin(t * p.tw + p.ph);
            ctx.globalAlpha = 0.18 + 0.4 * Math.max(0, tw);
            var gs = 0.7 + 0.3 * tw;
            ctx.drawImage(p.sp, p.x + mx * 8 * p.z - p.sp.width / 2 * gs, gy - p.sp.height / 2 * gs, p.sp.width * gs, p.sp.height * gs);
        }
        ctx.globalAlpha = 1;
    }

    function frame(ts) {
        var t = ts / 1000;
        var dt = Math.min(0.05, (t - last) || 0.016);
        last = t;
        mx += (tmx - mx) * 0.04; my += (tmy - my) * 0.04;
        for (var i = 0; i < parts.length; i++) {
            parts[i].y -= parts[i].vy * parts[i].z * dt;
            parts[i].rot += parts[i].vr * dt;
            if (parts[i].y < -90) { parts[i].y = H + 90; parts[i].x = Math.random() * W; }
        }
        for (var j = 0; j < glints.length; j++) {
            glints[j].y -= glints[j].vy * glints[j].z * dt;
            if (glints[j].y < -40) { glints[j].y = H + 40; glints[j].x = Math.random() * W; }
        }
        draw(t);
        if (!document.hidden) requestAnimationFrame(frame);
    }
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !REDUCED) { last = 0; requestAnimationFrame(frame); }
    });

    /* ===== EVENTS ===== */
    window.addEventListener('scroll', function () { scrollY = window.scrollY || 0; }, { passive: true });
    window.addEventListener('mousemove', function (e) {
        tmx = (e.clientX / W - 0.5) * 2; tmy = (e.clientY / H - 0.5) * 2;
    }, { passive: true });
    window.addEventListener('resize', resize);

    function start() {
        resize();
        canvas.classList.add('on');
        if (REDUCED) { draw(1); return; }
        requestAnimationFrame(frame);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();