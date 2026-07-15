var config = {
    map: {
        '*': {
            ibnabmodernizr: 'Ibnab_MegaMenu/js/modernizr-2.8.3'
        }
    },
    paths: {
        ibnabmodernizr: 'Ibnab_MegaMenu/js/modernizr-2.8.3',
        'js/owl.carousel': 'js/owl.carousel',
        'js/simple-lightbox': 'js/simple-lightbox',
        'js/jquery.popupoverlay': 'js/jquery.popupoverlay',
        'js/readmore': 'js/readmore',
        'js/jquery-mTab-min': 'js/jquery-mTab-min',
        'js/navigation-menu': 'js/navigation-menu',
        'js/grt-responsive-menu': 'js/grt-responsive-menu'
    },
    config: {
        mixins: {
            // Disabled: custom variation cards replace Magento swatch UI.
            // Re-enabling caused re-render loops with our PDP markup.
        }
    },
    shim: {
        'js/owl.carousel': {
            deps: ['jquery']
        },
        'js/simple-lightbox': {
            deps: ['jquery'],
            exports: 'SimpleLightbox'
        },
        'js/jquery.popupoverlay': {
            deps: ['jquery']
        },
        'js/readmore': {
            deps: ['jquery']
        },
        'js/jquery-mTab-min': {
            deps: ['jquery']
        },
        'js/navigation-menu': {
            deps: ['jquery']
        },
        'js/grt-responsive-menu': {
            deps: ['jquery']
        }
    }
};
