var config = {
    paths: {
        'js/owl.carousel': 'js/owl.carousel',
        'js/simple-lightbox': 'js/simple-lightbox',
        'js/jquery.popupoverlay': 'js/jquery.popupoverlay',
        'js/readmore': 'js/readmore',
        'js/jquery-mTab-min': 'js/jquery-mTab-min',
        'js/navigation-menu': 'js/navigation-menu',
        'js/grt-responsive-menu': 'js/grt-responsive-menu'
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
