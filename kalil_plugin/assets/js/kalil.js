jQuery(function($){
    let currentTab = 'conversas';
    let currentPatient = window.kalilPatient ? (kalilPatient.id || '') : '';

    function filterMessages(){
        if(currentTab === 'conversas'){
            $('#kalil-messages .kalil-msg').show();
        }else{
            $('#kalil-messages .kalil-msg').hide();
            $('#kalil-messages .kalil-msg[data-type="'+currentTab+'"]').show();
        }
    }

    function adjustForm(){
        var fileInput = $('#kalil-form input[name="file"]');
        var videoUrl = $('#kalil-form input[name="video_url"]');
        var videoDays = $('#kalil-form input[name="video_days"]');
        var info = $('#kalil-info');
        var isAdmin = fileInput.data('admin') === 1 || fileInput.data('admin') === '1';
        if(currentTab === 'video'){
            fileInput.hide();
            if(isAdmin){
                videoUrl.show();
                videoDays.show();
                info.text('Envie um link do YouTube e defina os dias de disponibilidade.');
            }else{
                videoUrl.hide();
                videoDays.hide();
                info.text('Vídeos enviados pelo administrador.');
            }
        }else if(currentTab === 'document'){
            videoUrl.hide();
            videoDays.hide();
            fileInput.show();
            fileInput.attr('accept','application/pdf,image/*');
            info.text('Envie documentos ou veja os recebidos.');
        }else{
            videoUrl.hide();
            videoDays.hide();
            fileInput.show();
            fileInput.attr('accept', isAdmin ? 'video/*,image/*' : 'image/*');
            info.text('');
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
            $('#kalil-messages').scrollTop($('#kalil-messages')[0].scrollHeight);
            adjustForm();
        });
    }
    if(currentPatient){
        fetchMessages();
    }
    adjustForm();
    $('#kalil-patient-select').on('change', function(){
        currentPatient = $(this).val();
        fetchMessages();
    });
    $('.kalil-menu').on('click','li',function(){
        currentTab = $(this).data('tab');
        $('.kalil-menu li').removeClass('active');
        $(this).addClass('active');
        filterMessages();
        adjustForm();
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

    $('.kalil-register-tabs li').on('click', function(){
        var tab = $(this).data('tab');
        $('.kalil-register-tabs li').removeClass('active');
        $(this).addClass('active');
        $('.kalil-register-content').hide();
        $('#kalil-' + tab + '-form').show();
    });
});

