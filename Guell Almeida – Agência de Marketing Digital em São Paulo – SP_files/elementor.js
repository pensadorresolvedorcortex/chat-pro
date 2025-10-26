( function( $ ) {
    "use strict";
    function coreriverElAfterRender(){
        let _elementor = typeof elementor != 'undefined' ? elementor : elementorFrontend;
        _elementor.hooks.addFilter('pxl_element_container/after-render', function(ouput, settings) {
            if(typeof settings.pxl_parallax_bg_img != 'undefined' && settings.pxl_parallax_bg_img.url != ''){
                ouput += '<div class="pxl-section-bg-parallax"></div>';
            }
            if (typeof settings.el_number_layer_overlay != 'undefined' && settings.el_number_layer_overlay == '1') {
                ouput += `<div class="e-con-overlay"></div>`;
            }    
            if (typeof settings.el_overlays != 'undefined' && settings.el_overlays.length > 0) {
                settings.el_overlays.forEach(function (item) {
                    let classes = 'e-con-overlay elementor-repeater-item-' + item._id;
                    ouput += `<div class="${classes}"></div>`;
                });
            }    
            return ouput;
        });
    } 

    function coreriverElBeforeRender(){
        let _elementor = typeof elementor != 'undefined' ? elementor : elementorFrontend;
        
        _elementor.hooks.addFilter( 'pxl_element_container/before-render', function( ouput, settings) {
            return ouput;
        });
    } 


    var PXL_Icon_Contact_Form = function( $scope, $ ) {
        setTimeout(function () {
            $('.pxl--item').each(function () {
                var icon_input = $(this).find(".pxl--form-icon"),
                control_wrap = $(this).find('.wpcf7-form-control');
                control_wrap.before(icon_input.clone());
                icon_input.remove();
            });
        }, 10);
    };

    function coreriver_split_scroll_color() {
        var revealContainers = document.querySelectorAll(".pxl-slip-text-color");
        revealContainers.forEach((container) => {
            var text = new SplitText(container, {type: 'words, chars'});
            gsap.fromTo(text.chars, 
            {
                position: 'relative',
                display: 'inline-block',
                opacity: 0.2,
                x: -5,
            }, 
            {
                opacity: 1,
                x: 0,
                stagger: 0.1,
                scrollTrigger: {
                    trigger: container,
                    toggleActions: "play pause reverse pause",
                    start: "top 90%",
                    end: "top 40%",
                    scrub: 0.2,
                }
            }
            );
        });
    }
    function coreriver_parallax_scale($scope) {
        setTimeout(function () {
            let revealContainers = document.querySelectorAll(".parallax-scale");
            revealContainers.forEach((container) => {
                let image = container.querySelector("img");
                let tb = gsap.timeline({
                    scrollTrigger: {
                        trigger: container,
                        toggleActions: "restart none none reset",
                        start: "top 95%",
                        end: "bottom 70%",
                        scrub: true,
                    }
                });

                gsap.set(container, { x: "0px", rotationY: "0" });
                gsap.set(image, { x: "0px", rotationY: "0" });

                tb.to(container, { 
                    x: "-21px", 
                    rotationY: "-27deg", 
                    ease: Power4.out,
                    duration: 1.5
                });
                tb.to(image, { 
                    x: "-21px", 
                    rotationY: "-27deg", 
                    ease: Power4.out,
                    duration: 1.5
                }, "-=1"); //

            });

        }, 100);
    }
    function coreriver_gsap_scroll_trigger($scope){ 
        gsap.registerPlugin(ScrollTrigger);
        const images = gsap.utils.toArray('img');  
        const showDemo = () => {
            document.body.style.overflow = 'auto';
            gsap.utils.toArray($scope.find('.pxl-horizontal-scroll .scroll-trigger')).forEach((section, index) => {
                const w = section;
                var x = w.scrollWidth * -1;
                var xEnd = 0;
                if($(section).closest('.pxl-horizontal-scroll').hasClass('revesal')){   
                    x = '100%';
                    xEnd = (w.scrollWidth - section.offsetWidth) * -1;
                } 
                gsap.fromTo(w, { x }, {
                    x: xEnd,
                    scrollTrigger: { 
                        trigger: section, 
                        scrub: 0.5 
                    }
                });
            });
        }
        showDemo();
    }
    function coreriver_split_text($scope){
        setTimeout(function () {
            var st = $scope.find(".pxl-split-text");
            if(st.length == 0) return;
            gsap.registerPlugin(SplitText);
            st.each(function(index, el) {
                el.split = new SplitText(el, { 
                    type: "lines,words,chars",
                    linesClass: "split-line"
                });
                gsap.set(el, { perspective: 400 });

                if( $(el).hasClass('split-in-fade') ){
                    $(el).addClass('active');
                    gsap.set(el.split.chars, {
                        opacity: 0,
                        ease: "Back.easeOut",
                    });
                }
                if( $(el).hasClass('split-in-right') ){
                    gsap.set(el.split.chars, {
                        opacity: 0,
                        x: "50",
                        ease: "Back.easeOut",
                    });
                }
                if( $(el).hasClass('split-in-left') ){
                    gsap.set(el.split.chars, {
                        opacity: 0,
                        x: "-50",
                        ease: "circ.out",
                    });
                }
                if( $(el).hasClass('split-in-up') ){
                    gsap.set(el.split.chars, {
                        opacity: 0,
                        y: "80",
                        ease: "circ.out",
                    });
                }
                if( $(el).hasClass('split-in-down') ){
                    gsap.set(el.split.chars, {
                        opacity: 0,
                        y: "-80",
                        ease: "circ.out",
                    });
                }
                if( $(el).hasClass('split-in-rotate') ){
                    gsap.set(el.split.chars, {
                        opacity: 0,
                        rotateX: "50deg",
                        ease: "circ.out",
                    });
                }
                if( $(el).hasClass('split-in-scale') ){
                    gsap.set(el.split.chars, {
                        opacity: 0,
                        scale: "0.5",
                        ease: "circ.out",
                    });
                }
                el.anim = gsap.to(el.split.chars, {
                    scrollTrigger: {
                        trigger: el,
                        toggleActions: "restart pause resume reverse",
                        start: "top 90%",
                    },
                    x: "0",
                    y: "0",
                    rotateX: "0",
                    scale: 1,
                    opacity: 1,
                    duration: 0.8, 
                    stagger: 0.02,
                });
            });

        }, 200);
    }

    function coreriver_box_go_to_x($scope){
        gsap.to(".box", { 
            x: 200,
            duration: 2,
            delay: 2,
        });
    }

    function coreriver_box_go_to_y($scope){
        gsap.to(".box-y", { 
            y: -120,
            duration: 1.2,
            delay: 1.2,
        });
    }

    function coreriver_box_rotation($scope){
        let tl = gsap.timeline({repeat: -1, repeatDelay: 1, yoyo: true})
        tl.to(".box-green", { rotation: 360 });
        tl.to(".box-purple", { rotation: 360 });
        tl.to(".box-orange", { rotation: 360 });
    }

    function coreriver_zoom_point(){
        elementorFrontend.waypoint($(document).find('.pxl-zoom-point'), function () {
            var offset = $(this).offset();
            var offset_top = offset.top;
            var scroll_top = $(window).scrollTop();
        }, {
            offset: -100,
            triggerOnce: true
        });
    }

    function coreriver_scroll_fixed_section(){
        if($('.pxl-section-fix-top').length > 0) {
            ScrollTrigger.matchMedia({
                "(min-width: 991px)": function() {
                    const pinnedSections = ['.pxl-section-fix-top'];
                    pinnedSections.forEach(className => {
                        gsap.to(".pxl-section-fix-bottom", {
                            scrollTrigger: {
                                trigger: ".pxl-section-fix-bottom",
                                scrub: true,
                                pin: className,
                                pinSpacing: false,
                                start: 'top bottom',
                                end: "bottom top",
                            },
                        });
                    });
                }
            });
        }
    }

    function coreriver_scroll_trigger_circle($scope) {
        const textElements = gsap.utils.toArray('.pxl-video-player .bg-image');
        textElements.forEach(text => {
            gsap.to(text, {
                y: '30%',
                scaleX: 1.3,
                scaleY: 1.3,
                ease: 'none',
                scrollTrigger: {
                    trigger: text,
                    start: 'center 80%',
                    end: 'center 0%',
                    scrub: true,
                },
            });
        });
    }
    function coreriver_scroll_line_gradient( $scope ) {
        $scope.find(".pxl-item--title.pxl-heading__style-scroll-gradient").each(function () {
          var $container = $(this).find(".pxl-heading--text");
          const lines_list = new SplitText($container[0], {
            type: "lines",
            linesClass: "split-line"
          });

          // Scroll animation
          let tl = gsap.timeline({
            scrollTrigger: {
              trigger: $container[0],
              toggleActions: "play pause reverse pause",
              start: "top 70%",
              end: "top 40%",
              scrub: 0.7
            }
          });

          lines_list.lines.forEach((line) => {
            tl.fromTo(
              line,
              { backgroundPosition: "100% 100%" },
              {
                backgroundPosition: "0% 100%",
                duration: 0.5,
                ease: "none"
              },
              ">0"
            );
          });
        });
    }
    function coreriver_text_marquee($scope){
        const text_marquee = $scope.find('.pxl-text--marquee');
        const boxes = gsap.utils.toArray(text_marquee);
        const loop = text_horizontalLoop(boxes, {paused: false,repeat: -1,});
        function text_horizontalLoop(items, config) {
            items = gsap.utils.toArray(items);
            config = config || {};
            let tl = gsap.timeline({repeat: config.repeat, paused: config.paused, defaults: {ease: "none"}, onReverseComplete: () => tl.totalTime(tl.rawTime() + tl.duration() * 100)}),
            length = items.length,
            startX = items[0].offsetLeft,
            times = [],
            widths = [],
            xPercents = [],
            curIndex = 0,
            pixelsPerSecond = (config.speed || 1) * 100,
            snap = config.snap === false ? v => v : gsap.utils.snap(config.snap || 1),
            totalWidth, curX, distanceToStart, distanceToLoop, item, i;
            gsap.set(items, {
                xPercent: (i, el) => {
                    let w = widths[i] = parseFloat(gsap.getProperty(el, "width", "px"));
                    xPercents[i] = snap(parseFloat(gsap.getProperty(el, "x", "px")) / w * 100 + gsap.getProperty(el, "xPercent"));
                    return xPercents[i];
                }
            });
            gsap.set(items, {x: 0});
            totalWidth = items[length-1].offsetLeft + xPercents[length-1] / 100 * widths[length-1] - startX + items[length-1].offsetWidth * gsap.getProperty(items[length-1], "scaleX") + (parseFloat(config.paddingRight) || 0);
            for (i = 0; i < length; i++) {
                item = items[i];
                curX = xPercents[i] / 100 * widths[i];
                distanceToStart = item.offsetLeft + curX - startX;
                distanceToLoop = distanceToStart + widths[i] * gsap.getProperty(item, "scaleX");
                tl.to(item, {xPercent: snap((curX - distanceToLoop) / widths[i] * 100), duration: distanceToLoop / pixelsPerSecond}, 0)
                .fromTo(item, {xPercent: snap((curX - distanceToLoop + totalWidth) / widths[i] * 100)}, {xPercent: xPercents[i], duration: (curX - distanceToLoop + totalWidth - curX) / pixelsPerSecond, immediateRender: false}, distanceToLoop / pixelsPerSecond)
                .add("label" + i, distanceToStart / pixelsPerSecond);
                times[i] = distanceToStart / pixelsPerSecond;
            }
            function toIndex(index, vars) {
                vars = vars || {};
                (Math.abs(index - curIndex) > length / 2) && (index += index > curIndex ? -length : length);
                let newIndex = gsap.utils.wrap(0, length, index),
                time = times[newIndex];
                if (time > tl.time() !== index > curIndex) { 
                    vars.modifiers = {time: gsap.utils.wrap(0, tl.duration())};
                    time += tl.duration() * (index > curIndex ? 1 : -1);
                }
                curIndex = newIndex;
                vars.overwrite = true;
                return tl.tweenTo(time, vars);
            }
            tl.next = vars => toIndex(curIndex+1, vars);
            tl.previous = vars => toIndex(curIndex-1, vars);
            tl.current = () => curIndex;
            tl.toIndex = (index, vars) => toIndex(index, vars);
            tl.times = times;
            tl.progress(1, true).progress(0, true);
            if (config.reversed) {
                tl.vars.onReverseComplete();
                tl.reverse();
            }
            return tl;
        }
    }

    $( window ).on( 'elementor/frontend/init', function() {
        coreriverElAfterRender();
        coreriverElBeforeRender();
        coreriver_zoom_point();
        coreriver_scroll_fixed_section();
        coreriver_split_scroll_color();
        coreriver_parallax_scale();
        coreriver_box_go_to_x();
        coreriver_box_go_to_y();
        coreriver_box_rotation();
        elementorFrontend.hooks.addAction( 'frontend/element_ready/pxl_contact_form.default', PXL_Icon_Contact_Form );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/pxl_heading.default', function( $scope ) {
            coreriver_split_text($scope);
            coreriver_scroll_line_gradient($scope);
        } );

        elementorFrontend.hooks.addAction( 'frontend/element_ready/pxl_text_marquee.default', function( $scope ) {
            coreriver_text_marquee($scope);
        } );

        elementorFrontend.hooks.addAction( 'frontend/element_ready/pxl_product_grid.default', function( $scope ) {
            coreriver_split_text($scope);
        } );

        elementorFrontend.hooks.addAction( 'frontend/element_ready/pxl_post_carousel.default', function( $scope ) {
            coreriver_split_text($scope);
        } );

        elementorFrontend.hooks.addAction( 'frontend/element_ready/pxl_horizontal_scroll.default', function( $scope ) {
            coreriver_gsap_scroll_trigger($scope);
        } );
    } );

} )( jQuery );