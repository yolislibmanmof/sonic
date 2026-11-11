/**
 * SONIC FOOTER — Interactive Elements
 */
jQuery(function($) {
    'use strict';

    // ===== Stats Counter Animation =====
    var statsAnimated = false;
    var $stats = $('.sonic-stat-number');
    
    function animateStats() {
        if (statsAnimated) return;
        
        $stats.each(function() {
            var $this = $(this);
            var target = parseInt($this.data('target'), 10);
            var duration = 2000;
            var step = target / (duration / 16);
            var current = 0;
            
            var timer = setInterval(function() {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                $this.text(Math.floor(current).toLocaleString());
            }, 16);
        });
        
        statsAnimated = true;
    }

    // Trigger animation when footer is visible
    var $footer = $('#sonicFooter');
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                animateStats();
            }
        });
    }, { threshold: 0.3 });
    
    observer.observe($footer[0]);

    // ===== Smooth Scroll for Footer Links =====
    $('.sonic-footer-links a').on('click', function(e) {
        var href = $(this).attr('href');
        if (href && href.startsWith('#')) {
            e.preventDefault();
            var $target = $(href);
            if ($target.length) {
                $('html, body').animate({ scrollTop: $target.offset().top - 100 }, 600);
            }
        }
    });

    // ===== Search Box Focus Effect =====
    $('.sonic-search-box input').on('focus', function() {
        $(this).closest('.sonic-search-box').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.sonic-search-box').removeClass('focused');
    });

    console.log('%c SONIC Footer v2 Loaded', 'color: #00f0ff; font-size: 14px; font-weight: bold;');
});