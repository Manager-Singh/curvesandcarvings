/*!
 * GRT Responsive Menu - jQuery Plugin
 * Version: 1.0
 * Author: GRT107
 *
 * Copyright (c) 2018 GRT107
 * Released under the MIT license
*/

// Create a function for mobile version
(function( jQuery ){
	jQuery.fn.grtmobile = function () {
		if (jQuery(window).width() < 768) {
			jQuery('.grt-mobile-button').on('click', function(){
				jQuery(this).toggleClass("grt-mobile-button-open");
				jQuery("ul.grt-menu").toggleClass("open-grt-menu ");
				jQuery("html, body").toggleClass("body-overflow");
			});
			jQuery('li.grt-dropdown').on('click', function(e){
				jQuery(this).toggleClass("active-dropdown");
			});
		}
	}
})( jQuery );

// Initialize and check for mobile
jQuery.fn.grtmobile();

// On resize window check for mobile

var resizeTimeout;
jQuery(window).resize(function(){
  if(!!resizeTimeout){ clearTimeout(resizeTimeout); }
  resizeTimeout = setTimeout(function(){
    jQuery.fn.grtmobile();
  },200);
});

// Add shadow on scroll after 60px
jQuery(window).scroll(function(e){
   if (jQuery(this).scrollTop() > 60){
       jQuery('header').addClass('scrolled');
   } else {
       jQuery('header').removeClass('scrolled');
   }
});

// Prevent a href clicks on dropdown category
jQuery('li.grt-dropdown > a').on('click', function(e){
	e.preventDefault();
});