(function(){
  document.addEventListener('DOMContentLoaded', function(){
    if(!window.bolaoxStats || typeof Chart === 'undefined') return;
    function create(id,type,labels,data){
      var el=document.getElementById(id);
      if(!el) return;
      new Chart(el.getContext('2d'),{
        type:type,
        data:{labels:labels,datasets:[{label:id,data:data,backgroundColor:'#259f3c',borderColor:'#1e734c'}]},
        options:{responsive:true,maintainAspectRatio:false}
      });
    }
    create('bx-visits','line',bolaoxStats.dates,bolaoxStats.visits);
    create('bx-users','line',bolaoxStats.dates,bolaoxStats.users);
    create('bx-countries','bar',bolaoxStats.countries.labels,bolaoxStats.countries.data);
    create('bx-platforms','doughnut',bolaoxStats.platforms.labels,bolaoxStats.platforms.data);
    create('bx-browsers','doughnut',bolaoxStats.browsers.labels,bolaoxStats.browsers.data);
  });
})();
