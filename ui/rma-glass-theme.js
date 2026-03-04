(function () {
  'use strict';

  var buttonPagamento = document.getElementById('btnPagamento');
  if (buttonPagamento) {
    buttonPagamento.addEventListener('click', function () {
      window.location.href = '/rma/checkout';
    });
  }

  var buttonVoltar = document.getElementById('btnVoltar');
  if (buttonVoltar) {
    buttonVoltar.addEventListener('click', function () {
      window.history.back();
    });
  }

  setInterval(function () {
    fetch('/wp-json/rma/v1/onboarding/status', { credentials: 'same-origin' })
      .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
      .then(function () {
        // hook para atualizar stepper/status quando endpoint existir
      })
      .catch(function () {
        // fallback silencioso em ambientes sem endpoint mockado
      });
  }, 10000);
})();
