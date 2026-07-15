/**
 * PDP safety: clear stuck full-page loaders on product pages.
 */
define(['jquery', 'domReady!'], function ($) {
    'use strict';

    function clearBodyLoaders() {
        $('body > .loading-mask').remove();
        $('body').trigger('processStop');
        $(document).trigger('processStop');
    }

    function clearGalleryLoader() {
        var $gallery = $('[data-gallery-role=gallery-placeholder]');

        if (!$gallery.length) {
            return;
        }

        if ($gallery.find('.fotorama__wrap').length || $gallery.data('gallery')) {
            $gallery.removeClass('_block-content-loading');
            $gallery.find('[data-role="loader"], .loading-mask').remove();
        }
    }

    $(document).on('gallery:loaded', clearGalleryLoader);
    $('[data-gallery-role=gallery-placeholder]').on('gallery:loaded', clearGalleryLoader);

    setTimeout(clearGalleryLoader, 500);
    setTimeout(clearGalleryLoader, 2000);
    setTimeout(clearBodyLoaders, 4000);
    setTimeout(clearBodyLoaders, 8000);

    return clearGalleryLoader;
});
