/** SONIC PLATFORM — BATCH 8: tema dark/light/auto + kontrol font + skip-link. */
(function () {
    'use strict';
    var P = (window.SONIC_CFG || {}).platform || {};
    var d = document.documentElement;
    function eff(m) { return m === 'auto' ? (window.matchMedia && matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark') : m; }
    function curMode() { try { return localStorage.getItem('sonicTheme') || P.theme || 'auto'; } catch (e) { return P.theme || 'auto'; } }
    function curFont() { try { return parseInt(localStorage.getItem('sonicFontScale') || '100', 10) || 100; } catch (e) { return 100; } }
    function applyTheme(m) {
        d.setAttribute('data-theme', eff(m)); d.setAttribute('data-theme-mode', m);
        try { localStorage.setItem('sonicTheme', m); } catch (e) {}
        var b = document.getElementById('sonicThemeBtn');
        if (b) { b.setAttribute('data-mode', m); b.title = 'Theme: ' + m; b.innerHTML = m === 'light' ? '<i class="fas fa-sun"></i>' : (m === 'dark' ? '<i class="fas fa-moon"></i>' : '<i class="fas fa-adjust"></i>'); }
    }
    function applyFont(p) { p = Math.max(80, Math.min(140, p)); d.style.fontSize = p + '%'; try { localStorage.setItem('sonicFontScale', String(p)); } catch (e) {} }
    function init() {
        applyTheme(curMode());
        var tb = document.getElementById('sonicThemeBtn');
        if (tb) tb.addEventListener('click', function () { var o = ['auto', 'dark', 'light']; applyTheme(o[(o.indexOf(curMode()) + 1) % 3]); });
        if (window.matchMedia) { try { matchMedia('(prefers-color-scheme: light)').addEventListener('change', function () { if (curMode() === 'auto') applyTheme('auto'); }); } catch (e) {} }
        var fm = document.getElementById('sonicFontMinus'), fr = document.getElementById('sonicFontReset'), fp = document.getElementById('sonicFontPlus');
        if (fm) fm.addEventListener('click', function () { applyFont(curFont() - 10); });
        if (fr) fr.addEventListener('click', function () { applyFont(100); });
        if (fp) fp.addEventListener('click', function () { applyFont(curFont() + 10); });
        var sk = document.querySelector('.sonic-skip-link');
        if (sk) sk.addEventListener('click', function () { var t = document.getElementById('sonicMain'); if (t) t.focus(); });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();