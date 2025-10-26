( function( $ ) { 
    var pxl_slider_handler = function( $scope, $ ) {
        var breakpoints = elementorFrontend.config.breakpoints,
            carousel = $scope.find(".pxl-swiper-container");
        if(carousel.length == 0){
            return false;
        }

        var data = carousel.data(), 
            settings = data.settings,
            custom_dots = data.customdot,
            carousel_settings = {
                direction: settings['slide_direction'],
                effect: settings['slide_mode'],
                wrapperClass : 'pxl-swiper-wrapper',
                slideClass: 'pxl-swiper-slide',
                slidesPerView: 1,
                slidesPerGroup: 1,
                slidesPerColumn: 1,
                spaceBetween: 0,
                navigation: {
                  nextEl: $scope.find(".pxl-slider-arrow-next"),
                  prevEl: $scope.find(".pxl-slider-arrow-prev"),
                },
                pagination : {
                    type: settings['dots_style'],
                    el: $scope.find('.pxl-slider-dots'),
                    clickable : true,
                    modifierClass: 'pxl-slider-pagination-',
                    bulletClass : 'pxl-slider-pagination-bullet',
                    formatFractionCurrent: function (number) {
                        return ('0' + number).slice(-2);
                    },
                    formatFractionTotal: function (number) {
                        return ('0' + number).slice(-2);
                    },
                    renderFraction: function (currentClass, totalClass) {
                        return '<span class="' + currentClass + '"></span>' +
                               '<span class="divider"></span>' +
                               '<span class="' + totalClass + '"></span>';
                    },
                    renderCustom: function (swiper, element, current, total) {
                        return current + ' of ' + total;
                    }
                },
                speed: settings['speed'],
                watchSlidesProgress: true,
                watchSlidesVisibility: true,
                autoplay: settings['autoplay'],
                on: {
                    init : function (swiper){ 
                        elementorFrontend.waypoint($scope.find('.pxl-animate'), function () {
                            var $this = $(this),
                                data = $this.data('settings');
                            if(typeof data['animation'] != 'undefined'){
                                setTimeout(function () {
                                    $this.removeClass('pxl-invisible').addClass('animated ' + data['animation']);
                                }, data['animation_delay']);
                            }
                        });

                        pxl_ken_burns(this);  
                    },
                    slideChangeTransitionStart : function (swiper){
                        var activeIndex = this.activeIndex;
                        var current = this.slides.eq(activeIndex);
                         
                        $scope.find('.swiper-slide .pxl-animate').each(function(){
                            var data = $(this).data('settings');
                            $(this).removeClass('animated '+data['animation']).addClass('pxl-invisible');
                        });
                        current.find('.pxl-animate').each(function(){
                            var  $item = $(this), 
                                data = $item.data('settings');
                            setTimeout(function () {
                                $item.removeClass('pxl-invisible').addClass('animated ' + data['animation']);
                            }, data['animation_delay']);
                        });

                        $(this.slides).each(function(index){
                            if(index == activeIndex)
                                $(this).find('.wow').removeClass('pxl-invisible').addClass('animated');
                            else
                                $(this).find('.wow').removeClass('animated').addClass('pxl-invisible');
                        });
                        pxl_ken_burns(this);  
                    },
                    slideChange: function (swiper) {
                        
                        var activeIndex = this.activeIndex; 
                        var current = this.slides.eq(activeIndex);
                        $scope.find('.swiper-slide .pxl-animate').each(function(){
                            var data = $(this).data('settings');
                            $(this).removeClass('animated '+data['animation']).addClass('pxl-invisible');
                        });
                        current.find('.pxl-animate').each(function(){
                            var  $item = $(this), 
                                data = $item.data('settings');
                            setTimeout(function () {
                                $item.removeClass('pxl-invisible').addClass('animated ' + data['animation']);
                            }, data['animation_delay']);
                        });

                        $(this.slides).each(function(index){
                            if(index == activeIndex)
                                $(this).find('.wow').removeClass('pxl-invisible').addClass('animated');
                            else
                                $(this).find('.wow').removeClass('animated').addClass('pxl-invisible');
                        });
                        pxl_ken_burns(this);  
                    },
                },
                autoHeight: true
            };

            if(settings['loop'] === 'true'){
                carousel_settings['loop'] = true;
            }
            // auto play
            if(settings['autoplay'] === 'true'){
                carousel_settings['autoplay'] = {
                    delay : settings['delay'],
                    disableOnInteraction : settings['pause_on_interaction']
                };
            } else {
                carousel_settings['autoplay'] = false;
            }
            // Effect
            if(settings['slide_mode'] === 'cube'){
                carousel_settings['cubeEffect'] = {
                  shadow: false,
                  slideShadows: false,
                  shadowOffset: 0,
                  shadowScale: 0, //0.94,
                };
            }
            if(settings['slide_mode'] === 'coverflow'){
                carousel_settings['centeredSlides'] = true;
                carousel_settings['coverflowEffect'] = {
                  rotate: 50,
                  stretch: 0,
                  depth: 100,
                  modifier: 1,
                  slideShadows: false,
                };
            }
             
        
        carousel.each(function(index, element) {
            var swiper = new Swiper(carousel, carousel_settings);
            if(settings['autoplay'] === 'true' && settings['pause_on_hover'] === 'true'){
                $(this).on({
                    mouseenter: function mouseenter() {
                        this.swiper.autoplay.stop();
                    },
                    mouseleave: function mouseleave() {
                        this.swiper.autoplay.start();
                    }
                });
            }
        });   

        function pxl_ken_burns(item) {
		    //if(settings['ken_burns'] == 'true'){ 
	    	var activeIndex = item.activeIndex; 
            var current = item.slides.eq(activeIndex);
            if(current.find('.pxl-ken-burns').length > 0){
	            $('.pxl-slide-bg').removeClass('pxl-ken-burns--active');
	            current.find('.pxl-slide-bg').addClass('pxl-ken-burns--active');
	        }
		    //}
	  	} 
    };
    // Make sure you run this code under Elementor.
    $( window ).on( 'elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction( 'frontend/element_ready/pxl_slider.default', pxl_slider_handler );
    } );
} )( jQuery );