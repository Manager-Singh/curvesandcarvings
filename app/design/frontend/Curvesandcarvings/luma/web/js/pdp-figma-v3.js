/**
 * PDP Figma v3 — minimal helpers only.
 * No Magento priceOptions / reloadPrice / priceBox fighting (causes Chrome freeze).
 */
define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    var ready = false;

    function ensurePaymentSelected() {
        var $checked = $('.cc-payment-card input[type=radio]:checked');
        if ($checked.length) {
            $checked.closest('.cc-payment-card').addClass('is-selected');
            return;
        }
        var $first = $('.cc-payment-card').first();
        if ($first.length) {
            $first.find('input[type=radio]').prop('checked', true);
            $first.addClass('is-selected');
        }
    }

    function initPaymentCards() {
        var $cards = $('.cc-payment-card');
        if (!$cards.length) {
            return;
        }

        $cards.off('click.ccPayment').on('click.ccPayment', function (e) {
            if ($(e.target).closest('.k-more').length) {
                return;
            }
            var $card = $(this);
            $cards.removeClass('is-selected');
            $card.addClass('is-selected');
            $card.find('input[type=radio]').prop('checked', true);
        });

        ensurePaymentSelected();
    }

    function initQtyStepper() {
        var $qty = $('#qty');
        if (!$qty.length) {
            return;
        }

        $('.cc-qty-minus').off('click.ccQty').on('click.ccQty', function () {
            var val = parseInt($qty.val(), 10) || 1;
            $qty.val(Math.max(1, val - 1)).trigger('change');
        });

        $('.cc-qty-plus').off('click.ccQty').on('click.ccQty', function () {
            var val = parseInt($qty.val(), 10) || 1;
            $qty.val(val + 1).trigger('change');
        });
    }

    function initBuyNow() {
        $('#product-buy-now-button').off('click.ccBuyNow').on('click.ccBuyNow', function (e) {
            e.preventDefault();
            var $form = $('#product_addtocart_form');
            var formEl = $form.get(0);

            ensurePaymentSelected();

            if (typeof $form.valid === 'function' && !$form.valid()) {
                return;
            }

            if (!$form.find('input[name="cc_buy_now"]').length) {
                $('<input>', { type: 'hidden', name: 'cc_buy_now', value: '1' }).appendTo($form);
            }

            if (formEl) {
                formEl.submit();
            }
        });
    }

    function clearStuckLoaders() {
        $('body > .loading-mask').remove();
        $('.gallery-placeholder').removeClass('_block-content-loading');
        $('.gallery-placeholder > .loading-mask, .gallery-placeholder [data-role="loader"]').remove();
        try {
            $('body').trigger('processStop');
        } catch (e) { /* ignore */ }
    }

    function hideNoise() {
        $('.catalog-product-view .availability.only.configurable-variation-qty').hide();
        $('.catalog-product-view .product-info-stock-sku .stock.available').hide();
        $('.product-options-wrapper .fieldset > .field').each(function () {
            var $field = $(this);
            if ($field.find('.cc-payment-options, .cc-pdp-variations, .swatch-attribute').length) {
                return;
            }
            $field.addClass('cc-pdp-hidden-option');
        });
    }

    return function () {
        if (ready) {
            return;
        }
        ready = true;

        initPaymentCards();
        initQtyStepper();
        initBuyNow();
        hideNoise();
        clearStuckLoaders();

        $('#product_addtocart_form').on('submit', ensurePaymentSelected);
        setTimeout(clearStuckLoaders, 2000);
    };
});
