/**
 * SONIC INFO DECK — Auto-enhance Library Information page
 * Inject icons ke heading, wrap contact info jadi grid cards
 */
jQuery(function($) {
    'use strict';
    
    var $deck = $('#sonicInfoDeck');
    if (!$deck.length) return;
    
    var $inner = $deck.find('.sonic-info-deck-inner');
    
    // ===== 1. Inject icons ke setiap heading berdasarkan keyword =====
    $inner.find('h1, h2, h3').each(function() {
        var text = $(this).text().toLowerCase();
        var iconClass = 'fas fa-info-circle';
        
        if (text.includes('contact') || text.includes('kontak')) {
            iconClass = 'fas fa-address-card';
        } else if (text.includes('opening') || text.includes('hours') || text.includes('jam')) {
            iconClass = 'far fa-clock';
        } else if (text.includes('collection') || text.includes('koleksi')) {
            iconClass = 'fas fa-book-open';
        } else if (text.includes('member') || text.includes('anggota')) {
            iconClass = 'fas fa-id-card';
        } else if (text.includes('rule') || text.includes('aturan') || text.includes('regulation')) {
            iconClass = 'fas fa-gavel';
        } else if (text.includes('service') || text.includes('layanan')) {
            iconClass = 'fas fa-concierge-bell';
        } else if (text.includes('fee') || text.includes('biaya') || text.includes('denda')) {
            iconClass = 'fas fa-coins';
        } else if (text.includes('location') || text.includes('lokasi') || text.includes('map')) {
            iconClass = 'fas fa-map-marked-alt';
        } else if (text.includes('history') || text.includes('sejarah')) {
            iconClass = 'fas fa-landmark';
        } else if (text.includes('vision') || text.includes('visi') || text.includes('misi')) {
            iconClass = 'fas fa-eye';
        }
        
        // Inject icon sebelum text heading
        if (!$(this).find('.sonic-section-icon').length) {
            $(this).prepend('<span class="sonic-section-icon"><i class="' + iconClass + '"></i></span>');
        }
    });
    
    // ===== 2. Wrap Contact Information jadi grid cards =====
    // Cari heading "Contact Information" lalu bungkus isinya
    $inner.find('h1, h2, h3').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.includes('contact') || text.includes('kontak')) {
            var $heading = $(this);
            var $cards = $();
            var $next = $heading.next();
            
            // Kumpulkan semua elemen sampai heading berikutnya
            while ($next.length && !$next.is('h1, h2, h3')) {
                $cards = $cards.add($next);
                $next = $next.next();
            }
            
            if ($cards.length > 0) {
                $cards.wrapAll('<div class="sonic-contact-grid"></div>');
            }
        }
    });
    
    // ===== 3. Style individual contact fields jadi cards =====
    $inner.find('.sonic-contact-grid p').each(function() {
        var $p = $(this);
        var text = $p.text().toLowerCase();
        var icon = 'fas fa-info-circle';
        
        if (text.includes('address') || text.includes('alamat')) {
            icon = 'fas fa-map-marker-alt';
        } else if (text.includes('phone') || text.includes('telepon') || text.includes('nomor')) {
            icon = 'fas fa-phone-alt';
        } else if (text.includes('fax')) {
            icon = 'fas fa-fax';
        } else if (text.includes('email') || text.includes('surel') || text.includes('@')) {
            icon = 'fas fa-envelope';
        } else if (text.includes('web') || text.includes('website') || text.includes('url')) {
            icon = 'fas fa-globe';
        }
        
        // Inject icon ke strong label
        $p.find('strong, b').each(function() {
            if (!$(this).find('i').length) {
                $(this).prepend('<i class="' + icon + '" style="margin-right:0.5rem;"></i>');
            }
        });
    });
    
    // ===== 4. Wrap Opening Hours jadi timeline =====
    $inner.find('h1, h2, h3').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.includes('opening') || text.includes('hours') || text.includes('jam')) {
            var $heading = $(this);
            var $hours = $();
            var $next = $heading.next();
            
            while ($next.length && !$next.is('h1, h2, h3')) {
                $hours = $hours.add($next);
                $next = $next.next();
            }
            
            if ($hours.length > 0) {
                $hours.wrapAll('<div class="sonic-hours-timeline"></div>');
            }
        }
    });
    
    // ===== 5. Inject icon ke hari di Opening Hours =====
    $inner.find('.sonic-hours-timeline p strong, .sonic-hours-timeline p b').each(function() {
        var text = $(this).text().toLowerCase();
        var icon = 'far fa-calendar';
        
        if (text.includes('monday') || text.includes('senin')) icon = 'fas fa-calendar-day';
        else if (text.includes('saturday') || text.includes('sabtu')) icon = 'fas fa-calendar-week';
        else if (text.includes('sunday') || text.includes('minggu') || text.includes('ahad')) icon = 'fas fa-calendar-check';
        else if (text.includes('friday') || text.includes('jumat')) icon = 'fas fa-mosque';
        
        if (!$(this).find('i').length) {
            $(this).prepend('<i class="' + icon + '" style="margin-right:0.5rem;"></i>');
        }
    });
    
    // ===== 6. Collections & Membership cards =====
    $inner.find('h1, h2, h3').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.includes('collection') || text.includes('koleksi') || 
            text.includes('member') || text.includes('anggota')) {
            var $heading = $(this);
            var $next = $heading.next('p');
            if ($next.length && !$next.find('.sonic-info-card').length) {
                $next.addClass('sonic-info-card');
            }
        }
    });
    
    console.log('%c SONIC Info Deck Enhanced', 'color: #00f0ff; font-size: 14px; font-weight: bold;');
});