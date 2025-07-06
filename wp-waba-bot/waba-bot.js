jQuery(function($){
    var $input = $('#waba-bot-input');
    var $messages = $('#waba-bot-messages');

    function addMessage(type, text){
        $messages.append('<div class="'+type+'">'+$('<div>').text(text).html()+'</div>');
        $messages.scrollTop($messages[0].scrollHeight);
    }

    function send(){
        var phone = $('#waba-bot-phone').val();
        var msg = $input.val();
        if(!msg) return;
        addMessage('user', msg);
        $input.val('');
        $input.prop('disabled', true);
        $('#waba-bot-send').prop('disabled', true);

        $.post(wabaBotAjax.ajaxurl, {
            action: 'waba_bot_send',
            phone: phone,
            message: msg
        }, function(res){
            if(res.reply){
                addMessage('bot', res.reply);
            }
        }).always(function(){
            $input.prop('disabled', false).focus();
            $('#waba-bot-send').prop('disabled', false);
        });
    }

    $('#waba-bot-send').on('click', send);
    $input.on('keydown', function(e){
        if(e.key === 'Enter'){
            e.preventDefault();
            send();
        }
    });
});
