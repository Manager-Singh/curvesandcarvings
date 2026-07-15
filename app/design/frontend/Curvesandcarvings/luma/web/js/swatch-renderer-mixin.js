/**
 * Rebuild Figma variation cards after Magento swatch controls render.
 */
define(['jquery'], function ($) {
    'use strict';

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', SwatchRenderer, {
            _RenderControls: function () {
                this._super();
                $(document).trigger('cc:buildVariationCards');
            }
        });

        return $.mage.SwatchRenderer;
    };
});
