/**
 * SONIC OPAC — jQuery Behaviors
 * Mempertahankan fungsi bawaan OPAC: bookmark, basket, share, citation popup
 * + fitur baru: back-to-top, Ctrl+K palette, toastr neon, boot screen
 */
jQuery(function ($) {
    'use strict';

    /* ===== 1. CITATION POPUP (colorbox) — fungsi bawaan ===== */
    if ($.fn.colorbox) {
        $(document).on('click', '.openPopUp.citationLink', function (e) {
            e.preventDefault();
            $.colorbox({
                iframe: true,
                href: $(this).attr('href'),
                width: '780px',
                height: '600px',
                maxWidth: '95%',
                className: 'sonic-colorbox'
            });
        });
    }

    /* ===== 2. BOOKMARK — fungsi bawaan ===== */
    $(document).on('click', '.bookMarkBook', function (e) {
        e.preventDefault();
        var $b = $(this), id = $b.data('id');
        function done() {
            var lbl = $('#label-' + id);
            if (lbl.length) {
                lbl.text(lbl.text().trim() === 'Bookmarked' ? 'Bookmark' : 'Bookmarked');
            }
            $b.toggleClass('active');
            if (window.toastr) toastr.success('Bookmark diperbarui');
        }
        $.getJSON('index.php?p=api/member/bookmark', { biblio_id: id })
            .done(done)
            .fail(function () {
                $.post('index.php?p=member&sec=bookmark', { biblio_id: id })
                    .done(done)
                    .fail(function () {
                        window.location = 'index.php?p=member&destination=' + encodeURIComponent(window.location.href);
                    });
            });
    });

    /* ===== 3. ADD TO BASKET — fungsi bawaan ===== */
    $(document).on('click', '.add-to-chart-button', function (e) {
        e.preventDefault();
        var id = $(this).data('biblio') || $(this).data('id');
        $.getJSON('index.php?p=api/member/basket', { biblio_id: id })
            .done(function () {
                if (window.toastr) toastr.success('Ditambahkan ke basket');
                var c = $('#count-basket');
                if (c.length) c.text(parseInt(c.text() || '0', 10) + 1);
            })
            .fail(function () {
                window.location = 'index.php?p=member&sec=title_basket';
            });
    });

    /* ===== 4. SHARE MODAL — fungsi bawaan (versi SONIC) ===== */
    $(document).on('click', '[data-target="#mediaSocialModal"]', function (e) {
        e.preventDefault();
        var title = $(this).data('title') || document.title;
        var url = window.location.href;
        var enc = encodeURIComponent;
        $('#mediaSocialModalBody').html(
            '<div style="display:grid;gap:.6rem;">' +
            '<a class="sonic-mini-btn" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=' + enc(url) + '"><i class="fab fa-facebook-square"></i> Facebook</a>' +
            '<a class="sonic-mini-btn" target="_blank" href="https://twitter.com/intent/tweet?text=' + enc(title) + '&url=' + enc(url) + '"><i class="fab fa-twitter"></i> Twitter</a>' +
            '<a class="sonic-mini-btn" target="_blank" href="https://wa.me/?text=' + enc(title + ' ' + url) + '"><i class="fab fa-whatsapp"></i> WhatsApp</a>' +
            '<a class="sonic-mini-btn" target="_blank" href="https://t.me/share/url?url=' + enc(url) + '&text=' + enc(title) + '"><i class="fab fa-telegram"></i> Telegram</a>' +
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

    /* ===== 5. TOASTR NEON CONFIG ===== */
    if (window.toastr) {
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 3500, newestOnTop: true };
    }

    /* ===== 6. BACK-TO-TOP ROCKET (fitur baru) ===== */
    var $top = $('<button class="sonic-totop" title="Kembali ke atas"><i class="fas fa-rocket"></i></button>').appendTo('body');
    $(window).on('scroll', function () { $top.toggleClass('show', $(window).scrollTop() > 400); });
    $top.on('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ===== 7. CTRL+K COMMAND PALETTE (fitur baru) ===== */
    var $pal = $(
        '<div class="sonic-palette" id="sonicPalette">' +
        '<div class="sonic-palette-box"><i class="fas fa-search"></i>' +
        '<input type="text" id="sonicPaletteInput" placeholder="Ketik judul / penulis lalu Enter..." autocomplete="off"/>' +
        '<kbd>ESC</kbd></div></div>'
    ).appendTo('body');
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 'k') {
            e.preventDefault(); $pal.addClass('active'); $('#sonicPaletteInput').trigger('focus');
        }
        if (e.key === 'Escape') $pal.removeClass('active');
    });
    $pal.on('click', function (e) { if (e.target === this) $pal.removeClass('active'); });
    $('#sonicPaletteInput').on('keydown', function (e) {
        if (e.key === 'Enter' && this.value.trim()) {
            window.location = 'index.php?keywords=' + encodeURIComponent(this.value.trim()) + '&search=search';
        }
    });

    /* ===== 8. BOOT SCREEN OFF ===== */
    function killBoot() { $('#sonicBoot').addClass('done'); setTimeout(function () { $('#sonicBoot').remove(); }, 700); }
    $(window).on('load', killBoot);
    setTimeout(killBoot, 2500); // pengaman
});