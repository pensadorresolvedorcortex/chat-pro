(function ($) {
    'use strict';

    function handleMediaSelect(event) {
        event.preventDefault();

        var $button = $(event.currentTarget);
        var targetId = $button.data('target');
        var $field = $button.closest('.adc-media-field');
        var $input = $('#' + targetId);
        var $preview = $field.find('.adc-media-preview img');
        var $altInput = $field.find('input[name$="[image_alt]"]');

        if (!wp || !wp.media) {
            return;
        }

        var frame = wp.media({
            title: ADCLoginDashboard.chooseImage,
            button: {
                text: ADCLoginDashboard.setImage
            },
            library: {
                type: 'image'
            },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();

            $input.val(attachment.id);
            if (attachment.url) {
                $preview.attr('src', attachment.url);
            }

            if ($altInput.length && !$altInput.val() && attachment.alt) {
                $altInput.val(attachment.alt);
            }
        });

        frame.open();
    }

    function handleMediaRemove(event) {
        event.preventDefault();

        var $button = $(event.currentTarget);
        var $field = $button.closest('.adc-media-field');
        var $input = $field.find('.adc-media-input');
        var $preview = $field.find('.adc-media-preview img');
        var $altInput = $field.find('input[name$="[image_alt]"]');
        var fallback = $field.data('default-src');

        $input.val('');

        if (fallback) {
            $preview.attr('src', fallback);
        }

        if ($altInput.length && !$altInput.data('persist')) {
            $altInput.val('');
        }
    }

    $(document).on('click', '.adc-media-select', handleMediaSelect);
    $(document).on('click', '.adc-media-remove', handleMediaRemove);
})(jQuery);
