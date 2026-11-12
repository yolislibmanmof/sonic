/**
 * SONIC OPAC — jQuery Behaviors (FASE 1: kontrak inti SLiMS 9.8)
 * Bookmark & basket memakai endpoint resmi core; share URL detail; boot sekali/sesi; ESC.
 */
jQuery(function ($) {
    'use strict';

    function detailUrl(id) {
        return new URL('index.php?p=show_detail&id=' + encodeURIComponent(id), window.location.href).href;
    }
    function setBadge(count) {
        var $link = $('.sonic-navbar a[href*="sec=title_basket"]');
        var $b = $link.find('.sonic-badge-count');
        if (!$b.length) { $b = $('<span class="sonic-badge-count" id="count-basket"></span>').appendTo($link); }
        $b.text(count);
    }

    /* ===== 1. CITATION POPUP (colorbox) ===== */
    if ($.fn.colorbox) {
        $(document).on('click', '.openPopUp.citationLink', function (e) {
            e.preventDefault();
            $.colorbox({ iframe: true, href: $(this).attr('href'), width: '780px', height: '600px', maxWidth: '95%', className: 'sonic-colorbox' });
        });
    }

    /* ===== 2. BOOKMARK (core: INSERT IGNORE / delete_bookmark — toggle BENAR) ===== */
    $(document).on('click', '.bookMarkBook', function (e) {
        e.preventDefault();
        var $b = $(this), id = $b.data('id');
        var removing = $b.hasClass('active');
        var payload = { bookmark_id: id, callback: 'json' };
        if (removing) payload.delete_bookmark = true;
        $.post('index.php?p=member', payload)
            .done(function (res) {
                if (typeof res === 'string') {
                    try { res = JSON.parse(res); }
                    catch (err) { window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href); return; }
                }
                if (res && res.status) {
                    var on = !removing;
                    $('.bookMarkBook[data-id="' + id + '"]').toggleClass('active', on);
                    var $lbl = $('#label-' + id);
                    if ($lbl.length) $lbl.text(on ? ($b.data('label-on') || res.label || 'Bookmarked') : ($b.data('label-off') || 'Bookmark'));
                    if (window.toastr) toastr.success(res.message || (on ? 'Bookmark disimpan' : 'Bookmark dihapus'));
                } else if (window.toastr) { toastr.error((res && res.message) || 'Gagal menyimpan bookmark'); }
            })
            .fail(function () {
                window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href);
            });
    });

    /* ===== 3. ADD TO BASKET (core: POST biblio[] + callback=json) ===== */
    $(document).on('click', '.add-to-chart-button', function (e) {
        e.preventDefault();
        var id = $(this).data('biblio') || $(this).data('id');
        $.post('index.php?p=member', { biblio: [id], callback: 'json' })
            .done(function (data) {
                if (typeof data === 'string') {
                try { data = JSON.parse(data); }
                catch (err) { window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href); return; }
                }
                if (data && data.status) {
                    if (window.toastr) toastr.success(data.message || 'Ditambahkan ke basket');
                    if (typeof data.count !== 'undefined') setBadge(data.count);
                } else if (window.toastr) { toastr.error((data && data.message) || 'Gagal menambah ke basket'); }
            })
            .fail(function () {
                window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href);
            });
    });

    /* ===== 4. SHARE MODAL (URL detail per item, bukan URL halaman) ===== */
    $(document).on('click', '[data-target="#mediaSocialModal"]', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var title = $(this).data('title') || document.title;
        var url = id ? detailUrl(id) : window.location.href;
        var enc = encodeURIComponent;
        $('#mediaSocialModalBody').html(
            '<div style="display:grid;gap:.6rem;">' +
            '<a class="sonic-mini-btn" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=' + enc(url) + '"><i class="fab fa-facebook-square"></i> Facebook</a>' +
            '<a class="sonic-mini-btn" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=' + enc(title) + '&url=' + enc(url) + '"><i class="fab fa-twitter"></i> Twitter</a>' +
            '<a class="sonic-mini-btn" target="_blank" rel="noopener" href="https://wa.me/?text=' + enc(title + ' ' + url) + '"><i class="fab fa-whatsapp"></i> WhatsApp</a>' +
            '<a class="sonic-mini-btn" target="_blank" rel="noopener" href="https://t.me/share/url?url=' + enc(url) + '&text=' + enc(title) + '"><i class="fab fa-telegram"></i> Telegram</a>' +
            '<button type="button" class="sonic-mini-btn" id="sonicCopyLink"><i class="fas fa-link"></i> Copy Link</button>' +
            '</div>'
        );
        $('#mediaSocialModal').addClass('active');
        $('#sonicCopyLink').on('click', function () {
            var ta = document.createElement('textarea');
            ta.value = url; document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); ta.remove();
            if (window.toastr) toastr.success('Link disalin');
        });
    });

    /* ===== 5. TOASTR ===== */
    if (window.toastr) {
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 3500, newestOnTop: true };
    }

    /* ===== 6. BACK-TO-TOP ===== */
    var $top = $('<button class="sonic-totop" title="Kembali ke atas"><i class="fas fa-rocket"></i></button>').appendTo('body');
    $(window).on('scroll', function () { $top.toggleClass('show', $(window).scrollTop() > 400); });
    $top.on('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== 7. CTRL+K PALETTE ===== */
    var $pal = $(
        '<div class="sonic-palette" id="sonicPalette"><div class="sonic-palette-box"><i class="fas fa-search"></i>' +
        '<input type="text" id="sonicPaletteInput" placeholder="Ketik judul / penulis lalu Enter..." autocomplete="off"/><kbd>ESC</kbd></div></div>'
    ).appendTo('body');
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 'k') { e.preventDefault(); $pal.addClass('active'); $('#sonicPaletteInput').trigger('focus'); }
        if (e.key === 'Escape') {
            $pal.removeClass('active');
            $('.sonic-modal.active, .sonic-adv-modal.active').removeClass('active'); // ESC tutup modal
        }
    });
    $pal.on('click', function (e) { if (e.target === this) $pal.removeClass('active'); });
    $('#sonicPaletteInput').on('keydown', function (e) {
        if (e.key === 'Enter' && this.value.trim()) { window.location = 'index.php?keywords=' + encodeURIComponent(this.value.trim()) + '&search=search'; }
    });

    /* ===== 8. BOOT SCREEN — sekali per sesi ===== */
    function killBoot() { $('#sonicBoot').addClass('done'); setTimeout(function () { $('#sonicBoot').remove(); }, 700); }
    try {
        if (sessionStorage.getItem('sonicBooted')) { $('#sonicBoot').remove(); }
        else { sessionStorage.setItem('sonicBooted', '1'); $(window).on('load', killBoot); setTimeout(killBoot, 2500); }
    } catch (e) { $(window).on('load', killBoot); setTimeout(killBoot, 2500); }
});