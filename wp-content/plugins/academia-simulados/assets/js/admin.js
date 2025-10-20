(function($){
    'use strict';

    function renumberQuestions(container) {
        container.find('.academia-simulados-question').each(function(index){
            var question = $(this);
            question.attr('data-index', index);
            question.find('.question-number').text(index + 1);
            question.find('textarea, input').each(function(){
                var input = $(this);
                var name = input.attr('name');
                if (!name) {
                    return;
                }
                name = name.replace(/academia_simulado_questions\[[0-9]+\]/, 'academia_simulado_questions[' + index + ']');
                input.attr('name', name);
            });
        });
    }

    function addAnswer(question){
        var answers = question.find('.academia-simulados-answers');
        var index = answers.find('.academia-simulados-answer').length;
        var template = $(
            '<p class="academia-simulados-answer">' +
                '<label>' +
                    '<span class="screen-reader-text">Resposta</span>' +
                    '<input type="text" class="widefat" required />' +
                '</label>' +
                '<label class="academia-simulados-correct">' +
                    '<input type="radio" />' +
                    '<span>' + window.academiaSimuladosAdmin.correctLabel + '</span>' +
                '</label>' +
                '<button type="button" class="button-link remove-answer" aria-label="' + window.academiaSimuladosAdmin.removeAnswerLabel + '">&times;</button>' +
            '</p>'
        );

        template.find('input[type="text"]').attr('name', questionIndexName(question, 'answers[' + index + ']'));
        template.find('input[type="radio"]').attr('name', questionIndexName(question, 'correct')).val(index);
        answers.append(template);
    }

    function questionIndexName(question, field){
        var index = parseInt(question.attr('data-index'), 10);
        return 'academia_simulado_questions[' + index + '][' + field + ']';
    }

    $(document).ready(function(){
        var container = $('.academia-simulados-questions');
        if (!container.length) {
            return;
        }

        container.on('click', '.add-answer', function(){
            var question = $(this).closest('.academia-simulados-question');
            addAnswer(question);
        });

        container.on('click', '.remove-answer', function(){
            var answer = $(this).closest('.academia-simulados-answer');
            var question = answer.closest('.academia-simulados-question');
            answer.remove();
            question.find('.academia-simulados-answer').each(function(index){
                var row = $(this);
                row.find('input[type="text"]').attr('name', questionIndexName(question, 'answers[' + index + ']'));
                row.find('input[type="radio"]').attr('name', questionIndexName(question, 'correct')).val(index);
            });
        });

        $('#academia-simulados-add-question').on('click', function(){
            var index = container.find('.academia-simulados-question').length;
            var template = container.find('.academia-simulados-question').first().clone();
            template.attr('data-index', index);
            template.find('textarea, input').val('');
            template.find('input[type="radio"]').prop('checked', false);
            template.find('.question-number').text(index + 1);
            container.append(template);
            renumberQuestions(container);
        });

        container.on('click', '.academia-remove-question', function(){
            if (container.find('.academia-simulados-question').length <= 1) {
                alert(window.academiaSimuladosAdmin.minimumQuestionMessage);
                return;
            }
            $(this).closest('.academia-simulados-question').remove();
            renumberQuestions(container);
        });

        renumberQuestions(container);
    });
})(jQuery);
