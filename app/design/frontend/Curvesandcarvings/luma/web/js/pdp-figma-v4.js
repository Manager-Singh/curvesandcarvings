/**
 * PDP Figma v4 — payment/qty/buy-now + EMI/advance from selected variation price.
 * Does not touch Magento priceOptions (that freezes Chrome).
 */
define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    var ready = false;
    var currentFinal = 0;

    function parsePrice(text) {
        var val = parseFloat(String(text || '').replace(/[^\d.]/g, ''));
        return isNaN(val) ? 0 : val;
    }

    function capturePrice(amount) {
        if (amount && amount > 0) {
            currentFinal = amount;
            return;
        }
        var $amount = $('.cc-pdp-price-header [data-price-type=finalPrice]').first();
        var amt = parseFloat($amount.attr('data-price-amount') || 0);
        if (!amt) {
            amt = parsePrice($amount.find('.price').first().text());
        }
        if (amt > 0) {
            currentFinal = amt;
        }
    }

    function updateAdvancePrice() {
        if (currentFinal <= 0) {
            return;
        }
        var advance = currentFinal * 0.5;
        $('.cc-advance-price').text(
            '₹' + advance.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );
    }

    function updateEmiDisplay() {
        if (currentFinal <= 0) {
            return;
        }

        var roi = (13 / 12) / 100;
        var months = 36;
        var factor = Math.pow(1 + roi, months);
        var emi = Math.round(currentFinal * roi * (factor / (factor - 1)));
        var label = 'EMI starting at Rs. ' + emi.toLocaleString('en-IN');

        $('.cc-emi-monthly, .lowemishowin').each(function () {
            $(this).text(label).data('cc-emi-set', true);
        });
    }

    function updateDiscountBadge(finalAmount, oldAmount) {
        var $badge = $('[data-role=cc-discount-badge]');
        if (!$badge.length) {
            return;
        }

        var final = finalAmount > 0 ? finalAmount : currentFinal;
        var old = oldAmount > 0 ? oldAmount : 0;

        if (!old) {
            var $old = $('.cc-pdp-price-header [data-price-type=oldPrice]').first();
            old = parseFloat($old.attr('data-price-amount') || 0);
            if (!old) {
                old = parsePrice($old.find('.price').first().text());
            }
        }

        if (!final) {
            capturePrice(0);
            final = currentFinal;
        }

        if (old > final && final > 0) {
            var pct = Math.round(((old - final) / old) * 100);
            if (pct > 0) {
                $badge.find('.cc-discount-badge-text').text(pct + '% off');
                $badge.prop('hidden', false).removeAttr('hidden');
                return;
            }
        }

        $badge.prop('hidden', true).attr('hidden', 'hidden');
    }

    function refreshPaymentAmounts(finalAmount, oldAmount) {
        capturePrice(finalAmount);
        updateAdvancePrice();
        updateEmiDisplay();
        updateDiscountBadge(finalAmount || 0, oldAmount || 0);
    }

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
        refreshPaymentAmounts();

        document.addEventListener('cc:priceUpdated', function (event) {
            var finalAmount = event.detail && event.detail.final ? event.detail.final : 0;
            var oldAmount = event.detail && event.detail.old ? event.detail.old : 0;
            refreshPaymentAmounts(finalAmount, oldAmount);
        });

        $('#product_addtocart_form').on('submit', ensurePaymentSelected);
        setTimeout(clearStuckLoaders, 2000);
        setTimeout(refreshPaymentAmounts, 400);
    };
});
