/**
 * SONIC LIBRARIAN — Profile Card Enhancement
 * Auto-detect markup bawaan SLiMS dan inject icon Font Awesome
 */
jQuery(function($) {
    'use strict';
    
    var $grid = $('#sonicLibrarianGrid');
    if (!$grid.length) return;
    
    // ===== 1. Inject icon ke setiap field info =====
    $grid.find('li, [class*="field"], p').each(function() {
        var text = $(this).text().toLowerCase();
        var $icon = $('<i class="fas fa-user"></i>');
        
        if (text.includes('nama') || text.includes('name')) {
            $icon = $('<i class="fas fa-user"></i>');
        } else if (text.includes('jabatan') || text.includes('position')) {
            $icon = $('<i class="fas fa-briefcase"></i>');
        } else if (text.includes('surel') || text.includes('email') || text.includes('@')) {
            $icon = $('<i class="fas fa-envelope"></i>');
        } else if (text.includes('media sosial') || text.includes('social')) {
            $icon = $('<i class="fas fa-share-alt"></i>');
        } else if (text.includes('telepon') || text.includes('phone') || text.includes('hp')) {
            $icon = $('<i class="fas fa-phone"></i>');
        } else if (text.includes('alamat') || text.includes('address')) {
            $icon = $('<i class="fas fa-map-marker-alt"></i>');
        } else if (text.includes('biografi') || text.includes('bio')) {
            $icon = $('<i class="fas fa-file-alt"></i>');
        }
        
        // Inject icon di depan text (jika belum ada icon)
        if (!$(this).find('i.fas, i.fab').length) {
            $(this).prepend('<span class="sonic-field-icon">' + $icon.prop('outerHTML') + '</span>');
        }
    });
    
    // ===== 2. Style social media links dengan icon yang sesuai =====
    $grid.find('a').each(function() {
        var href = $(this).attr('href') || '';
        var $link = $(this);
        
        if (href.includes('facebook') || href.includes('fb.com')) {
            $link.html('<i class="fab fa-facebook-f"></i>');
        } else if (href.includes('twitter') || href.includes('x.com')) {
            $link.html('<i class="fab fa-twitter"></i>');
        } else if (href.includes('instagram')) {
            $link.html('<i class="fab fa-instagram"></i>');
        } else if (href.includes('linkedin')) {
            $link.html('<i class="fab fa-linkedin-in"></i>');
        } else if (href.includes('youtube')) {
            $link.html('<i class="fab fa-youtube"></i>');
        } else if (href.includes('tiktok')) {
            $link.html('<i class="fab fa-tiktok"></i>');
        } else if (href.includes('wa.me') || href.includes('whatsapp')) {
            $link.html('<i class="fab fa-whatsapp"></i>');
        } else if (href.includes('t.me') || href.includes('telegram')) {
            $link.html('<i class="fab fa-telegram-plane"></i>');
        } else if (href.startsWith('mailto:')) {
            $link.html('<i class="fas fa-envelope"></i> ' + href.replace('mailto:', ''));
        } else if (href.startsWith('http')) {
            $link.html('<i class="fas fa-globe"></i> Website');
        }
    });
    
    // ===== 3. Tambahkan efek tilt 3D pada card =====
    $grid.find('> div').each(function() {
        var $card = $(this);
        
        $card.on('mousemove', function(e) {
            var rect = this.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width - 0.5;
            var y = (e.clientY - rect.top) / rect.height - 0.5;
            
            $card.css('transform', 'perspective(1000px) rotateY(' + (x * 6) + 'deg) rotateX(' + (-y * 6) + 'deg) translateY(-8px)');
        });
        
        $card.on('mouseleave', function() {
            $card.css('transform', '');
        });
    });
    
    // ===== 4. Count-up animation untuk jumlah pustakawan =====
    var count = $grid.find('> div').length;
    if (count > 0) {
        var $header = $('.sonic-librarian-header span');
        $header.text(count + ' ' + $header.text());
    }
    
    console.log('%c SONIC Librarian Cards Enhanced: ' + count + ' profiles', 'color: #00f0ff; font-size: 14px; font-weight: bold;');
});