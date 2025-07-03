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
    var cd = document.querySelector('.bolaox-countdown');
    if(cd){
      var end = parseInt(cd.getAttribute('data-end'),10)*1000;
      var expired = cd.getAttribute('data-expired') || 'Encerrado';
      function tick(){
        var diff = Math.max(0, Math.floor((end - Date.now())/1000));
        var h = Math.floor(diff/3600).toString().padStart(2,'0');
        var m = Math.floor((diff%3600)/60).toString().padStart(2,'0');
        var s = (diff%60).toString().padStart(2,'0');
        cd.textContent = h+':'+m+':'+s;
        if(diff>0){
          requestAnimationFrame(tick);
        }else{
          cd.textContent = expired;
        }
      }
      tick();
    }
    document.querySelectorAll('.bolaox-numbers').forEach(function(container){
      var hidden = container.querySelector('input[type="hidden"]');
      container.querySelectorAll('.bolaox-number').forEach(function(btn){
        btn.addEventListener('click', function(){
          if(btn.classList.contains('selected')){
            btn.classList.remove('selected');
          } else {
            if(container.querySelectorAll('.bolaox-number.selected').length >= 10) return;
            btn.classList.add('selected');
          }
          var arr = [];
          container.querySelectorAll('.bolaox-number.selected').forEach(function(el){
            arr.push(el.textContent);
          });
          hidden.value = arr.join(',');
        });
      });
    });

    document.querySelectorAll('.bolaox-phone').forEach(function(inp){
      inp.addEventListener('input', function(){
        var v = inp.value.replace(/\D/g,'').slice(0,11);
        var f = v;
        if(v.length > 10){
          f = '('+v.slice(0,2)+') '+v.slice(2,7)+'-'+v.slice(7,11);
        }else if(v.length > 5){
          f = '('+v.slice(0,2)+') '+v.slice(2,6)+'-'+v.slice(6);
        }else if(v.length > 2){
          f = '('+v.slice(0,2)+') '+v.slice(2);
        }
        inp.value = f;
      });
    });

    document.querySelectorAll('.bolaox-tabs').forEach(function(tabs){
      var items = tabs.querySelectorAll('li');
      var contents = tabs.parentElement.querySelectorAll('.bolaox-tab-content');
      items.forEach(function(li,idx){
        li.addEventListener('click', function(){
          items.forEach(function(o){o.classList.remove('active');});
          contents.forEach(function(o){o.classList.remove('active');});
          li.classList.add('active');
          if(contents[idx]) contents[idx].classList.add('active');
        });
      });
    });

    var success = document.querySelector('.bolaox-login-success, .bolaox-register-success');
    if(success){
      setTimeout(function(){
        window.location.href = '/participe';
      }, 1500);
    }

    document.querySelectorAll('.bolaox-card[data-target]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var target = btn.getAttribute('data-target');
        document.querySelectorAll('.bolaox-section').forEach(function(sec){
          sec.style.display = sec.id === target ? 'block' : 'none';
        });
      });
    });
  });
})();
