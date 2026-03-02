(function () {
  'use strict';

  function initWizard(root) {
    var steps = Array.prototype.slice.call(root.querySelectorAll('.rma-step'));
    if (!steps.length) return;

    var nextButton = root.querySelector('[data-rma-next-step]');
    var prevButton = root.querySelector('[data-rma-prev-step]');
    var resetButton = root.querySelector('[data-rma-reset-step]');
    var status = root.querySelector('[data-rma-wizard-status]');
    var initial = parseInt(root.getAttribute('data-rma-current-step') || '1', 10);
    var current = !isNaN(initial) ? initial : 1;

    function clampCurrent() {
      current = Math.max(1, Math.min(current, steps.length));
    }

    function paint() {
      clampCurrent();

      steps.forEach(function (el, index) {
        var position = index + 1;
        el.classList.remove('is-done', 'is-current');

        if (position < current) el.classList.add('is-done');
        if (position === current) el.classList.add('is-current');
        el.setAttribute('aria-current', position === current ? 'step' : 'false');
      });

      root.setAttribute('data-rma-current-step', String(current));

      if (status) {
        status.textContent = 'Etapa ' + current + ' de ' + steps.length + ' em andamento.';
      }

      if (nextButton) {
        nextButton.disabled = current >= steps.length;
        nextButton.setAttribute('aria-disabled', nextButton.disabled ? 'true' : 'false');
      }

      if (prevButton) {
        prevButton.disabled = current <= 1;
        prevButton.setAttribute('aria-disabled', prevButton.disabled ? 'true' : 'false');
      }
    }

    function goNext() {
      current = Math.min(current + 1, steps.length);
      paint();
    }

    function goPrev() {
      current = Math.max(current - 1, 1);
      paint();
    }

    function reset() {
      current = initial;
      paint();
    }

    if (nextButton) nextButton.addEventListener('click', goNext);
    if (prevButton) prevButton.addEventListener('click', goPrev);
    if (resetButton) resetButton.addEventListener('click', reset);

    root.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        goNext();
      }

      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        goPrev();
      }

      if (event.key === 'Escape') {
        event.preventDefault();
        reset();
      }
    });

    paint();
  }

  var roots = document.querySelectorAll('[data-rma-wizard]');
  Array.prototype.forEach.call(roots, initWizard);
})();
