(function(){
    function debounce(fn, delay){
        var timer;return function(){clearTimeout(timer);var args=arguments;timer=setTimeout(function(){fn.apply(null,args);},delay);};
    }

    document.addEventListener('DOMContentLoaded', function(){
        var root = document.querySelector('.ahousi-search');
        if(!root) return;
        var input = root.querySelector('.ahousi-search__input');
        var sugg = root.querySelector('.ahousi-search__suggestions');
        var results = root.querySelector('.ahousi-search__results');
        var selected = -1;

        var fetchSuggest = debounce(function(term){
            if(!term){sugg.innerHTML='';return;}
            fetch('/wp-json/addon-ahousi/v1/suggest?q='+encodeURIComponent(term))
                .then(function(r){return r.json();})
                .then(function(data){
                    var html='';
                    var re=new RegExp('('+term.replace(/[-/\\^$*+?.()|[\]{}]/g,'\\$&')+')','ig');
                    data.items.forEach(function(item){
                        var title=item.title.replace(re,'<mark>$1</mark>');
                        html+='<li role="option" data-url="'+item.url+'">'+title+'</li>';
                    });
                    sugg.innerHTML=html;
                    selected=-1;
                });
        },250);

        input.addEventListener('input', function(){
            fetchSuggest(this.value);
        });

        input.addEventListener('keydown', function(e){
            var items=sugg.querySelectorAll('li');
            if(e.key==='ArrowDown'){selected=Math.min(selected+1,items.length-1);update();e.preventDefault();}
            if(e.key==='ArrowUp'){selected=Math.max(selected-1,-1);update();e.preventDefault();}
            if(e.key==='Enter'){
                if(selected>-1 && items[selected]){window.location=items[selected].dataset.url;} else {performSearch(this.value);} e.preventDefault();
            }
            if(e.key==='Escape'){sugg.innerHTML='';}
            function update(){items.forEach(function(li,i){li.setAttribute('aria-selected',i===selected);});}
        });

        sugg.addEventListener('click', function(e){
            var li=e.target.closest('li');
            if(li){window.location=li.dataset.url;}
        });

        document.addEventListener('click', function(e){
            if(!root.contains(e.target)) sugg.innerHTML='';
        });

        function performSearch(term){
            fetch('/wp-json/addon-ahousi/v1/query?q='+encodeURIComponent(term))
                .then(function(r){return r.json();})
                .then(function(data){
                    var html='';
                    data.items.forEach(function(item){
                        var re=new RegExp('('+term.replace(/[-/\\^$*+?.()|[\]{}]/g,'\\$&')+')','ig');
                        var title=item.title.replace(re,'<mark>$1</mark>');
                        var snippet=item.snippet.replace(re,'<mark>$1</mark>');
                        html+='<div class="ahousi-search__results-item">'+
                            '<a href="'+item.url+'">'+title+'</a><p>'+snippet+'</p></div>';
                    });
                    results.innerHTML=html;
                });
        }
    });
})();
