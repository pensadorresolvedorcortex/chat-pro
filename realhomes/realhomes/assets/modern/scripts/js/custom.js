( function ( $ ) {
    "use strict";

    /**
     *  Sticky footer related code.
     */
    function footerStick() {
        $( '.rh_wrap_stick_footer' ).css( 'padding-bottom', $( '.rh_sticky_wrapper_footer' ).outerHeight() + 'px' )
    }

    $( document ).ready( function () {

        // Sticky Headers
        reahomesStickyHeader( '.rh_mod_sticky_header,.rhea_long_screen_header_temp', 1139, '>' );
        reahomesStickyHeader( '.rh-mobile-sticky-header', 1139, '<' );

        // footerStick - Run on document ready.
        footerStick();
        $( window ).on( 'load resize', function () {
            footerStick();
        } );

        /*-----------------------------------------------------------------------------------*/
        /*  Navigation menu.
        /*-----------------------------------------------------------------------------------*/
        $( '.rh_menu ul.rh_menu__main > li' ).on( {
            mouseenter : function () {
                var menu_link = $( this ).children( 'a' );
                $( menu_link ).addClass( 'rh_menu--hover' );
            },
            mouseleave : function () {
                var menu_link = $( this ).children( 'a' );
                $( menu_link ).removeClass( 'rh_menu--hover' );
            }
        } );

        // Responsive Menu.
        // First level
        $( '.rh_menu__hamburger' ).on( 'click', function () {
            $( 'ul.rh_menu__responsive' ).toggleClass( 'rh_menu__responsive_show' );
        } );

        var sub_menu_parent = $( '.rh_menu__responsive ul.sub-menu' ).parent();
        sub_menu_parent.prepend( '<i class="fas fa-caret-down rh_menu__indicator"></i>' );

        // Second Level
        $( 'ul.rh_menu__responsive > li .rh_menu__indicator' ).on( 'click', function ( e ) {
            e.preventDefault();
            $( this ).parent().children( 'ul.sub-menu' ).slideToggle();
            $( this ).toggleClass( 'rh_menu__indicator_up' );
        } );

        function moveUserMenuRes() {
            var largeUserMenu        = $( '.rh_user_menu_wrapper_large' ).find( '.rh_menu__user_profile' );
            var smallUserMenu        = $( '.rh_user_menu_wrapper_responsive' ).find( '.rh_menu__user_profile' );
            var largeUserMenuWrapper = $( '.rh_user_menu_wrapper_large' );
            var smallUserMenuWrapper = $( '.rh_user_menu_wrapper_responsive' );
            if ( $( window ).width() < 1140 ) {
                largeUserMenu.appendTo( smallUserMenuWrapper );
            } else {
                smallUserMenu.appendTo( largeUserMenuWrapper );
            }
        }

        moveUserMenuRes();
        $( window ).on( 'resize', function () {
            moveUserMenuRes();
        } );

        // header variation standard
        $( '.rh_menu__hamburger_standard' ).on( 'click', function () {
            $( this ).siblings( '.menu-container-standard-responsive' ).toggleClass( 'rh_menu__responsive_show' );
            // $('ul.rh_menu__responsive_plain').toggleClass('rh_menu__responsive_show');
        } );

        var sub_menu_parent_plain = $( '.rh_menu__responsive_plain ul.sub-menu' ).parent();
        sub_menu_parent_plain.prepend( '<i class="fas fa-caret-down rh_menu__indicator"></i>' );

        $( 'ul.rh_menu__responsive_plain > li .rh_menu__indicator' ).on( 'click', function ( e ) {
            e.preventDefault();
            $( this ).parent().children( 'ul.sub-menu' ).slideToggle();
            $( this ).toggleClass( 'rh_menu__indicator_up' );
        } );

        /*-----------------------------------------------------------------------------------*/
        /*	Scroll to Top
        /*-----------------------------------------------------------------------------------*/
        $( function () {
            var scroll_anchor = $( '#scroll-top' );

            $( window ).on( 'scroll', function () {
                // if ($(window).width() > 980) {
                if ( $( this ).scrollTop() > 250 ) {
                    scroll_anchor.addClass( 'show' );
                    return;
                }
                // }
                scroll_anchor.removeClass( 'show' );
            } );

            scroll_anchor.on( 'click', function ( event ) {
                event.preventDefault();
                $( 'html, body' ).animate( { scrollTop : 0 }, 'slow' );
            } );
        } );

        /*-----------------------------------------------------------------------------------*/
        /*  Hover Fix For Listings
        /*-----------------------------------------------------------------------------------*/

        var mobileHover = function ( thumbFigure ) {
            $( thumbFigure ).each( function () {
                $( this ).on( 'touchstart', function () {
                    return true;
                } );
                $( this ).parents( 'body' ).on( 'touchstart', function () {
                    return true;
                } );
            } );

        };

        mobileHover( '.rh_prop_card__thumbnail' );
        mobileHover( '.rh_list_card__thumbnail' );

        // $('.rh_prop_card__thumbnail').attr("onclick","return true");

        /*-----------------------------------------------------------------------------------*/
        /*	Login Menu Open/Close
        /*-----------------------------------------------------------------------------------*/
        function logInMenu() {

            $( ".rh_menu__user_profile" ).on( ' mouseover', function () {
                if ( $( window ).width() > 1023 || $( this ).find( '.add-favorites-without-login' ).length ) {
                    if ( ! $( this ).hasClass( 'open-login' ) ) {
                        $( this ).addClass( 'open-login' );
                    }
                }
            } );

            $( ".rh_menu__user_profile" ).on( 'mouseout', function () {
                if ( $( window ).width() > 1023 || $( this ).find( '.add-favorites-without-login' ).length ) {
                    if ( $( this ).hasClass( 'open-login' ) ) {
                        $( this ).removeClass( 'open-login' );
                    }
                }
            } );

            $( "body" ).on( 'click', '.rh_menu__user_profile', function () {
                if ( $( window ).width() < 1024 ) {
                    $( this ).toggleClass( 'open-login' );

                    $( '.rh_modal' ).on( 'click', function ( e ) {
                        e.stopPropagation();
                    } );
                }
            } );
        }

        logInMenu();

        /*-----------------------------------------------------------------------------------*/
        /*	Auto Focus on login
        /*-----------------------------------------------------------------------------------*/
        $( function () {
            $( '.rh_menu__user_profile' ).on( 'mouseover', function () {
                if ( $( this ).find( '#username' ).hasClass( 'focus-class' ) ) {
                    var userFocus   = $( '.focus-class' );
                    var fieldVal    = userFocus.val();
                    var fieldLength = fieldVal.length;
                    if ( fieldLength === 0 ) {
                        $( userFocus ).focus();
                    }
                }
            } );
        } );

        /*-----------------------------------------------------------------------------------*/
        /*  Flex Slider
        /*-----------------------------------------------------------------------------------*/
        if ( jQuery().flexslider ) {

            // Flex Slider for Homepage.
            $( '#rh_slider__home .flexslider' ).flexslider( {
                animation          : "fade",
                slideshowSpeed     : 7000,
                animationSpeed     : 1500,
                slideshow          : true,
                directionNav       : true,
                controlNav         : false,
                keyboardNav        : true,
                customDirectionNav : $( ".rh_flexslider__nav_main a" ),
                start              : function ( slider ) {
                    slider.removeClass( 'loading' );
                    slider.removeClass( 'rh_home_load_height' );
                }
            } );

            // Flex Slider for Property Single Videos.
            const singlePropertyVideosSlider      = $( '.rh_wrapper_property_videos_slider' ),
                  singlePropertyVideosSliderItems = singlePropertyVideosSlider.find( '.slides li' );
            if ( ( singlePropertyVideosSliderItems.length > 1 ) ) {
                singlePropertyVideosSlider.flexslider( {
                    animation    : "slide",
                    slideshow    : false,
                    directionNav : true,
                    controlNav   : false,
                    start        : function ( slider ) {
                        slider.resize();
                        $( '.flexslider .clone' ).children().removeAttr( "data-fancybox" );
                    },
                } );
            } else {
                singlePropertyVideosSliderItems.css( 'display', 'block' );
            }

            // Featured Properties slider for Homepage.
            $( '#rh_section__featured_slider .flexslider' ).flexslider( {
                animation          : "fade",
                slideshowSpeed     : 7000,
                animationSpeed     : 1500,
                slideshow          : false,
                directionNav       : true,
                controlNav         : false,
                keyboardNav        : true,
                customDirectionNav : $( ".rh_flexslider__nav a" ),
                start              : function ( slider ) {
                    slider.removeClass( 'loading' );
                    $( '.flexslider .clone' ).children().removeAttr( "data-fancybox" );
                }
            } );


            // Flex Slider for Detail Page
            var $sliderItemCurrent = $( ".slider-item-current" );
            $( '#property-detail-flexslider .flexslider' ).flexslider( {
                animation    : "fade",
                slideshow    : false,
                directionNav : true,
                controlNav   : false,
                start        : function ( slider ) {
                    slider.resize();
                    slider.removeClass( 'rh_property_load_height' );
                    $( '.flexslider .clone' ).children().removeAttr( "data-fancybox" );
                },
                after        : function ( slider ) {
                    $sliderItemCurrent.text( slider.currentSlide + 1 );

                },

            } );

            /* Property detail page slider variation two */
            $( '#property-detail-slider-carousel-nav' ).flexslider( {
                animation     : "slide",
                controlNav    : false,
                animationLoop : false,
                directionNav  : true,
                prevText      : "",
                nextText      : "",
                slideshow     : false,
                itemWidth     : 130,
                itemMargin    : 5,
                minItems      : 8,
                maxItems      : 8,
                asNavFor      : '#property-detail-slider-two'
            } );
            $( '#property-detail-slider-two' ).flexslider( {
                animation     : "fade",
                controlNav    : false,
                animationLoop : false,
                slideshow     : false,
                directionNav  : true,
                // smoothHeight: true,
                prevText : "",
                nextText : "",
                sync     : "#property-detail-slider-carousel-nav",
                start    : function ( slider ) {
                    slider.removeClass( 'rh_property_load_height' );
                    $( '.flexslider .clone' ).children().removeAttr( "data-fancybox" );
                    $( '.thumb-on-bottom .property-head-wrapper' ).css( 'bottom', $( '#property-detail-slider-carousel-nav' ).outerHeight() );
                },

            } );

            $( window ).on( 'resize', function () {
                $( '.thumb-on-bottom .property-head-wrapper' ).css( 'bottom', $( '#property-detail-slider-carousel-nav' ).outerHeight() );
            } );


            // Flex Slider for Child Properties on property detail page.
            $( '#rh_property__child_slider .flexslider' ).flexslider( {
                animation    : "slide",
                slideshow    : false,
                directionNav : true,
                controlNav   : false,
                start        : function ( slider ) {
                    slider.resize();
                }
            } );

            // Remove Flex Slider Navigation for Smaller Screens Like IPhone Portrait
            $( '.slider-wrapper, .listing-slider' ).on( {
                mouseenter : function () {
                    var mobile = $( 'body' ).hasClass( 'probably-mobile' );
                    if ( ! mobile ) {
                        $( '.flex-direction-nav' ).stop( true, true ).fadeIn( 'slow' );
                    }
                },
                mouseleave : function () {
                    $( '.flex-direction-nav' ).stop( true, true ).fadeOut( 'slow' );
                }
            } );

            // Flex Slider Gallery Post
            $( '.listing-slider' ).each( function () {
                $( this ).flexslider( {
                    animation  : "slide",
                    slideshow  : false,
                    controlNav : false,
                    // directionNav: false,
                    // customDirectionNav: $(".rh_flexslider__nav_main_gallery .nav-mod"),
                    customDirectionNav : $( this ).next( '.rh_flexslider__nav_main_gallery' ).find( '.nav-mod' ),
                    start              : function ( slider ) {
                        $( '.listing-slider' ).find( '.clone' ).children().removeAttr( "data-fancybox" );
                    },
                } );
            } );

        }


        /*-----------------------------------------------------------------------------------*/
        /*  Slick Carousel
        /*-----------------------------------------------------------------------------------*/
        if ( jQuery().slick ) {

            $( '.inspiry_property_carousel_style' ).on( 'init', function ( event, slick, direction ) {
                $( event.currentTarget ).removeClass( 'rh_property_car_height' );
            } );
            $( '.inspiry_property_carousel_style:not(.images_2)' ).slick( {
                infinite       : false,
                dots           : false,
                slidesToScroll : 1,
                vertical       : false,
                mobileFirst    : true,
                responsive     : [
                    {
                        breakpoint : 1024,
                        settings   : {
                            slidesToShow: 3,
                        }
                    },
                    {
                        breakpoint : 767,
                        settings   : {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint : 0,
                        settings   : {
                            slidesToShow : 2,
                            vertical: true,
                        }
                    }
                ]
            } );

            $( '.inspiry_property_carousel_style.images_2' ).slick( {
                infinite       : false,
                dots           : false,
                slidesToScroll : 1,
                vertical       : false,
                slidesToShow   : 2,
                responsive     : [
                    {
                        breakpoint : 767,
                        settings   : {
                            vertical: true,
                        }
                    }
                ]
            } );


            $( '.property-detail-slider-three' ).slick( {
                infinite       : false,
                slidesToShow   : 1,
                slidesToScroll : 1,
                arrows         : true,
                dots           : false,
                fade           : true,
                adaptiveHeight : true,
                asNavFor       : '.property-detail-carousel-three'
            } );
            $( '.property-detail-carousel-three' ).slick( {
                infinite       : false,
                slidesToScroll : 3,
                asNavFor       : '.property-detail-slider-three',
                dots           : false,
                arrows         : true,
                centerMode     : false,
                focusOnSelect  : true,
                mobileFirst    : true,
                nextArrow      : '<i class="fas fa-angle-right"></i>',
                prevArrow      : '<i class="fas fa-angle-left"></i>',
                responsive     : [
                    {
                        breakpoint : 1400,
                        settings   : {
                            slidesToShow: 9,
                        }
                    },
                    {
                        breakpoint : 1200,
                        settings   : {
                            slidesToShow: 8,
                        }
                    },
                    {
                        breakpoint : 1024,
                        settings   : {
                            slidesToShow: 7,
                        }
                    },
                    {
                        breakpoint : 767,
                        settings   : {
                            slidesToShow: 6,
                        }
                    }
                ]
            } );


        }

        var sfoiB1 = $( '.rh_open_sfoi_advance' ).outerWidth();
        var sfoiB2 = $( '.rh_sfoi_search_btn' ).outerWidth();

        if ( sfoiB1 > sfoiB2 ) {
            $( '.rh_sfoi_search_btn' ).css( 'min-width', sfoiB1 + "px" );
            $( '.rh_mod_sfoi_wrapper_inner .rh_prop_search__option:nth-of-type(1)' ).css( 'min-width', sfoiB1 + "px" );
        } else {
            $( '.rh_open_sfoi_advance' ).css( 'min-width', sfoiB2 + "px" );
            $( '.rh_mod_sfoi_wrapper_inner .rh_prop_search__option:nth-of-type(1)' ).css( 'min-width', sfoiB2 + "px" );

        }

        $( '.rh_mod_sfoi_advanced_expander' ).on( 'click', function () {
            $( this ).toggleClass( 'rh_sfoi_is_open' );
            const topFields = $(".rh_top_sfoi_fields");

            if ( $( this ).hasClass( 'rh_sfoi_is_open' ) ) {
                topFields.addClass("sfoi-fields-expanded");
                $( '.rh_mod_sfoi_advance_fields_wrapper' ).stop().slideDown( 500 );
            } else {
                $( '.rh_mod_sfoi_advance_fields_wrapper' ).stop().slideUp( 500, function() {
                    topFields.removeClass("sfoi-fields-expanded");
                } );
            }
        } );

        $( '.rh_sfoi_features .more-option-trigger a' ).on( 'click', function ( e ) {
            e.preventDefault();
            $( this ).toggleClass( 'rh_sfoi_feature_open' );
            if ( $( this ).hasClass( 'rh_sfoi_feature_open' ) ) {
                $( '.more-options-wrapper' ).stop().slideDown( 500 );
            } else {
                $( '.more-options-wrapper' ).stop().slideUp( 500 );
            }
        } );


        function moveFormToHeader() {
            var screenWidth = $( window ).width();
            if ( screenWidth <= 1139 ) {
                $( '.inspiry_mod_header_variation_three .rh_prop_search_form_header' ).detach().prependTo( ".rh_prop_search" );
                $( '.inspiry_mod_header_variation_three .rh_prop_search' ).show();
            } else if ( screenWidth >= 1139 ) {
                $( '.inspiry_mod_header_variation_three .rh_prop_search_form_header' ).detach().prependTo( ".rh_prop_search_in_header" );
                $( '.inspiry_mod_header_variation_three .rh_prop_search' ).hide();
            }
        }

        moveFormToHeader();

        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test( navigator.userAgent ) ? true : false;

        if ( ! isMobile ) {
            $( window ).on( 'resize', moveFormToHeader );
        }


        function slideElementDisplay() {
            var getDataTopBar        = $( '#rh_fields_search__wrapper' ).data( 'top-bar' );
            var slideElementsDisplay = $( '.rh_prop_search__form .rh_prop_search__fields .rh_prop_search__option' );

            var setDataTopBar = 0;
            if ( window.matchMedia( '(max-width: 767px)' ).matches ) {
                if ( getDataTopBar == 3 ) {
                    setDataTopBar = 4;
                } else {
                    setDataTopBar = getDataTopBar;
                }
            } else if ( window.matchMedia( '(min-width: 768px)' ).matches ) {
                setDataTopBar = getDataTopBar;

            }

            var slideElements = $( '.rh_prop_search__form .rh_prop_search__fields .rh_prop_search__option:not(.hide-fields):nth-of-type(n+' + ( setDataTopBar + 1 ) + ')' );

            if ( ! slideElements.hasClass( 'show' ) ) {
                // slideElements.css('max-width','25%');
                slideElements.addClass( 'show' ).slideDown( 100 ).animate(
                    { opacity : 1 },
                    {queue: false, duration: 300}
                );

            } else {
                slideElements.removeClass( 'show' ).slideUp( 100 ).animate(
                    { opacity : 0 },
                    {queue: false, duration: 100}
                );
            }
        }

        $( '.rh_prop_search__buttons .rh_prop_search__advance_btn' ).on( 'click', function ( e ) {

            e.preventDefault();

            // Toggle search icon.
            $( this ).find( '#rh_icon__search' ).toggle( '400' );

            // Open advance search fields.
            $( '#rh_prop_search__dropdown' ).toggleClass( 'rh_prop_search__ddActive' );


            var thisParent = $( this ).parents( '.rh_prop_search_init' );

            if ( ! ( thisParent ).hasClass( 'rh_open_form' ) ) {
                thisParent.addClass( 'rh_open_form' );
                $( '.rh_form_fat_collapsed_fields_wrapper' ).stop().slideDown( 400 );
            } else {
                thisParent.removeClass( 'rh_open_form' );
                // thisParent.find('.more-options-wrapper-mode').slideUp(200);
                // thisParent.find('.open_more_features').removeClass('featured-open');
                $( '.rh_form_fat_collapsed_fields_wrapper' ).stop().slideUp( 400 )
            }

            // slideElementDisplay();

        } );

        $( '.rh_prop_search__buttons_smart .rh_prop_search__advance_btn' ).on( 'click', function ( e ) {
            e.preventDefault();

            $( this ).find( '#rh_icon__search' ).toggle( '400' );

            $( this ).toggleClass( 'rh_smart_form_open' );

            if ( $( this ).hasClass( 'rh_smart_form_open' ) ) {
                $( '.rh_form_smart_collapsed_fields_wrapper' ).stop().slideDown( 400 );
            } else (
                    $( '.rh_form_smart_collapsed_fields_wrapper' ).stop().slideUp( 400 )
                )

        } );

        function topBarFieldsHeight() {
            if ( $( '.advance-search-form' ).hasClass( 'rh_prop_search__form' ) ) {
                var getDataTopBar    = $( '#rh_fields_search__wrapper' ).data( 'top-bar' );
                var topElementsReset = $( '.rh_prop_search__form .rh_prop_search__fields .rh_prop_search__option' );

                var showDataTopBar = 0;
                if ( window.matchMedia( '(max-width: 767px)' ).matches ) {
                    if ( getDataTopBar == '3' ) {
                        showDataTopBar = 4;
                        topElementsReset.removeClass( 'default-show' );
                    } else {
                        showDataTopBar = getDataTopBar;
                    }
                } else if ( window.matchMedia( '(min-width: 768px)' ).matches ) {
                    showDataTopBar = getDataTopBar;
                    topElementsReset.removeClass( 'default-show' );
                }
                var topElements = $( '.rh_prop_search__form .rh_prop_search__fields .rh_prop_search__option:not(.hide-fields):nth-of-type(-n+' + showDataTopBar + ')' );
                topElements.addClass( 'default-show' );
                if ( window.matchMedia( '(min-width: 768px)' ).matches ) {
                    topElements.css({'max-width': (100 / showDataTopBar) + '%', 'width': 100 + '%'});
                }
                if ( window.matchMedia( '(max-width: 767px)' ).matches ) {
                    if ( getDataTopBar == 1 ) {
                        topElements.css({'max-width': 'none', 'width': '100%'});
                    } else {
                        topElements.css({'max-width': 'none', 'width': (100 / 2) + '%'});
                    }
                }
            }
        }

        $( '.rh_prop_search__selectwrap' ).on( 'click', function ( e ) {
            e.preventDefault();
            var search_select = $( this ).find( '.ajax-location-field' );
            // var search_select = $(this).find('.rh_select2, .ajax-location-field');

            if (e.target.classList[0] === 'select2-selection' || e.target.classList[0] === 'select2-selection__rendered') return;


            // search_select.select2("open");
        } );

        $( '.inspiry_select_picker_trigger,.ajax-location-field' ).each( function () {
            // $('.rh_select2, .ajax-location-field').each(function () {

            var thisParent = $( this ).parents( '.rh_prop_search__select' );

            var thisCurrentValue = $( this ).children( "option:selected" ).val();


            if ( thisCurrentValue !== 'any' && typeof thisCurrentValue !== 'undefined' ) {
                thisParent.addClass( 'rh_sfoi_field_selected' );
            } else {
                thisParent.removeClass( 'rh_sfoi_field_selected' );
            }

            $( this ).on( 'change', function () {
                var thisAnyValue = this.value;


                if ( thisAnyValue !== 'any' && thisAnyValue.length !== 0 ) {

                    thisParent.addClass( 'rh_sfoi_field_selected' );
                } else {
                    thisParent.removeClass( 'rh_sfoi_field_selected' );
                }
            } );
        } );

        $( '.rh_mod_text_field' ).each( function () {
            var thisParent    = $( this ).not( '.rvr_check_in,.rvr_check_out' );
            var thisTextField = $( this ).find( 'input' );

            thisTextField.on( "focus", function () {
                thisParent.addClass( 'rh_mod_text_field_focused' );
            } );

            thisTextField.on( "blur", function () {

                setTimeout( function () {
                    if ( ! $( thisTextField ).val() ) {
                        thisParent.removeClass( 'rh_mod_text_field_focused' );
                    } else {
                        thisParent.addClass( 'rh_mod_text_field_focused' );
                    }
                }, 100 );

            } );
        } );


        //Open Search Form More Features fields
        $( '.open_more_features' ).on( 'click', function ( e ) {
            e.preventDefault();
            $( this ).toggleClass( 'featured-open' );
            $( '.more-options-wrapper-mode' ).slideToggle( 200 );

        } );

        $( '.advance-search-form ' ).each( function () {
            var getDataTopBar = $( this ).find( '.rh_prop_search_data' ).data( 'top-bar' );

            var advanceSearch = $( this ).find( '.rh_search_top_field_common .rh_prop_search__option' );

            var prePendTo = $( this ).find( '.rh_search_fields_prepend_to' );

            var j = 0;

            var i = 0;

            advanceSearch.each( function () {
                if ( i < getDataTopBar ) {
                    if ( $( this ).hasClass( 'hide-fields' ) ) {
                        j = 2;
                    }
                }
                i++;
            } );

            var advanceElements = getDataTopBar + j + 1;

            if ( advanceElements > 0 ) {
                var advanceFieldsSmart = $( this ).find( '.rh_search_top_field_common .rh_prop_search__option:nth-of-type(n+' + advanceElements + ')' );

                advanceFieldsSmart.detach().prependTo( prePendTo );

            }
        } );


        var rhSFOIModFields = function () {
            $( '.rh_sfoi_advance_search_form' ).each( function () {
                var getDataTopBar = $( this ).find( '.rh_top_sfoi_fields' ).data( 'sfoi-top' );

                var advanceSearch = $( this ).find( '.rh_top_sfoi_fields .rh_prop_search__option' );

                var prePendTo = $( this ).find( '.rh_mod_sfoi_advance_fields' );

                var j = 0;

                var i = 0;

                advanceSearch.each( function () {
                    if ( i < getDataTopBar ) {
                        if ( $( this ).hasClass( 'hide-fields' ) ) {
                            j = 2;
                        }
                    }
                    i++;
                } );


                var advanceElements = getDataTopBar + j + 1;


                if ( advanceElements > 0 ) {
                    var advanceFieldsSmart = $( this ).find( '.rh_top_sfoi_fields .rh_prop_search__option:nth-of-type(n+' + advanceElements + ')' );

                    advanceFieldsSmart.detach().prependTo( prePendTo );

                }
            } );
        };

        var removeFadedFields = function () {
            $( '.rh_mod_sfoi_content' ).removeClass( 'rh_sfoi_faded' );
        };

        var disableSfoiAdvance = function () {
            if ( ! $.trim( $( '.rh_mod_sfoi_advance_fields' ).html() ).length ) {
                $( '.rh_top_sfoi_fields' ).addClass( 'rh_sfoi_hide_advance_fields' );
            }
        };

        var SfoiCallbacks = $.Callbacks();

        SfoiCallbacks.add( rhSFOIModFields );
        SfoiCallbacks.fire( rhSFOIModFields );

        SfoiCallbacks.add( disableSfoiAdvance );
        SfoiCallbacks.fire( disableSfoiAdvance );

        SfoiCallbacks.add( removeFadedFields );
        SfoiCallbacks.fire( removeFadedFields );

        /*-----------------------------------------------------------------------------------*/
        /* Properties Sorting
        /*-----------------------------------------------------------------------------------*/
        function insertParam( key, value ) {
            key   = encodeURI( key );
            value = encodeURI( value );

            var kvp = document.location.search.substr( 1 ).split( '&' );

            var i = kvp.length;
            var x;
            while ( i-- ) {
                x = kvp[i].split( '=' );

                if ( x[0] == key ) {
                    x[1]   = value;
                    kvp[i] = x.join( '=' );
                    break;
                }
            }

            if ( i < 0 ) {
                kvp[kvp.length] = [key, value].join( '=' );
            }

            //this will reload the page, it's likely better to store this until finished
            document.location.search = kvp.join( '&' );
        }

        $( '#sort-properties' ).on( 'change', function () {
            var key   = 'sortby';
            var value = $( this ).val();
            insertParam( key, value );
        } );

        /*-----------------------------------------------------------------------------------*/
        /* Properties Listing Map Height Fix
        /*-----------------------------------------------------------------------------------*/
        var fixMapHeight = function () {
            var height      = ( $( '.rh_page__map_properties' ) ) ? $( '.rh_page__map_properties' ).outerHeight() : false;
            var screenWidth = $( document ).width();
            if ( height && ( 1024 < screenWidth ) ) {
                $( '.rh_page__listing_map' ).css( { 'height' : height } );
            }
        };

        /*----------------------------------------------------------------------------------*/
        /* Contact Form AJAX validation and submission
        /* Validation Plugin : http://bassistance.de/jquery-plugins/jquery-plugin-validation/
        /* Form Ajax Plugin : http://www.malsup.com/jquery/form/
        /*---------------------------------------------------------------------------------- */
        if ( jQuery().validate && jQuery().ajaxSubmit ) {

            var submitButton     = $( '#submit-button' ),
                ajaxLoader       = $( '#ajax-loader' ),
                messageContainer = $( '#message-container' ),
                errorContainer   = $( "#error-container" );

            var formOptions = {
                beforeSubmit : function () {
                    submitButton.attr( 'disabled', 'disabled' );
                    ajaxLoader.fadeIn( 'fast' );
                    messageContainer.fadeOut( 'fast' );
                    errorContainer.fadeOut( 'fast' );
                },
                success      : function ( ajax_response, statusText, xhr, $form ) {
                    var response = $.parseJSON( ajax_response );
                    ajaxLoader.fadeOut( 'fast' );
                    submitButton.removeAttr( 'disabled' );
                    if ( response.success ) {
                        $form.resetForm();
                        messageContainer.html( response.message ).fadeIn( 'fast' );

                        setTimeout( function () {
                            messageContainer.fadeOut( 'slow' )
                        }, 5000 );

                        // call reset function if it exists
                        if ( typeof inspiryResetReCAPTCHA == 'function' ) {
                            inspiryResetReCAPTCHA();
                        }

                        if ( typeof CFOSData !== 'undefined' ) {
                            setTimeout( function () {
                                window.location.replace( CFOSData.redirectPageUrl );
                            }, 1000 );
                        }

                        if ( typeof contactFromData !== 'undefined' ) {
                            setTimeout( function () {
                                window.location.replace( contactFromData.redirectPageUrl );
                            }, 1000 );
                        }
                    } else {
                        errorContainer.html( response.message ).fadeIn( 'fast' );
                    }
                }
            };

            // Contact page form
            $( '#contact-form .contact-form' ).validate( {
                errorLabelContainer : errorContainer,
                submitHandler       : function ( form ) {
                    $( form ).ajaxSubmit( formOptions );
                }
            } );

            // Contact Form Over Slider
            $( '.cfos_contact_form' ).validate( {
                errorLabelContainer : errorContainer,
                submitHandler       : function ( form ) {
                    $( form ).ajaxSubmit( formOptions );
                }
            } );

            // Agent single page form
            $( '#agent-single-form' ).validate( {
                errorLabelContainer : errorContainer,
                submitHandler       : function ( form ) {
                    $( form ).ajaxSubmit( formOptions );
                }
            } );
        }


        /**
         * Handling Schedule A Tour form functionality
         *
         * @since 4.0.0
         * */
        if ( jQuery().validate && jQuery().ajaxSubmit ) {

            let submitButton     = $( '#schedule-submit' ),
                ajaxLoader       = $( '#sat-loader' ),
                messageContainer = $( '#message-container' ),
                errorContainer   = $( "#error-container" );

            let satFormOptions = {
                beforeSubmit : function () {
                    submitButton.attr( 'disabled', 'disabled' );
                    ajaxLoader.fadeIn( 'fast' );
                    messageContainer.fadeOut( 'fast' );
                    errorContainer.fadeOut( 'fast' );
                },
                success      : function ( ajax_response, statusText, xhr, $form ) {
                    let response = $.parseJSON( ajax_response );
                    ajaxLoader.fadeOut( 'fast' );
                    submitButton.removeAttr( 'disabled' );
                    if ( response.success ) {
                        $form.resetForm();
                        messageContainer.html( response.message ).fadeIn( 'fast' );

                        setTimeout( function () {
                            messageContainer.fadeOut( 'slow' )
                        }, 5000 );

                        // call reset function if it exists
                        if ( typeof inspiryResetReCAPTCHA == 'function' ) {
                            inspiryResetReCAPTCHA();
                        }
                    } else {
                        errorContainer.html( response.message ).fadeIn( 'fast' );
                    }
                }
            };

            // Contact page form
            $( '#schedule-a-tour' ).validate( {
                errorLabelContainer : errorContainer,
                submitHandler       : function ( form ) {
                    $( form ).ajaxSubmit( satFormOptions );
                }
            } );
        }

        // adding date picker to schedule date field
        let singleProperty = document.querySelector( '.single-property' );
        if ( singleProperty ) {
            let satForm = document.getElementById( 'schedule-a-tour' );
            if ( satForm ) {
                $( "#sat-date" ).datepicker( {
                    minDate  : 0,
                    showAnim : 'slideDown',
                    beforeShow: function(input, inst) {
                        inst.dpDiv[0].classList.add('schedule-calendar-wrapper')
                    }
                } );
            }
        }


        /*-----------------------------------------------------------------*/
        /* Property Floor Plans
        /*-----------------------------------------------------------------*/
        $( '.floor-plans-accordions .floor-plan:first-child' ).addClass( 'current' )
        .children( '.floor-plan-content' ).css( 'display', 'block' ).end()
        .find( 'i.fas' ).removeClass( 'fa-plus' ).addClass( 'fa-minus' );

        $( '.floor-plan-title' ).on( 'click', function () {
            var parent_accordion = $( this ).closest( '.floor-plan' );
            if ( parent_accordion.hasClass( 'current' ) ) {
                $( this ).find( 'i.fas' ).removeClass( 'fa-minus' ).addClass( 'fa-plus' );
                parent_accordion.removeClass( 'current' ).children( '.floor-plan-content' ).stop().slideUp( 300 );
            } else {
                $( this ).find( 'i.fas' ).removeClass( 'fa-plus' ).addClass( 'fa-minus' );
                parent_accordion.addClass( 'current' ).children( '.floor-plan-content' ).stop().slideDown( 300 );
            }
            var siblings = parent_accordion.siblings( '.floor-plan' );
            siblings.find( 'i.fas' ).removeClass( 'fa-minus' ).addClass( 'fa-plus' );
            siblings.removeClass( 'current' ).children( '.floor-plan-content' ).stop().slideUp( 300 );
        } );


        /**
         * forEach implementation for Objects/NodeLists/Arrays, automatic type loops and context options
         *
         * @private
         * @author Todd Motto
         * @link https://github.com/toddmotto/foreach
         * @param {Array|Object|NodeList} collection - Collection of items to iterate, could be an Array, Object or NodeList
         * @callback requestCallback      callback   - Callback function for each iteration.
         * @param {Array|Object|NodeList} scope=null - Object/NodeList/Array that forEach is iterating over, to use as the this value when executing callback.
         * @returns {}
         */
        var forEach = function ( t, o, r ) {
            if ("[object Object]" === Object.prototype.toString.call(t)) for (var c in t) Object.prototype.hasOwnProperty.call(t, c) && o.call(r, t[c], c, t); else for (var e = 0, l = t.length; l > e; e++) o.call(r, t[e], e, t)
        };

        var hamburgers = document.querySelectorAll( ".hamburger" );
        if ( hamburgers.length > 0 ) {
            forEach( hamburgers, function ( hamburger ) {
                hamburger.addEventListener( "click", function () {
                    this.classList.toggle( "is-active" );
                }, false );
            } );
        }

        /* ---------------------------------------------------- */
        /*  Close Hamburger Menu on modern when click outside
        /* ---------------------------------------------------- */
        $( document ).on( 'mouseup', function ( e ) {
            var container = $( ".main-menu" );

            var innerContainer = container.find( 'ul.rh_menu__responsive' );

            // If the target of the click isn't the container

            if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
                if ( innerContainer.hasClass( 'rh_menu__responsive_show' ) ) {
                    innerContainer.removeClass( 'rh_menu__responsive_show' );
                }
                if ( $( hamburgers ).hasClass( 'is-active' ) ) {
                    $( hamburgers ).removeClass( "is-active" );
                }
            }
        } );

        /*-----------------------------------------------------------------------------------*/
        /* Hide the note
        /*-----------------------------------------------------------------------------------*/
        $( '.icon-remove' ).on( 'click', function ( e ) {
            e.preventDefault();
            $( this ).parent().fadeOut( 200 );
        } );

        /*-----------------------------------------------------------------------------------*/
        /* Homepage - CTA Buttons Width
         /*-----------------------------------------------------------------------------------*/
        if ( $( '.rh_cta__btns .rh_btn--blackBG' ).length > 0 || $( '.rh_cta__btns .rh_btn--whiteBG' ).length > 0 ) {
            var w1 = $( '.rh_cta__btns .rh_btn--blackBG' ).outerWidth();
            var w2 = $( '.rh_cta__btns .rh_btn--whiteBG' ).outerWidth();

            if ( w1 > w2 ) {
                $( '.rh_cta__btns .rh_btn--whiteBG' ).css( 'width', w1 + "px" );
            } else {
                $( '.rh_cta__btns .rh_btn--blackBG' ).css( 'width', w2 + "px" );
            }
        }
        if ( $( '.rh_cta__btns .rh_btn--secondary' ).length > 0 || $( '.rh_cta__btns .rh_btn--greyBG' ).length > 0 ) {
            var w1 = $( '.rh_cta__btns .rh_btn--secondary' ).outerWidth();
            var w2 = $( '.rh_cta__btns .rh_btn--greyBG' ).outerWidth();

            if ( w1 > w2 ) {
                $( '.rh_cta__btns .rh_btn--greyBG' ).css( 'width', w1 + "px" );
            } else {
                $( '.rh_cta__btns .rh_btn--secondary' ).css( 'width', w2 + "px" );
            }
        }

        /*-----------------------------------------------------------------------------------*/
        /* Optima Express IDX Support
        /*-----------------------------------------------------------------------------------*/
        $( '.ihf-grid-result-mlsnum-proptype' ).parent().parent().find( '.col-xs-9' ).toggleClass( 'col-xs-12' );
        $( '#ihf-main-container .ihf-detail-back-to-results a' ).html( '<span class="fas fa-angle-left"></span><span class="rh_back-link"> Back to Results</span>' );
        $( "#ihf-refine-search-button" ).on( 'click', function () {
            $( "#ihf-refine-search .dropdown-menu" ).fadeToggle();
            $( "#ihf-sort-values" ).fadeOut();
        } );
        $( "#ihf-sort-values" ).parent().on( 'click', function () {
            $( "#ihf-sort-values" ).fadeToggle();
            $( "#ihf-refine-search .dropdown-menu" ).fadeOut();
        } );
        $( "#ihf-main-container" ).on( 'mouseleave', function () {
            $( "#ihf-sort-values" ).fadeOut();
            $( "#ihf-refine-search .dropdown-menu" ).fadeOut();
        } );


        /*-----------------------------------------------------------------------------------*/
        /*  Post Nav Support
        /*-----------------------------------------------------------------------------------*/
        $( function () {
            var post_nav = $( '.inspiry-post-nav' );
            $( window ).on( 'scroll', function () {
                if ( $( window ).width() > 980 ) {
                    if ( $( this ).scrollTop() > 650 ) {
                        post_nav.fadeIn( 'fast' );
                        return;
                    }
                }
                post_nav.fadeOut( 'fast' );
            } );
        } );

        /*-----------------------------------------------------------------------------------*/
        /*  Property Ratings
        /*-----------------------------------------------------------------------------------*/
        if ( jQuery().barrating ) {
            $( '#rate-it' ).barrating( {
                theme         : 'fontawesome-stars',
                initialRating: 5,
            } );
        }

        /*-----------------------------------------------------------------------------------*/
        /* Home page properties pagination
        /*-----------------------------------------------------------------------------------*/
        var homePropertiesSection = $( '#home-properties-section' );

        // if homepage
        if ( homePropertiesSection.length && homePropertiesSection.hasClass( 'ajax-pagination' ) ) {

            $( document ).on( 'click', '#home-properties-section .pagination > a', function ( e ) {
                e.preventDefault();
                var homePropertiesContainer = $( '#home-properties-section-wrapper', homePropertiesSection );
                var paginationLinks         = $( '.pagination > a', homePropertiesSection );
                var svgLoader               = $( '.svg-loader', homePropertiesSection );
                var currentButton           = $( this );
                svgLoader.slideDown( 'fast' );
                homePropertiesContainer.fadeTo( 'slow', 0.5 );
                paginationLinks.removeClass( 'current' );
                currentButton.addClass( 'current' );
                homePropertiesContainer.load(
                    currentButton.attr( 'href' ) + ' ' + '#home-properties-section-inner',
                    function ( response, status, xhr ) {
                        if ( status == 'success' ) {
                            homePropertiesContainer.fadeTo( 100, 1, function () {
                            } );
                            svgLoader.slideUp( 'fast' );

                            $( 'html, body' ).animate( {
                                scrollTop : homePropertiesSection.find( '.rh_section__properties' ).offset().top - 32
                            }, 1000 );

                        } else {
                            homePropertiesContainer.fadeTo( 'slow', 1 );
                        }
                    }
                );
            } );
        }

        /*-----------------------------------------------------------------------------------*/
        /* AJAX Pagination for Listing & Archive Pages
        /*-----------------------------------------------------------------------------------*/
        const propertiesSection = $( '#properties-listing' );

        if ( propertiesSection.length && propertiesSection.hasClass( 'ajax-pagination' ) ) {
            const propertiesContainer = $( '.rh_page__listing', propertiesSection );
            const svgLoader           = $( '.svg-loader', propertiesSection );
            const statsContainer      = $( '.rh_pagination__stats' );
            const paginationContainer = $( '.rh_pagination' );
            const mapServiceType      = localized.mapService.toString();
            const page_id             = statsContainer.data( 'page-id' );
            $( document ).on( 'click', '#properties-listing .rh_pagination > a', function ( e ) {
                e.preventDefault();
                const paginationLinks = $( '.rh_pagination > a', propertiesSection );
                let currentButton     = $( this );
                svgLoader.slideDown( 'fast' );
                propertiesContainer.fadeTo( 'slow', 0.5 );
                let current_page = parseInt( currentButton.attr( 'data-page-number' ) );
                statsContainer.attr( 'data-page', current_page );
                paginationContainer.load( currentButton.attr( 'href' ) + ' ' + '.rh_pagination > *' );
                statsContainer.load( currentButton.attr( 'href' ) + ' ' + '.rh_pagination__stats > *' );
                propertiesContainer.load(
                    currentButton.attr( 'href' ) + ' ' + '.rh_page__listing > *',
                    function ( response, status, xhr ) {
                        if ( status == 'success' ) {
                            propertiesContainer.fadeTo( 100, 1, function () {
                                paginationLinks.removeClass( 'current' );
                                currentButton.addClass( 'current' );
                            } );
                            svgLoader.slideUp( 'fast' );

                            $( 'html, body' ).animate( {
                                scrollTop : propertiesSection.find( '.rh_page__listing' ).offset().top - 100
                            }, 1000 );

                        } else {
                            propertiesContainer.fadeTo( 'slow', 1 );
                        }
                        if ( typeof realhomesInfoboxPopupTrigger === 'function' ) {
                            realhomesInfoboxPopupTrigger();
                        }

                        // Binding Favorites & Compare Properties Features
                        if ( typeof realhomes_update_favorites === 'function' ) {
                            realhomes_update_favorites();
                        }
                        if ( typeof realhomes_update_compare_properties === 'function' ) {
                            realhomes_update_compare_properties();
                        }
                    }
                );
                // If this pagination is for ajax search results
                if ( propertiesSection.hasClass( 'realhomes_ajax_search' ) ) {
                    realhomes_update_ajax_map_results( current_page );
                    let currentQueryStrings = statsContainer.data( 'query-strings' );
                    let searchURL           = $( '.rh_page' ).data( 'search-url' );
                    if ( current_page === 1 ) {
                        window.history.pushState( {}, '', searchURL + currentQueryStrings );
                    } else {
                        window.history.pushState( {}, '', searchURL + 'page/' + current_page + '/' + currentQueryStrings );
                    }
                } else if ( typeof $( '.rh_page' ).data( 'search-url' ) != 'undefined' ) {
                    $.ajax( {
                        url     : ajaxurl,
                        type    : 'post',
                        data    : {
                            action  : 'realhomes_map_ajax_search_results',
                            page_id : page_id,
                            page    : current_page
                        },
                        success : ( response ) => {
                            let propertiesMapData = response.data.propertiesData;
                            if ( typeof realhomes_update_open_street_map !== 'undefined' && typeof mapServiceType !== "undefined" && mapServiceType === 'openstreetmaps' ) {
                                realhomes_update_open_street_map( propertiesMapData );
                            } else if ( typeof realhomes_update_mapbox !== 'undefined' && typeof mapServiceType !== "undefined" && mapServiceType === 'mapbox' ) {
                                $( '#map-head' ).empty().append( '<div id="listing-map"></div>' );
                                realhomes_update_mapbox( propertiesMapData );
                            } else if ( typeof realhomes_update_google_map !== 'undefined' ) {
                                realhomes_update_google_map( propertiesMapData );
                            }
                        }
                    } );
                    window.history.pushState( {}, '', currentButton.attr( 'href' ) );
                }
                window.history.pushState( {}, '', currentButton.attr( 'href' ) );
            } );
        }

        // Add equal heights to all the rows of all the columns
        var rowHeight   = -1;

        $( '.rh-compare-properties-row .rh-compare-properties-column p' ).each( function () {
            rowHeight = rowHeight > $( this ).outerHeight() ? rowHeight : $( this ).outerHeight();
        } );

        $( '.rh-compare-properties-row .rh-compare-properties-column > p' ).css( {
            height : rowHeight
        } );

        /*-------------------------------------------------------*/
        /*  Isotope
        /*------------------------------------------------------*/
        if ( $.isFunction( $.fn.isotope ) ) {
            const container      = $( '.isotope' ),
                  filterLinks    = $( '#filter-by a' ),
                  isotopeOptions = {
                      filter          : "*",
                      layoutMode      : 'fitRows',
                      itemSelector    : '.isotope-item',
                      animationEngine : 'best-available'
                  };

            // RTL support
            if ( $( 'body' ).hasClass( 'rtl' ) ) {
                isotopeOptions.originLeft = false;
            }

            /* to fix floating bugs due to variation in height */
            setTimeout( function () {
                container.isotope( isotopeOptions );
            }, 1000 );

            filterLinks.on( 'click', function ( e ) {
                let selector = $( this ).data( 'filter' );
                container.isotope( { filter : '.' + selector } );
                filterLinks.removeClass( 'active' );
                $( '#filter-by li' ).removeClass( 'current-cat' );
                $( this ).addClass( 'active' );
                e.preventDefault();
            } );
        }

        // Scroll effect
        function isInViewport( node ) {
            var rect = node.getBoundingClientRect()
            return (
                ( rect.height > 0 || rect.width > 0 ) &&
                rect.bottom >= 0 &&
                rect.right >= 0 &&
                rect.top <= ( window.innerHeight || document.documentElement.clientHeight ) &&
                rect.left <= ( window.innerWidth || document.documentElement.clientWidth )
            )
        }

        function scrollParallax( selector ) {
            var scrolled = $( window ).scrollTop();
            $( selector ).each( function ( index, element ) {
                var initY  = $( this ).offset().top;
                var height = $( this ).height();
                var endY   = initY + $( this ).height();

                // Check if the element is in the viewport.
                var visible = isInViewport( this );
                if ( visible ) {
                    var diff  = -scrolled + initY;
                    var ratio = Math.round( ( diff / height ) * 100 );
                    $( this ).css( 'background-position', 'center ' + parseInt( -( ratio ) ) + 'px' )
                }

            } )
        }

        function parallaxBanner( selector, unit, speed ) {
            var docHeight = $( document ).height();
            var scrolled  = $( window ).scrollTop();

            var parallaxSpeed = ( 0 + ( speed * ( scrolled / docHeight ) ) );

            $( selector ).css( 'background-position', 'center ' + parallaxSpeed + unit );
        }

        function parallaxBannerReverse( selector, unit, speed ) {
            var docHeight = $( document ).height();
            var scrolled  = $( window ).scrollTop();

            var parallaxSpeed = ( 0 - ( speed * ( scrolled / docHeight ) ) );

            $( selector ).css( 'background-position', 'center ' + parallaxSpeed + unit );
        }

        $( window ).on( 'scroll', function () {
            // scrollParallax('.rh_mod_parallax_sfoi');
            scrollParallax( '.rh_parallax_cta' );
            scrollParallax( '.rh_parallax' );
            parallaxBanner( '#rh-banner-attachment-parallax', '%', 150 );
            parallaxBannerReverse( '.rh_mod_parallax_sfoi', '%', 300 );
        } );

        // Mobile device user nav menu position
        function userNavPosition( selector ) {
            if ( window.matchMedia( '(max-width: 767px)' ).matches ) {
                var getHeaderHeight = $( '.rh_header__wrap' ).height();
                var getBarHeight    = $( '.rh_menu__user' ).height();
                var getTopHeight    = getHeaderHeight - getBarHeight;
                $( selector ).css( 'top', getTopHeight / 2 + 'px' );
            } else {
                $( selector ).css( 'top', 'auto' );
            }
        }

        userNavPosition( '.rh_header_advance .user_menu_wrapper' );
        userNavPosition( '.rh_header_advance .rh_menu .main-menu' );

        $( window ).on( 'resize', function () {
            userNavPosition( '.rh_header_advance .user_menu_wrapper' );
            userNavPosition( '.rh_header_advance .rh_menu .main-menu' );
        } );

        // Function to add Whatsapp sharing
        function decorateWhatsAppLink() {
            //set up the url
            var url = 'https://api.whatsapp.com/send?text=';

            var thisShareData = $( '.share-this' );

            //get property title
            var name = thisShareData.data( 'property-name' );

            //get property permalink
            var permalink = thisShareData.data( 'property-permalink' );

            //encode the text
            var encodedText = encodeURIComponent( name + ' ' + permalink );

            //find the link
            var whatsApp = $( ".inspiry_whats_app_share_link" );
            //set the href attribute on the link
            whatsApp.attr( 'href', url + encodedText );
        }

        decorateWhatsAppLink();

        /*-----------------------------------------------------------------------------------*/
        /* Geolocation Field Places AutoComplete
        /*-----------------------------------------------------------------------------------*/
        if ( typeof google !== undefined ) {
            let input = document.getElementById( 'geolocation-address' );
            if ( input ) {
                function initAutocomplete() {
                    const locationFieldsWrap = $( '#location-fields-wrap' );
                    let autocomplete         = new google.maps.places.Autocomplete( input );

                    // Set the data fields to return when the user selects a place.
                    autocomplete.setFields( ['address_components', 'geometry', 'icon', 'name'] );

                    // Handle place selection
                    autocomplete.addListener( 'place_changed', function () {
                        const place = autocomplete.getPlace();
                        locationFieldsWrap.find( '.location-field-lat' ).val( place.geometry.location.lat() );
                        locationFieldsWrap.find( '.location-field-lng' ).val( place.geometry.location.lng() );
                    } );
                }

                initAutocomplete();
            }
        }

        /*-----------------------------------------------------------------------------------*/
        /* Geolocation Radius Slider for Properties Search Form
        /*-----------------------------------------------------------------------------------*/
        const geolocationRadiusWrapper = $( '#geolocation-radius-slider-wrapper' );
        if ( geolocationRadiusWrapper.length ) {
            const geolocationRadiusSlider = geolocationRadiusWrapper.find( '#geolocation-radius-slider' );
            geolocationRadiusSlider.slider( {
                range : 'max',
                value : geolocationRadiusSlider.data( 'value' ),
                min   : geolocationRadiusSlider.data( 'min-value' ),
                max   : geolocationRadiusSlider.data( 'max-value' ),
                slide : function ( event, ui ) {
                    geolocationRadiusWrapper.find( 'strong' ).text( ui.value + ' ' + geolocationRadiusSlider.data( 'unit' ) );
                    geolocationRadiusWrapper.find( '#rh-geolocation-radius' ).val( ui.value );
                }
            } );
        }

        // Fullscreen search overlay toggle
        $( '.rh-search-toggle' ).on( 'click', function () {
            $( '#rh-search-overlay' ).addClass( 'is-active' );
        } );

        $( document ).on( 'click', '.rh-search-close', function () {
            $( '#rh-search-overlay' ).removeClass( 'is-active' );
        } );

        $( document ).on( 'keyup', function ( e ) {
            if ( e.key === 'Escape' ) {
                $( '#rh-search-overlay' ).removeClass( 'is-active' );
            }
        } );

    } );
} )( jQuery );