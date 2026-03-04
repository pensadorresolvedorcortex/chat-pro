(function () {
  'use strict';

  var buttonPagamento = document.getElementById('btnPagamento');
  if (buttonPagamento) {
    buttonPagamento.addEventListener('click', function () {
      var checkoutBase = '/rma/checkout/';
      var productId = Number(buttonPagamento.getAttribute('data-product-id') || 3407);
      var target = buttonPagamento.getAttribute('data-checkout-url') || (checkoutBase + '?add-to-cart=' + productId);
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
