(function ($) {
    'use strict';

    function download(filename, content) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:application/json;charset=utf-8,' + encodeURIComponent(content));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }

    $(function () {
        $('#questoes-export-config').on('click', function () {
            var data = $('#questoes-data').closest('form').serializeArray();
            var config = {};
            data.forEach(function (item) {
                config[item.name] = item.value;
            });
            download('questoes-config.json', JSON.stringify(config, null, 2));
        });

        $('#questoes-export-data').on('click', function () {
            var json = $('#questoes-data').val();
            download('questoes-data.json', json || '{}');
        });

        $('#questoes-upload').on('change', function (event) {
            var file = event.target.files[0];
            if (!file) {
                return;
            }
            var reader = new FileReader();
            reader.onload = function (loadEvent) {
                $('#questoes-data').val(loadEvent.target.result);
            };
            reader.readAsText(file);
        });

        $('#questoes-import').on('change', function (event) {
            var file = event.target.files[0];
            if (!file) {
                return;
            }
            var reader = new FileReader();
            reader.onload = function (loadEvent) {
                try {
                    var parsed = JSON.parse(loadEvent.target.result);
                    Object.keys(parsed).forEach(function (key) {
                        var field = $('[name="' + key + '"]');
                        if (field.length) {
                            field.val(parsed[key]);
                        }
                    });
                } catch (error) {
                    window.alert(questoesAdmin.messages.invalid);
                }
            };
            reader.readAsText(file);
        });
    });
})(jQuery);
