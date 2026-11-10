/**
 * SONIC MEMBER — count-up statistik + tilt holografik
 */
jQuery(function ($) {
    'use strict';

    // Count-up angka statistik
    $('.sonic-holo-stat .num').each(function () {
        var $n = $(this), target = parseInt($n.data('count') || '0', 10), cur = 0;
        if (target <= 0) { $n.text('0'); return; }
        var step = Math.max(1, Math.round(target / 30));
        var iv = setInterval(function () {
            cur += step;
            if (cur >= target) { cur = target; clearInterval(iv); }
            $n.text(cur);
        }, 40);
    });

    // Tilt 3D kartu holografik
    var card = document.getElementById('sonicHoloCard');
    if (card) {
        card.addEventListener('mousemove', function (e) {
            var r = card.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - 0.5;
            var y = (e.clientY - r.top) / r.height - 0.5;
            card.style.transform = 'perspective(900px) rotateY(' + (x * 8) + 'deg) rotateX(' + (-y * 8) + 'deg)';
        });
        card.addEventListener('mouseleave', function () { card.style.transform = ''; });
    }
});