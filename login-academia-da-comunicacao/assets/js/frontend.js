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

    function initAuthModal() {
        var $modal = $('#adc-auth-modal');

        if (! $modal.length) {
            return;
        }

        var $body = $('body');
        var $panels = $modal.find('[data-modal-panel]');
        var $tabs = $modal.find('[data-modal-tab]');
        var $dialog = $modal.find('.adc-modal__dialog');

        function setActivePanel(target) {
            if (! target) {
                target = 'login';
            }

            $panels
                .removeClass('is-active')
                .attr('aria-hidden', 'true');

            var $panel = $panels.filter('[data-modal-panel="' + target + '"]');
            if (! $panel.length) {
                $panel = $panels.first();
            }

            $panel
                .addClass('is-active')
                .attr('aria-hidden', 'false');

            $tabs
                .removeClass('is-active')
                .attr('aria-selected', 'false');

            var $tab = $tabs.filter('[data-modal-tab="' + target + '"]');
            if (! $tab.length) {
                $tab = $tabs.first();
            }

            $tab
                .addClass('is-active')
                .attr('aria-selected', 'true');

            var $focus = $panel.find('input, select, textarea, button').filter(':visible').first();
            if ($focus.length) {
                $focus.trigger('focus');
            }

            if ($dialog.length) {
                var labelledBy = $panel.find('.adc-modal__title').attr('id');
                if (labelledBy) {
                    $dialog.attr('aria-labelledby', labelledBy);
                }
            }
        }

        function openModal(target) {
            $modal
                .addClass('is-open')
                .attr('aria-hidden', 'false');
            $body.addClass('adc-modal-open');
            setActivePanel(target);
        }

        function closeModal() {
            $modal
                .removeClass('is-open')
                .attr('aria-hidden', 'true');
            $body.removeClass('adc-modal-open');
        }

        $('[data-modal-trigger]').on('click', function (e) {
            var $trigger = $(this);
            var target = $trigger.data('modal-trigger');
            if (! target) {
                return;
            }

            e.preventDefault();

            var redirect = $trigger.data('redirect');
            if (redirect) {
                var $form = $modal.find('[data-modal-panel="' + target + '"] form');
                if ($form.length) {
                    $form.find('input[name="redirect_to"]').val(redirect);
                }
            }
            openModal(target);
        });

        $modal.find('[data-modal-close]').on('click', function (e) {
            e.preventDefault();
            closeModal();
        });

        $modal.find('.adc-modal__overlay').on('click', function (e) {
            e.preventDefault();
            closeModal();
        });

        $modal.on('click', function (e) {
            if ($(e.target).is($modal)) {
                closeModal();
            }
        });

        $tabs.on('click', function (e) {
            e.preventDefault();
            var target = $(this).data('modal-tab');
            setActivePanel(target);
        });

        $(document).on('keydown.adcAuthModal', function (e) {
            if ((e.key && e.key.toLowerCase() === 'escape') || e.keyCode === 27) {
                if ($modal.hasClass('is-open')) {
                    closeModal();
                }
            }
        });

        $panels.each(function () {
            var $panel = $(this);
            $panel.attr('aria-hidden', ! $panel.hasClass('is-active'));
        });
    }

    $(function () {
        initCarousel();
        initPasswordToggle();
        initValidation();
        initAuthModal();
    });
})(jQuery);
