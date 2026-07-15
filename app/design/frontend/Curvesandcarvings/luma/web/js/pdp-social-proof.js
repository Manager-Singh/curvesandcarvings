/**
 * Persist PDP social-proof numbers in localStorage per product.
 * Views: 20–100; purchases: 10–19.
 */
define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    var STORAGE_PREFIX = 'cc_pdp_social_';

    function clamp(value, min, max) {
        var n = parseInt(value, 10);
        if (isNaN(n)) {
            return min;
        }
        return Math.min(max, Math.max(min, n));
    }

    function readStored(productId) {
        try {
            var raw = window.localStorage.getItem(STORAGE_PREFIX + productId);
            if (!raw) {
                return null;
            }
            var data = JSON.parse(raw);
            if (!data || typeof data !== 'object') {
                return null;
            }
            return {
                views: clamp(data.views, 20, 100),
                purchases: clamp(data.purchases, 10, 19)
            };
        } catch (e) {
            return null;
        }
    }

    function writeStored(productId, views, purchases) {
        try {
            window.localStorage.setItem(
                STORAGE_PREFIX + productId,
                JSON.stringify({
                    views: views,
                    purchases: purchases,
                    savedAt: Date.now()
                })
            );
        } catch (e) {
            /* private mode / quota — ignore */
        }
    }

    return function (config, element) {
        var $root = $(element);
        if (!$root.length) {
            return;
        }

        var productId = String($root.attr('data-product-id') || '');
        if (!productId || productId === '0') {
            return;
        }

        var stored = readStored(productId);
        var views = stored
            ? stored.views
            : clamp($root.attr('data-views'), 20, 100);
        var purchases = stored
            ? stored.purchases
            : clamp($root.attr('data-purchases'), 10, 19);

        if (!stored) {
            writeStored(productId, views, purchases);
        }

        $root.attr('data-views', views);
        $root.attr('data-purchases', purchases);
        $root.find('[data-role=cc-social-views]').text(views + ' people');
        $root.find('[data-role=cc-social-purchases]').text(purchases + ' customers purchased');
    };
});
