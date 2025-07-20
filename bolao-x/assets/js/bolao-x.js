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

  document.querySelectorAll('.bolaox-login-tabs').forEach(function(box){
    var active = box.dataset.tab;
    if(active){
      var idx = active === 'register' ? 1 : 0;
      var lis = box.querySelectorAll('.bolaox-tabs li');
      if(lis[idx]) lis[idx].click();
    }
  });

    var success = document.querySelector('.bolaox-login-success, .bolaox-register-success');
    if(success){
      var url = success.getAttribute('data-redirect');
      if(!url && window.bolaoxData){
        url = bolaoxData.form_url || '/';
      }
      setTimeout(function(){
        window.location.href = url;
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
    var contestSelect = document.querySelector('.bolaox-contest-form select');

    function loadMyBets(id){
      if(!mybets) return;
      mybets.innerHTML = '<div class="bolaox-loading">'+(bolaoxData?bolaoxData.loading:'Carregando...')+'</div>';
      var urlStr = mybets.getAttribute('data-mybets-url') || '';
      var urlObj = new URL(urlStr, window.location.origin);
      if(id !== undefined){
        urlObj.searchParams.set('contest', id);
        mybets.setAttribute('data-mybets-url', urlObj.toString());
        if(history.replaceState){
          var params = new URLSearchParams(window.location.search);
          if(id){ params.set('contest', id); } else { params.delete('contest'); }
          history.replaceState(null,'','?'+params.toString());
        }
      }
      if(urlObj && window.bolaoxData){
        fetch(urlObj.toString(), {
          headers:{'X-WP-Nonce': bolaoxData.nonce},
          credentials:'include'
        }).then(function(r){
          if(r.status===401){
            if(bolaoxData.login_url){
              window.location.href = bolaoxData.login_url+'?redirect_to='+encodeURIComponent(window.location.href);
            }
            return Promise.reject();
          }
          if(!r.ok){ throw new Error('HTTP '+r.status); }
          return r.json();
        }).then(function(data){
          var html = data.html || data;
          mybets.innerHTML = html;
          mybets.querySelectorAll('.bolaox-progress').forEach(animateBar);
        }).catch(function(){
          var msg = bolaoxData ? bolaoxData.load_error : 'Erro ao carregar apostas.';
          mybets.innerHTML = '<p class="bolaox-error">'+msg+'</p>';
        });
      }
    }

    // The table is already rendered server side. Only fetch
    // new results when the contest filter changes to avoid
    // overwriting the initial data if the REST request fails.

    if(contestSelect){
      contestSelect.addEventListener('change', function(){
        loadMyBets(this.value);
      });
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

    var cart = [];
    var cartBox = document.querySelector('.bolaox-cart');
    var priceEl = document.querySelector('.bolaox-price');
    var unitPrice = priceEl ? parseFloat(priceEl.getAttribute('data-price')) : 0;
    function updatePixBtn(){
      document.querySelectorAll('.bolaox-pix-btn').forEach(function(b){
        b.style.display = cart.length ? 'block' : 'none';
      });
    }

    function updateAddLabel(){
      document.querySelectorAll('.bolaox-add-bet').forEach(function(b){
        var init = b.dataset.labelInit || 'Adicionar Jogo';
        var more = b.dataset.labelMore || 'Adicionar mais um Jogo';
        b.textContent = cart.length ? more : init;
      });
    }

    updateAddLabel();
    updatePixBtn();

    function updatePrice(){
      if(!priceEl) return;
      var total = (unitPrice * cart.length).toFixed(2).replace('.',',');
      priceEl.textContent = 'Valor total: R$ ' + total;
    }

    document.querySelectorAll('.bolaox-add-bet').forEach(function(btn){
      btn.addEventListener('click', function(){
        var container = document.querySelector('.bolaox-numbers');
        var hidden = container.querySelector('input[type="hidden"]');
        if(!hidden.value || hidden.value.split(',').length !== 10) return;
        cart.push(hidden.value);
        var item = document.createElement('div');
        item.className = 'bolaox-cart-item';
        var span = document.createElement('div');
        span.className = 'bolaox-numlist';
        hidden.value.split(',').forEach(function(n){
          var s = document.createElement('span');
          s.className = 'bolaox-number drawn';
          s.textContent = n;
          span.appendChild(s);
        });
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'bolaox-remove-bet button';
        remove.textContent = 'Remover';
        remove.addEventListener('click', function(){
          var idx = Array.prototype.indexOf.call(cartBox.children, item);
          if(idx>=0){ cart.splice(idx,1); }
          item.remove();
          updatePrice();
          updateAddLabel();
        });
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'bolaox_numbers[]';
        input.value = hidden.value;
        item.appendChild(span);
        item.appendChild(remove);
        item.appendChild(input);
        cartBox.appendChild(item);
        container.querySelectorAll('.bolaox-number.selected').forEach(function(el){el.classList.remove('selected');});
        hidden.value='';
        updatePrice();
        updateAddLabel();
        updatePixBtn();
      });
    });

    document.querySelectorAll('.bolaox-pix-btn').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var qty = cart.length;
        if(qty === 0) return;
        fetch('/wp-json/bolao-x/v1/create-payment',{
          method:'POST',
          headers:{'Content-Type':'application/json','X-WP-Nonce': bolaoxData ? bolaoxData.nonce : ''},
          body: JSON.stringify({qty: qty})
        }).then(function(r){ return r.json(); }).then(function(data){
          var modal = document.querySelector(btn.getAttribute('data-target'));
          if(!modal) return;
          var img = modal.querySelector('img');
          var code = modal.querySelector('#bolaox-pix-code');
          if(img){
            if(data.qr_code_base64){
              img.src = 'data:image/png;base64,'+data.qr_code_base64;
            }else{
              img.src = 'https://chart.googleapis.com/chart?chs=500x500&cht=qr&chl='+encodeURIComponent(data.qr_code);
            }
          }
          if(code){ code.value = data.qr_code; }
          var pid = document.querySelector('input[name="bolaox_payment_id"]');
          if(pid) pid.value = data.id;
          modal.classList.add('active');
        });
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
