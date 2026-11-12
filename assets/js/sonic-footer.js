/**
 * SONIC FOOTER — Interactive Elements (FINAL)
 * Ringan: counter pakai requestAnimationFrame (bukan setInterval 16ms),
 * guard bila footer/IO tidak ada, hormati prefers-reduced-motion.
 */
jQuery(function ($) {
    'use strict';

    var statsAnimated = false;
    var $stats = $('.sonic-stat-number');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function animateStats() {
        if (statsAnimated) return;
        statsAnimated = true;
        $stats.each(function () {
            var el = this;
            var target = parseInt(el.getAttribute('data-target'), 10) || 0;
            if (reduceMotion || target <= 0) { el.textContent = target.toLocaleString(); return; }
            var start = null, duration = 1600;
            function step(ts) {
                if (start === null) start = ts;
                var p = Math.min(1, (ts - start) / duration);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.floor(target * eased).toLocaleString();
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }

    var $footer = $('#sonicFooter');
    if ($footer.length && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { animateStats(); observer.disconnect(); }
            });
        }, { threshold: 0.25 });
        observer.observe($footer[0]);
    } else {
        animateStats();
    }

    // Smooth scroll hanya untuk anchor yang benar-benar ada
    $('.sonic-footer-links a').on('click', function (e) {
        var href = $(this).attr('href');
        if (href && href.charAt(0) === '#') {
            var $target = $(href);
            if ($target.length) {
                e.preventDefault();
                $('html, body').animate({ scrollTop: $target.offset().top - 100 }, 600);
            }
        }
    });

    $('.sonic-search-box input').on('focus', function () {
        $(this).closest('.sonic-search-box').addClass('focused');
    }).on('blur', function () {
        $(this).closest('.sonic-search-box').removeClass('focused');
    });
});