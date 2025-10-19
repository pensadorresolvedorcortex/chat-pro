(function ($) {
    'use strict';

    function generateId() {
        return 'alt_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    }

    function normalizeAnswers(raw) {
        if (!Array.isArray(raw)) {
            return [];
        }
        return raw.map(function (item, index) {
            return {
                id: item.id || generateId() + '_' + index,
                text: item.text || '',
                is_correct: !!item.is_correct,
                feedback: item.feedback || ''
            };
        });
    }

    function createAnswerRow(answer) {
        var row = $('<div/>', { 'class': 'questoes-answer-item', 'data-id': answer.id });

        var radio = $('<label/>', { 'class': 'questoes-answer-correct-label' })
            .append($('<input/>', {
                type: 'radio',
                name: 'questoes_answer_correct',
                'class': 'questoes-answer-correct',
                value: answer.id,
                checked: !!answer.is_correct
            }))
            .append($('<span/>').text(questoesQuestionMeta.messages.correct));

        var textarea = $('<textarea/>', {
            'class': 'questoes-answer-text',
            placeholder: questoesQuestionMeta.messages.placeholder || ''
        }).val(answer.text);

        var feedback = $('<textarea/>', {
            'class': 'questoes-answer-feedback',
            placeholder: questoesQuestionMeta.messages.feedback
        }).val(answer.feedback);

        var removeButton = $('<button/>', {
            type: 'button',
            'class': 'button-link-delete questoes-remove-answer'
        }).text(questoesQuestionMeta.messages.remove);

        row.append(radio);
        row.append($('<div/>', { 'class': 'questoes-answer-content' }).append(textarea).append(feedback));
        row.append(removeButton);

        return row;
    }

    function syncAnswers(container, answers) {
        if (!answers.length) {
            answers.push({ id: generateId(), text: '', is_correct: true, feedback: '' });
            answers.push({ id: generateId(), text: '', is_correct: false, feedback: '' });
        }

        var hasCorrect = answers.some(function (answer) { return answer.is_correct; });
        if (!hasCorrect) {
            answers[0].is_correct = true;
        }

        container.find('#questoes-answer-data').val(JSON.stringify(answers));
    }

    $(function () {
        var manager = $('#questoes-answer-manager');
        if (!manager.length) {
            return;
        }

        var answers = normalizeAnswers(manager.data('answers'));
        var list = manager.find('.questoes-answer-list');

        function render() {
            list.empty();
            answers.forEach(function (answer) {
                list.append(createAnswerRow(answer));
            });
            syncAnswers(manager, answers);
        }

        manager.on('click', '.questoes-add-answer', function (event) {
            event.preventDefault();
            answers.push({ id: generateId(), text: '', is_correct: false, feedback: '' });
            render();
        });

        manager.on('click', '.questoes-remove-answer', function (event) {
            event.preventDefault();
            if (answers.length <= 2) {
                window.alert(questoesQuestionMeta.messages.minimum);
                return;
            }
            var id = $(this).closest('.questoes-answer-item').data('id');
            answers = answers.filter(function (answer) { return answer.id !== id; });
            render();
        });

        manager.on('input change', '.questoes-answer-text', function () {
            var id = $(this).closest('.questoes-answer-item').data('id');
            answers = answers.map(function (answer) {
                if (answer.id === id) {
                    answer.text = $(this).val();
                }
                return answer;
            }.bind(this));
            syncAnswers(manager, answers);
        });

        manager.on('input change', '.questoes-answer-feedback', function () {
            var id = $(this).closest('.questoes-answer-item').data('id');
            answers = answers.map(function (answer) {
                if (answer.id === id) {
                    answer.feedback = $(this).val();
                }
                return answer;
            }.bind(this));
            syncAnswers(manager, answers);
        });

        manager.on('change', '.questoes-answer-correct', function () {
            var id = $(this).closest('.questoes-answer-item').data('id');
            answers = answers.map(function (answer) {
                answer.is_correct = (answer.id === id);
                return answer;
            });
            syncAnswers(manager, answers);
        });

        render();
    });
})(jQuery);
