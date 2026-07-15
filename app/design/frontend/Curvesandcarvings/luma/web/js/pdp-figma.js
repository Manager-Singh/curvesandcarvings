/**
 * PDP Figma helpers — keep lean to avoid browser freezes.
 * Layout order is handled mainly by CSS; JS only nudges payment once.
 */
define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    var orderFixed = false;
    var baseFinal = 0;

    function parsePrice(text) {
        var val = parseFloat(String(text || '').replace(/[^\d.]/g, ''));
        return isNaN(val) ? 0 : val;
    }

    function captureBasePrice() {
        var $amount = $('.cc-pdp-price-header [data-price-type=finalPrice]').first();
        var amt = parseFloat($amount.attr('data-price-amount') || 0);
        if (!amt) {
            amt = parsePrice($amount.find('.price').first().text());
        }
        if (amt > 0) {
            baseFinal = amt;
        }
    }

    function updateAdvancePrice() {
        if (baseFinal <= 0) {
            return;
        }
        var advance = baseFinal * 0.5;
        $('.cc-advance-price').text(
            '₹' + advance.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );
    }

    function updateEmiDisplay() {
        var $emi = $('.cc-emi-monthly, .lowemishowin').filter(function () {
            return !$(this).data('cc-emi-set');
        }).first();

        if (!$emi.length || baseFinal <= 0) {
            return;
        }

        var roi = (13 / 12) / 100;
        var months = 36;
        var factor = Math.pow(1 + roi, months);
        var emi = Math.round(baseFinal * roi * (factor / (factor - 1)));

        $emi.text('EMI starting at Rs. ' + emi.toLocaleString('en-IN'));
        $emi.data('cc-emi-set', true);
    }

    /**
     * Move payment once between variations and ATC.
     * Do not fight Magento priceOptions / reloadPrice loops.
     */
    function fixFormOrder() {
        if (orderFixed) {
            return;
        }

        var $form = $('#product_addtocart_form');
        if (!$form.length) {
            return;
        }

        var $bottom = $form.find('.cc-pdp-options-bottom').first();
        if (!$bottom.length) {
            $bottom = $form.find('.product-options-bottom').first();
        }

        var $paymentField = $form.find('.field').has('.cc-payment-options').first();
        if (!$paymentField.length) {
            return;
        }

        if (!$paymentField.parent().hasClass('cc-pdp-payment-section')) {
            $paymentField.wrap('<div class="cc-pdp-payment-section"></div>');
        }

        var $paymentSection = $paymentField.parent();
        if ($bottom.length && !$paymentSection.next().is($bottom) && !$bottom.prev().is($paymentSection)) {
            $paymentSection.insertBefore($bottom);
        }

        $form.find('.product-options-wrapper .fieldset > .field').each(function () {
            var $field = $(this);
            if ($field.find('.cc-payment-options, .cc-pdp-variations, .swatch-attribute').length) {
                return;
            }
            $field.addClass('cc-pdp-hidden-option');
        });

        orderFixed = true;
    }

    function ensurePaymentSelected() {
        var $cards = $('.cc-payment-card');
        if (!$cards.length) {
            return;
        }
        if (!$cards.find('input[type=radio]:checked').length) {
            var $first = $cards.first();
            $first.find('input[type=radio]').prop('checked', true);
            $first.addClass('is-selected');
        }
    }

    function initPaymentCards() {
        var $cards = $('.cc-payment-card');
        if (!$cards.length) {
            return;
        }

        $cards.each(function () {
            if ($(this).find('input[type=radio]').is(':checked')) {
                $(this).addClass('is-selected');
            }
        });

        $cards.off('click.ccPayment').on('click.ccPayment', function (e) {
            if ($(e.target).closest('.k-more').length) {
                return;
            }
            e.preventDefault();

            var $card = $(this);
            var $radio = $card.find('input[type=radio]');

            $cards.removeClass('is-selected');
            $card.addClass('is-selected');
            $radio.prop('checked', true);
            // Do NOT call opConfig.reloadPrice() — it fights header price and can freeze Chrome.
        });

        ensurePaymentSelected();
        updateAdvancePrice();
        updateEmiDisplay();
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

    function hideBrokenStock() {
        $('.catalog-product-view .availability.only.configurable-variation-qty').hide();
        $('.catalog-product-view .stock').each(function () {
            if ($(this).text().indexOf('%1') !== -1) {
                $(this).hide();
            }
        });
        $('.catalog-product-view .product-info-stock-sku .stock.available').hide();
    }

    return function () {
        captureBasePrice();
        fixFormOrder();
        initPaymentCards();
        initQtyStepper();
        initBuyNow();
        hideBrokenStock();
        clearStuckLoaders();

        $('#product_addtocart_form').on('submit', function () {
            ensurePaymentSelected();
        });

        // One delayed pass only (widgets settle) — never loop on price events
        setTimeout(function () {
            fixFormOrder();
            initPaymentCards();
            clearStuckLoaders();
        }, 600);

        setTimeout(clearStuckLoaders, 2500);
    };
});
