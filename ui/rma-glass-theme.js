(function () {
  'use strict';

  var buttonPagamento = document.getElementById('btnPagamento');
  if (buttonPagamento) {
    buttonPagamento.addEventListener('click', function () {
      var target = buttonPagamento.getAttribute('data-checkout-url') || '/rma/checkout/';
      buttonPagamento.disabled = true;
      buttonPagamento.textContent = 'Processando pagamento...';
      window.location.href = target;
    });
  }

  var buttonVoltar = document.getElementById('btnVoltar');
  if (buttonVoltar) {
    buttonVoltar.addEventListener('click', function () {
      window.history.back();
    });
  }

})();
