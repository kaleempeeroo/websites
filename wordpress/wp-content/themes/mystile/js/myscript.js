alert("hey");
jQuery(document).ready(function($) {
     $('.slider').bxSlider();
	 var autoplaySlider = $('#lightSlider').lightSlider({
        auto:true,
        loop:true,
        pauseOnHover: true,
        onBeforeSlide: function (el) {
            $('#current').text(el.getCurrentSlideCount());
        } 
    });
    $('#total').text(autoplaySlider.getTotalSlideCount());
	 alert("heyo");
    });
