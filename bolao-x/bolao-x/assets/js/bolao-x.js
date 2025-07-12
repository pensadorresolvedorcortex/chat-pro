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
    if(window.bolaoxData){
      document.querySelectorAll('.bolaox-app').forEach(function(el){
        if(bolaoxData.logged_in){
          el.classList.add('bx-logged-in');
          el.classList.remove('bx-logged-out');
        }else{
          el.classList.add('bx-logged-out');
          el.classList.remove('bx-logged-in');
        }
        el.setAttribute('data-logged-in', bolaoxData.logged_in ? '1' : '0');
      });
    }
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
        window.location.href = '/participe?bx_sec=mybets';
      }, 1500);
    }

    var params = new URLSearchParams(window.location.search);
    var open = params.get('bx_sec');
    var sections = document.querySelectorAll('.bolaox-section');
    if(sections.length){
      var target = open ? 'bx-' + open : sections[0].id;
      sections.forEach(function(sec){
        sec.style.display = sec.id === target ? 'block' : 'none';
      });
    }

    // Load and animate the "Minhas Apostas" table
    var mybets = document.querySelector('#bolaox-my-bets');
    if(mybets){
      var url = mybets.getAttribute('data-mybets-url');
      if(url && window.bolaoxData){
        fetch(url, {
          headers:{'X-WP-Nonce': bolaoxData.nonce},
          credentials:'same-origin'
        }).then(function(r){return r.text();}).then(function(html){
          mybets.innerHTML = html;
          mybets.querySelectorAll('.bolaox-progress').forEach(animateBar);
        }).catch(function(){
          mybets.querySelectorAll('.bolaox-progress').forEach(animateBar);
        });
      }else{
        mybets.querySelectorAll('.bolaox-progress').forEach(animateBar);
      }
    }

    document.querySelectorAll('.bolaox-card[data-target]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var target = btn.getAttribute('data-target');
        document.querySelectorAll('.bolaox-section').forEach(function(sec){
          sec.style.display = sec.id === target ? 'block' : 'none';
        });
      });
    });

    document.querySelectorAll('.bolaox-open-modal').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var target = btn.getAttribute('data-target');
        var modal = document.querySelector(target);
        if(modal) modal.classList.add('active');
      });
    });
    document.querySelectorAll('.bolaox-modal-close').forEach(function(btn){
      btn.addEventListener('click', function(){
        var modal = btn.closest('.bolaox-modal');
        if(modal) modal.classList.remove('active');
      });
    });

    document.querySelectorAll('.bolaox-copy').forEach(function(btn){
      btn.addEventListener('click', function(){
        var target = btn.getAttribute('data-target');
        var input = document.querySelector(target);
        if(input){
          input.select();
          if(window.navigator && navigator.clipboard){
            navigator.clipboard.writeText(input.value).catch(function(){});
          }else{
            document.execCommand('copy');
          }
          btn.textContent = 'Copiado!';
          setTimeout(function(){ btn.textContent = 'Copiar'; }, 1000);
        }
      });
    });

    document.querySelectorAll('.bolaox-validate-creds').forEach(function(btn){
      btn.addEventListener('click', function(){
        var msg = btn.nextElementSibling;
        msg.textContent = '...';
        var modeSel = document.querySelector('select[name="bolaox_mp_mode"]');
        var mode = modeSel ? modeSel.value : 'test';
        fetch('/wp-json/bolao-x/v1/validate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': btn.getAttribute('data-nonce')
          },
          body: JSON.stringify({mode: mode})
        }).then(function(r){
          if(r.ok){ msg.textContent = 'OK'; }
          else{ msg.textContent = 'Inválido'; }
        }).catch(function(){ msg.textContent = 'Erro'; });
      });
    });

    // Fallback for iOS Safari not triggering form submission on styled buttons
    document.querySelectorAll('.bolaox-submit').forEach(function(btn){
      btn.addEventListener('click', function(){
        var form = btn.closest('form');
        if(form){
          if(form.requestSubmit){
            form.requestSubmit(btn);
          } else {
            form.submit();
          }
        }
      });
    });
  });
})();
