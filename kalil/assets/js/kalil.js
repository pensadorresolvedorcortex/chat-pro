jQuery(function($){
    let currentTab = 'conversas';
    let currentPatient = kalilPatient.id || '';

    function filterMessages(){
        if(currentTab === 'conversas'){
            $('#kalil-messages .kalil-msg').show();
        }else{
            $('#kalil-messages .kalil-msg').hide();
            $('#kalil-messages .kalil-msg[data-type="'+currentTab+'"]').show();
        }
    }
    function fetchMessages(){
        if(!currentPatient){
            $('#kalil-messages').html('');
            return;
        }
        $.get(kalilVars.ajaxUrl, {
            action:'kalil_get_messages',
            nonce: kalilVars.nonce,
            patient: currentPatient
        }, function(html){
            $('#kalil-messages').html(html);
            filterMessages();
        });
    }
    if(currentPatient){
        fetchMessages();
    }
    $('#kalil-patient-select').on('change', function(){
        currentPatient = $(this).val();
        fetchMessages();
    });
    $('.kalil-menu').on('click','li',function(){
        currentTab = $(this).data('tab');
        $('.kalil-menu li').removeClass('active');
        $(this).addClass('active');
        filterMessages();
    });
    $('#kalil-form').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'kalil_send_message');
        formData.append('nonce', kalilVars.nonce);
        formData.append('patient', currentPatient);
        $.ajax({
            url: kalilVars.ajaxUrl,
            method: 'POST',
            data: formData,
            contentType:false,
            processData:false,
            success:function(){
                $('#kalil-form')[0].reset();
                fetchMessages();
            }
        });
    });
});

