(function(){
  function getColors(){
    var r = getComputedStyle(document.documentElement);
    return [
      r.getPropertyValue('--bx-color1').trim() || '#bb2649',
      r.getPropertyValue('--bx-color2').trim() || '#ffbe98',
      r.getPropertyValue('--bx-color3').trim() || '#6667ab',
      r.getPropertyValue('--bx-color4').trim() || '#259f3c',
      r.getPropertyValue('--bx-color5').trim() || '#b9d938'
    ];
  }
  document.addEventListener('DOMContentLoaded', function(){
    if(!window.bolaoxStats || typeof Chart === 'undefined'){return;}
    var colors = getColors();
    var s = window.bolaoxStats;
    var ctxV = document.getElementById('bx-visits');
    if(ctxV){
      new Chart(ctxV, {type:'line',data:{labels:s.dates,datasets:[{label:'Visitas',data:s.visits,borderColor:colors[0],backgroundColor:'rgba(0,0,0,0)'}]},options:{responsive:true,maintainAspectRatio:false}});
    }
    var ctxU = document.getElementById('bx-users');
    if(ctxU){
      new Chart(ctxU, {type:'line',data:{labels:s.dates,datasets:[{label:'Usuários',data:s.users,borderColor:colors[1],backgroundColor:'rgba(0,0,0,0)'}]},options:{responsive:true,maintainAspectRatio:false}});
    }
    function donut(id,labels,data,idx){
      var el=document.getElementById(id);if(!el)return;new Chart(el,{type:'doughnut',data:{labels:labels,datasets:[{data:data,backgroundColor:data.map(function(_,i){return colors[i%colors.length];})}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
    }
    donut('bx-countries',s.countries.labels,s.countries.data,2);
    donut('bx-platforms',s.platforms.labels,s.platforms.data,3);
    donut('bx-browsers',s.browsers.labels,s.browsers.data,4);
  });
})();
