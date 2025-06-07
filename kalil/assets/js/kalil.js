jQuery(function($){
    let currentTab = 'conversas';
    function filterMessages(){
        if(currentTab === 'conversas'){
            $('#kalil-messages .kalil-msg').show();
        }else{
            $('#kalil-messages .kalil-msg').hide();
            $('#kalil-messages .kalil-msg[data-type="'+currentTab+'"]').show();
        }
    }
    function fetchMessages(){
        $.get(kalilVars.ajaxUrl, {
            action:'kalil_get_messages',
            nonce: kalilVars.nonce,
            patient: kalilPatient.id
        }, function(html){
            $('#kalil-messages').html(html);
            filterMessages();
        });
    }
    fetchMessages();
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
        formData.append('patient', kalilPatient.id);
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

