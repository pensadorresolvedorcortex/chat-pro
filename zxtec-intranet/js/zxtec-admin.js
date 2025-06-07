(function(){
    function fetchAddress(cep, fields){
        cep = cep.replace(/\D/g,'');
        if(cep.length !== 8) return;
        fetch('https://viacep.com.br/ws/'+cep+'/json/')
            .then(function(r){return r.ok?r.json():null;})
            .then(function(data){
                if(!data || data.erro) return;
                if(fields.street) fields.street.value = data.logradouro || '';
                if(fields.neighborhood) fields.neighborhood.value = data.bairro || '';
                if(fields.city) fields.city.value = data.localidade || '';
                if(fields.state) fields.state.value = data.uf || '';
                if(fields.country && !fields.country.value) fields.country.value = 'Brasil';
            });
    }
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.zxtec-cep').forEach(function(input){
            var fields = {};
            ['street','number','neighborhood','complement','city','state','country'].forEach(function(k){
                var sel = input.dataset[k];
                if(sel){
                    fields[k] = document.querySelector('[name="'+sel+'"]');
                }
            });
            input.addEventListener('blur', function(){
                fetchAddress(input.value, fields);
                setTimeout(function(){
                    var missing = [];
                    if(fields.number && !fields.number.value) missing.push('n\u00famero');
                    if(fields.complement && !fields.complement.value) missing.push('complemento');
                    if(missing.length) alert('Preencha ' + missing.join(' e '));
                }, 800);
            });
        });
    });
})();
