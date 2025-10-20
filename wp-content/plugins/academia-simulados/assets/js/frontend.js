(function(){
    'use strict';

    function formatString(template, valueOne, valueTwo){
        if (typeof template !== 'string') {
            return valueTwo !== undefined ? valueOne + ' / ' + valueTwo : String(valueOne);
        }
        return template.replace('%1$d', valueOne).replace('%2$d', valueTwo);
    }

    function updateProgress(wrapper){
        var total = parseInt(wrapper.getAttribute('data-total'), 10) || 0;
        var answered = wrapper.querySelectorAll('.academia-simulados-question.is-answered').length;
        var correct = wrapper.querySelectorAll('.academia-simulados-question.is-correct').length;
        var status = wrapper.querySelector('.academia-simulados-progress-status');
        var score = wrapper.querySelector('.academia-simulados-score');
        var bar = wrapper.querySelector('.academia-simulados-progress-bar');
        var fill = wrapper.querySelector('.academia-simulados-progress-fill');
        var completeMessage = wrapper.querySelector('.academia-simulados-complete-message');
        var resetButton = wrapper.querySelector('.academia-simulados-reset');

        if (status) {
            status.textContent = formatString(window.academiaSimulados.progressLabel, answered, total);
        }

        if (score) {
            score.textContent = formatString(window.academiaSimulados.scoreLabel, correct, total);
        }

        if (bar) {
            bar.setAttribute('aria-valuenow', answered);
        }

        if (fill) {
            var percentage = total === 0 ? 0 : Math.round((answered / total) * 100);
            fill.style.width = percentage + '%';
        }

        if (answered === total && total > 0) {
            if (completeMessage) {
                completeMessage.hidden = false;
                var template = window.academiaSimulados.completeLabel || completeMessage.getAttribute('data-template');
                completeMessage.textContent = formatString(template, correct, total);
            }
            if (resetButton) {
                resetButton.hidden = false;
            }
        } else {
            if (completeMessage) {
                completeMessage.hidden = true;
                completeMessage.textContent = '';
            }
            if (resetButton) {
                resetButton.hidden = true;
            }
        }
    }

    function resetQuestion(question){
        question.classList.remove('is-answered', 'is-correct');
        question.querySelectorAll('.academia-simulados-options button').forEach(function(option){
            option.disabled = false;
            option.classList.remove('is-correct', 'is-incorrect');
            option.setAttribute('aria-pressed', 'false');
        });
        var feedback = question.querySelector('.academia-simulados-feedback');
        if (feedback) {
            feedback.textContent = '';
            feedback.setAttribute('aria-live', 'off');
        }
        var hint = question.querySelector('.academia-simulados-hint');
        if (hint) {
            hint.hidden = true;
        }
    }

    function handleOptionClick(event){
        var button = event.currentTarget;
        var optionsContainer = button.closest('.academia-simulados-options');
        var question = optionsContainer.closest('.academia-simulados-question');
        var wrapper = question.closest('.academia-simulados-wrapper');
        var buttons = optionsContainer.querySelectorAll('button');
        var feedback = optionsContainer.parentElement.querySelector('.academia-simulados-feedback');
        var isCorrect = button.getAttribute('data-correct') === '1';
        var correctText = button.getAttribute('data-correct-text') || window.academiaSimulados.defaultCorrectText;
        var incorrectText = button.getAttribute('data-incorrect-text') || window.academiaSimulados.defaultErrorText;

        if (question.classList.contains('is-correct')) {
            return;
        }

        buttons.forEach(function(item){
            item.classList.remove('is-correct', 'is-incorrect');
        });

        if (isCorrect) {
            buttons.forEach(function(item){
                item.disabled = true;
                item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
            });
            question.classList.add('is-answered', 'is-correct');
            button.classList.add('is-correct');
            if (feedback) {
                feedback.textContent = correctText;
                feedback.setAttribute('aria-live', 'polite');
            }
            var reveal = optionsContainer.parentElement.querySelector('.academia-simulados-hint');
            if (reveal) {
                reveal.hidden = false;
            }
            updateProgress(wrapper);
            return;
        }

        button.classList.add('is-incorrect');
        button.disabled = true;
        button.setAttribute('aria-pressed', 'false');
        if (feedback) {
            feedback.textContent = incorrectText;
            feedback.setAttribute('aria-live', 'assertive');
        }
        question.classList.add('is-answered');
        updateProgress(wrapper);
    }

    function attachListeners(wrapper){
        wrapper.querySelectorAll('.academia-simulados-options button').forEach(function(button){
            button.addEventListener('click', handleOptionClick);
        });

        var resetButton = wrapper.querySelector('.academia-simulados-reset');
        if (resetButton) {
            resetButton.addEventListener('click', function(){
                wrapper.querySelectorAll('.academia-simulados-question').forEach(function(question){
                    resetQuestion(question);
                });
                var liveRegion = wrapper.querySelector('.academia-simulados-progress-status');
                if (liveRegion) {
                    liveRegion.textContent = formatString(window.academiaSimulados.progressLabel, 0, parseInt(wrapper.getAttribute('data-total'), 10) || 0);
                }
                var scoreRegion = wrapper.querySelector('.academia-simulados-score');
                if (scoreRegion) {
                    scoreRegion.textContent = formatString(window.academiaSimulados.scoreLabel, 0, parseInt(wrapper.getAttribute('data-total'), 10) || 0);
                }
                var completeMessage = wrapper.querySelector('.academia-simulados-complete-message');
                if (completeMessage) {
                    completeMessage.hidden = true;
                    completeMessage.textContent = '';
                }
                var announcement = wrapper.querySelector('.academia-simulados-feedback-global');
                if (announcement) {
                    announcement.textContent = window.academiaSimulados.resetFeedback || '';
                }
                wrapper.querySelectorAll('.academia-simulados-question').forEach(function(q){
                    q.classList.remove('is-answered', 'is-correct');
                });
                updateProgress(wrapper);
            });
        }
    }

    function filterCards(term, cards, emptyElement){
        var query = term ? term.trim().toLowerCase() : '';
        var visibleCount = 0;

        cards.forEach(function(card){
            var haystack = card.getAttribute('data-search') || '';
            var matches = !query || haystack.indexOf(query) !== -1;
            card.hidden = !matches;
            card.classList.toggle('is-hidden', !matches);
            if (matches) {
                visibleCount++;
            }
        });

        if (emptyElement) {
            emptyElement.hidden = visibleCount !== 0;
        }
    }

    function initialiseDirectory(directory){
        var grid = directory.querySelector('.academia-simulados-card-grid');
        if (!grid) {
            return;
        }

        var cards = Array.prototype.slice.call(grid.querySelectorAll('.academia-simulados-card'));
        var emptyElement = directory.querySelector('.academia-simulados-card-empty');

        if (emptyElement && grid.dataset.emptyMessage) {
            emptyElement.textContent = grid.dataset.emptyMessage;
        }

        var searchInput = directory.querySelector('.academia-simulados-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function(event){
                filterCards(event.target.value, cards, emptyElement);
            });
        }

        filterCards('', cards, emptyElement);
    }

    document.addEventListener('DOMContentLoaded', function(){
        if (!window.academiaSimulados) {
            window.academiaSimulados = {};
        }

        document.querySelectorAll('.academia-simulados-directory').forEach(function(directory){
            initialiseDirectory(directory);
        });

        document.querySelectorAll('.academia-simulados-wrapper').forEach(function(wrapper){
            attachListeners(wrapper);
            updateProgress(wrapper);
        });
    });
})();
