(function ($) {
    'use strict';

    function initCarousel() {
        var $carousel = $('.adc-carousel');
        if (! $carousel.length) {
            return;
        }

        $carousel.each(function () {
            var $container = $(this);
            var $slides = $container.find('.adc-slide');
            var $progress = $container.find('.adc-progress span');
            var index = 0;

            function showSlide(i) {
                index = i;
                $slides
                    .removeClass('is-active')
                    .attr('aria-hidden', 'true')
                    .eq(index)
                    .addClass('is-active')
                    .attr('aria-hidden', 'false');
                $progress.removeClass('is-active').eq(index).addClass('is-active');
            }

            $container.find('.adc-next').on('click', function (e) {
                e.preventDefault();
                if (index < $slides.length - 1) {
                    showSlide(index + 1);
                } else {
                    var url = $(this).data('target');
                    if (url) {
                        window.location.href = url;
                    }
                }
            });

            $container.find('.adc-skip').on('click', function (e) {
                e.preventDefault();
                var url = $(this).data('target');
                if (url) {
                    window.location.href = url;
                }
            });

            showSlide(0);
        });
    }

    function initPasswordToggle() {
        $('.adc-toggle-password').on('click', function (e) {
            e.preventDefault();
            var $target = $(this).prev('input');
            if (! $target.length) {
                return;
            }
            var type = $target.attr('type') === 'password' ? 'text' : 'password';
            $target.attr('type', type);
            $(this).attr('aria-pressed', type === 'text');
        });
    }

    function initValidation() {
        $('form.adc-validate').on('submit', function (e) {
            var valid = true;
            var $form = $(this);
            $form.find('[data-required="true"]').each(function () {
                var $field = $(this);
                if (!$field.val()) {
                    valid = false;
                    $field.addClass('adc-invalid');
                } else {
                    $field.removeClass('adc-invalid');
                }
            });

            if (!valid) {
                e.preventDefault();
            }
        });
    }

    $(function () {
        initCarousel();
        initPasswordToggle();
        initValidation();
    });
})(jQuery);
