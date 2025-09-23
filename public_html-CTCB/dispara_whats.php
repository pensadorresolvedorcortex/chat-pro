<?php
// Verifica se o código foi enviado via método POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['codigo']) && isset($_POST['numeroCelular'])) {
    // Captura o código enviado
    $codigo = $_POST['codigo'];
    $telefone = $_POST['numeroCelular'];
    
    // Agora você pode usar a variável $codigo como quiser
    // Por exemplo, você pode usá-la para enviar uma mensagem via WhatsApp
    
    // Exemplo de como enviar uma mensagem via WhatsApp
    enviarMensagemWhatsApp($codigo, $telefone);
    
    // Enviar uma resposta de sucesso ao cliente
    echo "Código recebido com sucesso.";
} else {
    // Se o código não foi enviado via método POST, enviar uma resposta de erro
    http_response_code(400); // Bad Request
    echo "Erro: Código não recebido.";
}

function enviarMensagemWhatsApp($codigo,$telefone) {
    
        //  public function notificaWhats() {
             
                
               $curl = curl_init();
              
              // Seleciona a mensagem a ser enviada
         
              $mensagem1 = "*Bem vindo(a) a CTCB*";
              $mensagem2 = "Seu código de verificação é:";
              $mensagem3 = "*" . $codigo . "*";


              
            curl_setopt_array($curl, array(
             CURLOPT_URL => 'https://v5.chatpro.com.br/chatpro-qupe0hisnd/api/v1/send_message',
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_ENCODING => '',
             CURLOPT_MAXREDIRS => 10,
             CURLOPT_TIMEOUT => 0,
             CURLOPT_SSLVERSION => 6,
             CURLOPT_FOLLOWLOCATION => true,
             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
             CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>'{
              "message": " '. $mensagem1 .' \n\n '.$mensagem2.' \n '.$mensagem3.'  ",
              "number": "'. $telefone .'"
            }',
              CURLOPT_HTTPHEADER => array(
               'Authorization: 537bbe1a7ecee1473012ff290973e73c',
              'Content-Type: application/json'
             ),
            ));
        
            $response = curl_exec($curl);

            if ($response === false) {
                // Ocorreu um erro durante a chamada cURL
                $error_message = 'Erro na chamada cURL: ' . curl_error($curl);
        
                // Registra a mensagem de erro em um arquivo de log
                registrarLog($error_message);
            } else {
             //   $error_message = 'Erro na chamada cURL: ' . curl_error($curl);
                registrarLog($response);
            }
        
            curl_close($curl);
            //echo $response;
                
            
}



function registrarLog($message) {
    // Nome do arquivo de log
    $log_file = 'erro_whatsapp.log';

    // Abre o arquivo de log no modo de escrita, adicionando conteúdo ao final do arquivo
    if ($handle = fopen($log_file, 'a')) {
        // Formata a mensagem de erro com data e hora
        $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

        // Escreve a mensagem de erro no arquivo de log
        fwrite($handle, $log_message);

        // Fecha o arquivo de log
        fclose($handle);
    } else {
        // Se não for possível abrir o arquivo de log, exibe uma mensagem de erro
        echo 'Erro ao abrir o arquivo de log.';
    }
}

?>