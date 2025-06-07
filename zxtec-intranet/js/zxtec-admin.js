(function(){
    function fetchAddress(cep, target){
        cep = cep.replace(/\D/g,'');
        if(cep.length !== 8) return;
        fetch('https://viacep.com.br/ws/'+cep+'/json/')
            .then(function(r){return r.ok?r.json():null;})
            .then(function(data){
                if(!data || data.erro) return;
                var addr = (data.logradouro||'')+', '+(data.bairro||'')+', '+(data.localidade||'')+' - '+(data.uf||'');
                if(target) target.value = addr.trim().replace(/^,\s*/,'');
            });
    }
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.zxtec-cep').forEach(function(input){
            var addressName = input.dataset.address;
            var target = document.querySelector('[name="'+addressName+'"]');
            input.addEventListener('blur', function(){fetchAddress(input.value,target);});
        });
    });
})();
