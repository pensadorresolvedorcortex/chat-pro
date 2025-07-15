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
      new Chart(el.getContext('2d'),{
        type:type,
        data:{labels:labels,datasets:[{label:id,data:data,backgroundColor:color('--bx-secondary','#259f3c'),borderColor:color('--bx-primary','#1e734c'),hoverBackgroundColor:color('--bx-highlight','#b9d938'),fill:type==='line'}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}
      });
    }
    create('bx-visits','line',bolaoxStats.dates,bolaoxStats.visits);
    create('bx-users','line',bolaoxStats.dates,bolaoxStats.users);
    create('bx-countries','bar',bolaoxStats.countries.labels,bolaoxStats.countries.data);
    create('bx-platforms','doughnut',bolaoxStats.platforms.labels,bolaoxStats.platforms.data);
    create('bx-browsers','doughnut',bolaoxStats.browsers.labels,bolaoxStats.browsers.data);
  });
})();
