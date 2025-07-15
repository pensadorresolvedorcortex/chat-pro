(function(){
  document.addEventListener('DOMContentLoaded', function(){
    if(!window.bolaoxStats || typeof Chart === 'undefined') return;
    function color(v,f){
      var c=getComputedStyle(document.documentElement).getPropertyValue(v);
      return c?c.trim():f;
    }
    function create(id,type,labels,data){
      var el=document.getElementById(id);
      if(!el) return;
      var ctx=el.getContext('2d');
      var grad=ctx.createLinearGradient(0,0,0,220);
      grad.addColorStop(0,color('--bx-secondary','#259f3c'));
      grad.addColorStop(1,'rgba(255,255,255,0.1)');
      new Chart(ctx,{
        type:type,
        data:{labels:labels,datasets:[{label:id,data:data,backgroundColor:type==='line'?grad:color('--bx-secondary','#259f3c'),borderColor:color('--bx-primary','#1e734c'),borderWidth:2,hoverBackgroundColor:color('--bx-highlight','#b9d938'),fill:type==='line',tension:0.4}]},
        options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.05)'}},x:{grid:{display:false}}},plugins:{legend:{display:false},tooltip:{backgroundColor:color('--bx-primary','#1e734c'),bodyColor:'#fff'}}}
      });
    }
    create('bx-visits','line',bolaoxStats.dates,bolaoxStats.visits);
    create('bx-users','line',bolaoxStats.dates,bolaoxStats.users);
    create('bx-countries','bar',bolaoxStats.countries.labels,bolaoxStats.countries.data);
    create('bx-platforms','doughnut',bolaoxStats.platforms.labels,bolaoxStats.platforms.data);
    create('bx-browsers','doughnut',bolaoxStats.browsers.labels,bolaoxStats.browsers.data);
  });
})();
