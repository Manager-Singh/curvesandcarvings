define([
    'jquery',
    'owl.carousel',
    'simple-lightbox'
], function ($, owlCarousel, SimpleLightbox) {
    'use strict';

    return {
        init: function () {
            if (typeof $.fn.owlCarousel !== 'function') {
                return;
            }

            $('.homcaro, .testimonial').owlCarousel({
                loop: true,
                autoplay: true,
                autoplaySpeed: 3000,
                margin: 10,
                responsiveClass: true,
                responsive: {
                    0: { items: 1, nav: true },
                    600: { items: 2, nav: false },
                    1000: { items: 3, nav: true, loop: false, margin: 20 }
                }
            });

            $('.hmblog').owlCarousel({
                loop: true,
                autoplay: true,
                autoplaySpeed: 3000,
                margin: 10,
                responsiveClass: true,
                responsive: {
                    0: { items: 1, nav: true },
                    600: { items: 2, nav: false },
                    1000: { items: 2, nav: true, loop: false, margin: 20 }
                }
            });

            $('.hbanner-slider').owlCarousel({
                loop: true,
                autoplay: true,
                autoplaySpeed: 5000,
                autoplayHoverPause: true,
                dots: false,
                nav: true,
                responsiveClass: true,
                responsive: {
                    0: { items: 1 },
                    1000: { items: 1 }
                }
            });

            if (typeof SimpleLightbox !== 'undefined') {
                new SimpleLightbox('.gallery a', {});
            }
        }
    };
});
