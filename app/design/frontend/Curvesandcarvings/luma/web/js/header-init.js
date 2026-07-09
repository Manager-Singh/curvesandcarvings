define(['jquery'], function ($) {
    'use strict';

    $('.hedserch01icon').on('click', function (e) {
        e.preventDefault();
        $('.hedserch01serch').toggle();
    });

    // Mage-OS ships listing buttons disabled until catalogAddToCart initializes; match live behavior.
    function enableAddToCartButtons() {
        $('form[data-role="tocart-form"] .action.tocart').prop('disabled', false);
        $('#product-addtocart-button').prop('disabled', false);
    }

    enableAddToCartButtons();
    $(document).on('contentUpdated', enableAddToCartButtons);
});
