/**
 * SONIC FACET — sinkronisasi sidebar filter dengan URL + auto-submit
 */
jQuery(function ($) {
    'use strict';
    var params = new URLSearchParams(window.location.search);

    // Sinkronkan nilai select/input dengan filter aktif
    $('.sonic-facet-select').each(function () {
        var v = params.get(this.name);
        if (v) $(this).val(v);
    });
    var py = $('.sonic-facet-input[name="publishyear"]');
    if (py.length && params.get('publishyear')) py.val(params.get('publishyear'));

    // Auto-submit saat dropdown berubah (rasa aplikasi modern)
    $(document).on('change', '.sonic-facet-select', function () {
        $('#sonicFacetForm').trigger('submit');
    });

    // Collapse di layar kecil
    $(document).on('click', '#sonicFacetToggle', function () {
        $('#sonicFacet').toggleClass('collapsed');
    });
    if ($(window).width() < 900) $('#sonicFacet').addClass('collapsed');
});