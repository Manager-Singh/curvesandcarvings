/**
 * PDP Figma interactions: payment card selection, advance price, buy-now.
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    function formatInr(amount) {
        return '₹' + Math.round(amount).toLocaleString('en-IN');
    }

    function getFinalPrice() {
        var $priceBox = $('[data-role=priceBox]').first();
        var $final = $priceBox.find('[data-price-type=finalPrice] .price').first();
        if (!$final.length) {
            $final = $priceBox.find('.price-wrapper .price').first();
        }
        var text = ($final.text() || '').replace(/[^\d.]/g, '');
        var val = parseFloat(text);
        return isNaN(val) ? 0 : val;
    }

    function updateAdvancePrice() {
        var price = getFinalPrice();
        if (price <= 0) {
            return;
        }
        var advance = price * 0.5;
        $('.cc-advance-price').each(function () {
            $(this).text(formatInr(advance));
        });
    }

    function initPaymentCards() {
        var $cards = $('.cc-payment-card');
        if (!$cards.length) {
            return;
        }

        function syncSelected($card) {
            $cards.removeClass('is-selected');
            $card.addClass('is-selected');
            $card.find('input[type=radio]').prop('checked', true).trigger('click');
            if (typeof window.opConfig !== 'undefined' && window.opConfig.reloadPrice) {
                window.opConfig.reloadPrice();
            }
        }

        $cards.each(function () {
            var $card = $(this);
            if ($card.find('input[type=radio]').is(':checked')) {
                $card.addClass('is-selected');
            }
        });

        $cards.on('click', function (e) {
            if ($(e.target).hasClass('k-more') || $(e.target).closest('.k-more').length) {
                return;
            }
            syncSelected($(this));
        });
    }

    function initBuyNow() {
        $('#product-buy-now-button').on('click', function (e) {
            e.preventDefault();
            var $form = $('#product_addtocart_form');
            $.ajax({
                url: $form.attr('action'),
                data: $form.serialize(),
                type: 'post',
                showLoader: true
            }).done(function () {
                customerData.reload(['cart'], true);
                window.location.href = '/checkout/';
            }).fail(function () {
                $form.trigger('submit');
            });
        });
    }

    function hideBrokenStock() {
        $('.catalog-product-view .stock').each(function () {
            if ($(this).text().indexOf('%1') !== -1) {
                $(this).hide();
            }
        });
    }

    return function () {
        initPaymentCards();
        initBuyNow();
        hideBrokenStock();
        updateAdvancePrice();

        $('[data-role=priceBox]').on('updatePrice', function () {
            updateAdvancePrice();
        });

        $(document).on('change', '.swatch-option', function () {
            setTimeout(updateAdvancePrice, 400);
        });
    };
});
