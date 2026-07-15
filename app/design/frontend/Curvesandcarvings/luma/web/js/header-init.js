define(['jquery'], function ($) {
    'use strict';

    // Search toggle is handled in Magento_Search::form.mini.phtml (single source).
    // Keep only cart-button enablement here.

    function enableAddToCartButtons() {
        $('form[data-role="tocart-form"] .action.tocart').prop('disabled', false);
        $('#product-addtocart-button').prop('disabled', false);
    }

    enableAddToCartButtons();
    $(document).on('contentUpdated', enableAddToCartButtons);
});
