var config = {
    paths: {
        'owl.carousel': 'Curvesandcarvings_Homepage/js/owl.carousel.min',
        'simple-lightbox': 'Curvesandcarvings_Homepage/js/simple-lightbox.min'
    },
    shim: {
        'owl.carousel': {
            deps: ['jquery']
        },
        'simple-lightbox': {
            exports: 'SimpleLightbox'
        }
    }
};
