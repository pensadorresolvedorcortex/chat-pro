(function(){
  function animateBar(bar){
    var target = parseInt(bar.getAttribute('data-progress'),10) || 0;
    var span = bar.querySelector('span');
    var start = null;
    function step(timestamp){
      if(!start) start = timestamp;
      var progress = Math.min((timestamp - start)/1000,1);
      var value = Math.floor(progress * target);
      span.style.width = value + '%';
      bar.setAttribute('data-progress', value + '%');
      bar.setAttribute('aria-valuenow', value);
      if(progress < 1){
        requestAnimationFrame(step);
      }else{
        span.style.width = target + '%';
        bar.setAttribute('data-progress', target + '%');
        bar.setAttribute('aria-valuenow', target);
      }
    }
    requestAnimationFrame(step);
  }
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.bolaox-progress').forEach(animateBar);
    document.querySelectorAll('.bolaox-copy').forEach(function(btn){
      btn.addEventListener('click', function(){
        var text = btn.getAttribute('data-copy');
        if(navigator.clipboard){
          navigator.clipboard.writeText(text).then(function(){
            btn.textContent = btn.getAttribute('data-copied') || 'Copiado!';
          });
        }
      });
    });
  });
})();
