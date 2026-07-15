/**
 * Standalone Figma variation cards — no dependency on swatch-renderer DOM.
 */
define(['jquery', 'domReady!'], function ($) {
    'use strict';

    var cachedConfig = null;

    function readData($el, key) {
        var value = $el.attr('data-' + key);
        if (value === undefined || value === null || value === '') {
            value = $el.data(key);
        }
        return value;
    }

    function parseJsonConfig() {
        if (cachedConfig) {
            return cachedConfig;
        }

        var $jsonNode = $('#cc-variation-json-config');
        if ($jsonNode.length) {
            try {
                cachedConfig = JSON.parse($jsonNode.text().trim());
                return cachedConfig;
            } catch (e) {
                // fall through
            }
        }

        return null;
    }

    function formatInr(amount) {
        return '₹' + Math.round(amount).toLocaleString('en-IN');
    }

    function ensureAttributeInputs(config) {
        var $form = $('#product_addtocart_form');
        if (!$form.length || !config.attributes) {
            return;
        }

        $.each(config.attributes, function (attrId, attribute) {
            var name = 'super_attribute[' + attrId + ']';
            var $input = $form.find('input[name="' + name + '"]');

            if (!$input.length) {
                $input = $('<input/>', {
                    type: 'hidden',
                    'class': 'swatch-input super-attribute-select',
                    name: name,
                    'data-selector': name,
                    'data-attr-code': attribute.code || '',
                    'data-validate': '{required:true}',
                    value: ''
                });
                $form.append($input);
            }
        });
    }

    function updatePrice(childId, config) {
        try {
            var prices = config.optionPrices && config.optionPrices[childId];
            if (!prices) {
                return;
            }

            var $priceBox = $('.cc-pdp-price-header [data-role=priceBox], .product-info-price [data-role=priceBox]').first();
            if ($priceBox.length && $priceBox.data('mage-priceBox')) {
                var displayPrices = $.extend(true, {}, $priceBox.priceBox('option').prices);
                $.each(displayPrices, function (code) {
                    if (prices[code] && displayPrices[code]) {
                        displayPrices[code].amount = prices[code].amount - displayPrices[code].amount;
                    }
                });
                $priceBox.trigger('updatePrice', { prices: displayPrices });
            }

            var finalAmount = prices.finalPrice && prices.finalPrice.amount;
            if (finalAmount) {
                $priceBox.find('[data-price-type=finalPrice] .price').text(formatInr(finalAmount));
            }
        } catch (e) {
            // never block selection on price widget errors
        }
    }

    function updateGallery(childId, config) {
        try {
            var images = config.images && config.images[childId];
            if (!images || !images.length) {
                return;
            }

            var $gallery = $('[data-gallery-role=gallery-placeholder]');

            function applyImages() {
                var gallery = $gallery.data('gallery');
                if (!gallery) {
                    return;
                }
                gallery.updateData($.extend(true, [], images));
            }

            if ($gallery.data('gallery')) {
                applyImages();
            } else {
                $gallery.one('gallery:loaded', applyImages);
            }
        } catch (e) {
            // never block selection on gallery widget errors
        }
    }

    function selectCard($card, config) {
        var optionId = String(readData($card, 'option-id') || '');
        var childId = String(readData($card, 'child-id') || '');
        var $group = $card.closest('.cc-pdp-variation-group');
        var attrId = String(readData($group, 'attribute-id') || '');

        if (!optionId || !childId || !attrId) {
            return;
        }

        $('.cc-pdp-variations .cc-variation-card').removeClass('selected');
        $card.addClass('selected');

        var $form = $('#product_addtocart_form');
        $form.find('input[name="super_attribute[' + attrId + ']"]').val(optionId).trigger('change');
        $form.find('input[name="selected_configurable_option"]').val(childId);

        updatePrice(childId, config);
        updateGallery(childId, config);
        $(document).trigger('cc:variationSelected', [childId, optionId, attrId]);
    }

    function bindCards(config) {
        var $container = $('.cc-pdp-variations');
        if (!$container.length) {
            return;
        }

        $container.off('click.ccVariation keydown.ccVariation');
        $container.on('click.ccVariation', '.cc-variation-card', function (event) {
            event.preventDefault();
            selectCard($(this), config);
        });
        $container.on('keydown.ccVariation', '.cc-variation-card', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                selectCard($(this), config);
            }
        });
    }

    function init() {
        var config = parseJsonConfig();
        if (!config) {
            return;
        }

        ensureAttributeInputs(config);
        bindCards(config);

        var $first = $('.cc-pdp-variations .cc-variation-card').first();
        if ($first.length && !$('.cc-pdp-variations .cc-variation-card.selected').length) {
            selectCard($first, config);
        }
    }

    return function () {
        init();
    };
});
