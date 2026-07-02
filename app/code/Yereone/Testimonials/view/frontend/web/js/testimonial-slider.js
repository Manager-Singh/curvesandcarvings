define(['jquery', 'Yereone_Testimonials/js/slick.min'], function ($) {
    return function(data, element)
    {
    	$(element).slick(data.config);
    };
});
