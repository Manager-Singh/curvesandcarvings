/**
 * Magento admin dynamic-rows for configurable matrix — keep permanently hidden.
 * Curves & Carvings Product Variations panel replaces this UI.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    function hideMatrixDom() {
        var $targets = $(
            '[data-index="configurable-matrix"],' +
            '[data-index="configurable_products_button_set"],' +
            '.admin__field[data-index="configurable-matrix"],' +
            '.admin__field[data-index="configurable_products_button_set"]'
        );
        $targets.hide().attr('style', function (i, style) {
            return (style || '') + ';display:none!important;visibility:hidden!important;height:0!important;overflow:hidden!important;';
        });
    }

    return function (DynamicRowsConfigurable) {
        return DynamicRowsConfigurable.extend({
            defaults: {
                listens: {
                    'insertDataFromGrid': 'processingInsertDataFromGrid',
                    'insertDataFromWizard': 'processingInsertDataFromWizard',
                    'unionInsertData': 'processingUnionInsertData',
                    'changeDataFromGrid': 'processingChangeDataFromGrid'
                }
            },

            initialize: function () {
                this._super();
                this.visible(false);
                hideMatrixDom();
                return this;
            },

            changeVisibility: function () {
                this.visible(false);
                hideMatrixDom();
                return this;
            },

            show: function () {
                this.visible(false);
                hideMatrixDom();
                return this;
            }
        });
    };
});
