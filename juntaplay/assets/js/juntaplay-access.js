(function ($) {
    'use strict';

    var i18n = (window.wp && window.wp.i18n) ? window.wp.i18n : null;

    function __(text) {
        if (i18n && typeof i18n.__ === 'function') {
            return i18n.__(text, 'juntaplay');
        }

        return text;
    }

    function getAjaxEndpoint() {
        if (window.JuntaPlay && window.JuntaPlay.ajax) {
            return window.JuntaPlay.ajax;
        }

        if (typeof window.ajaxurl !== 'undefined') {
            return window.ajaxurl;
        }

        return '';
    }

    function getAjaxNonce() {
        if (window.JuntaPlay && window.JuntaPlay.nonce) {
            return window.JuntaPlay.nonce;
        }

        return '';
    }

    function renderCredentials($card, payload) {
        var $panel = $card.find('[data-group-access-panel]').first();

        if (!$panel.length) {
            return;
        }

        var $details = $panel.find('[data-group-access-details]').first();
        var $hint = $panel.find('[data-group-access-hint]').first();
        var fields = (payload && payload.credentials && Array.isArray(payload.credentials)) ? payload.credentials : [];
        var hintText = (payload && payload.hint) ? payload.hint.toString() : '';
        var hintHtml = (payload && payload.hint_html) ? payload.hint_html.toString() : '';

        if ($details.length) {
            $details.empty();

            fields.forEach(function (field) {
                if (!field || typeof field !== 'object') {
                    return;
                }

                var label = field.label ? field.label.toString() : '';
                var value = field.value ? field.value.toString() : '';
                var htmlValue = field.html ? field.html.toString() : '';

                if (label === '' && value === '') {
                    return;
                }

                var $dt = $('<dt>', { 'class': 'juntaplay-group-card__access-label' }).text(label);
                var $dd = $('<dd>', { 'class': 'juntaplay-group-card__access-value' });

                if (field.type === 'url' && value !== '') {
                    $dd.append($('<a>', {
                        href: value,
                        target: '_blank',
                        rel: 'noopener',
                        text: value
                    }));
                } else if (htmlValue !== '') {
                    $dd.html(htmlValue);
                } else {
                    $dd.text(value);
                }

                $details.append($dt).append($dd);
            });

            if (fields.length > 0) {
                $details.removeAttr('hidden');
            } else {
                $details.attr('hidden', 'hidden');
            }
        }

        if ($hint.length) {
            if (hintHtml !== '') {
                $hint.html(hintHtml).removeAttr('hidden');
            } else {
                if (hintText === '' && fields.length === 0) {
                    hintText = __('O administrador ainda não cadastrou os dados de acesso deste grupo.');
                } else if (hintText === '') {
                    hintText = __('As credenciais foram atualizadas para este grupo.');
                }

                $hint.text(hintText).removeAttr('hidden');
            }
        }

        $panel.removeAttr('hidden');

        var $extended = $panel.closest('.juntaplay-group-card__details-extended');
        if ($extended.length) {
            $extended.removeAttr('hidden');
            $extended.closest('.juntaplay-group-card').addClass('is-expanded');
        }
    }

    function renderError($card, message) {
        var $panel = $card.find('[data-group-access-panel]').first();

        if (!$panel.length) {
            return;
        }

        var $hint = $panel.find('[data-group-access-hint]').first();

        if ($hint.length) {
            $hint.text(message || __('Não foi possível recuperar os dados de acesso agora. Tente novamente em instantes.'))
                .removeAttr('hidden');
        }

        $panel.removeAttr('hidden');
    }

    $(document).on('click', '[data-group-access]', function (event) {
        event.preventDefault();

        var $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }

        var groupId = parseInt($button.data('groupAccess'), 10);

        if (!groupId || Number.isNaN(groupId)) {
            return;
        }

        var endpoint = getAjaxEndpoint();

        if (!endpoint) {
            return;
        }

        var nonce = getAjaxNonce();
        var $card = $button.closest('[data-group-item]');

        $button.prop('disabled', true).addClass('is-loading');

        $.ajax({
            url: endpoint,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'juntaplay_group_credentials',
                nonce: nonce,
                group_id: groupId
            }
        }).done(function (response) {
            if (response && response.success) {
                renderCredentials($card, response.data || {});
                return;
            }

            var message = (response && response.data && response.data.message)
                ? response.data.message.toString()
                : __('Não foi possível recuperar os dados de acesso agora. Tente novamente em instantes.');

            renderError($card, message);
        }).fail(function (xhr) {
            var message = __('Não foi possível recuperar os dados de acesso agora. Tente novamente em instantes.');

            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                message = xhr.responseJSON.data.message.toString();
            }

            renderError($card, message);
        }).always(function () {
            $button.prop('disabled', false).removeClass('is-loading');
        });
    });
})(jQuery);
