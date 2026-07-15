/**
 * Ensure form_key cookie matches the session hidden input.
 * Magento mini-cart remove reads $.mage.cookies.get('form_key'); without this
 * cookie (common when FPC is disabled / theme drops form_key_provider) remove fails
 * with "Invalid Form Key".
 */
define([
    'jquery',
    'mage/cookies',
    'domReady!'
], function ($) {
    'use strict';

    return function () {
        var $inputs = $('input[name="form_key"]');
        var fromInput = $inputs.filter(function () {
            return !!(this.value && String(this.value).length);
        }).first().val();
        var fromCookie = $.mage.cookies.get('form_key');
        var formKey = fromInput || fromCookie;

        if (!formKey) {
            return;
        }

        if (fromCookie !== formKey) {
            $.mage.cookies.set('form_key', formKey, {
                lifetime: 86400,
                path: '/',
                secure: window.cookiesConfig ? !!window.cookiesConfig.secure : true,
                samesite: (window.cookiesConfig && window.cookiesConfig.samesite) || 'Lax'
            });
        }

        $inputs.val(formKey);
    };
});
