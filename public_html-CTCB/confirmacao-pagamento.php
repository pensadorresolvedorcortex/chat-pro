<?php
$noficationCode = preg_replace('/[^[:alnum:]-]/','',$_POST["notificationCode"]);
$data["token"] = 'SEUTOKEN';
$data["email"] = 'SEUEMAIL';
$data = http_build_query($data);
$url = 'https://ws.pagseguro.uol.com.br/v3/transactions/notifications/'.$noficationCode.'?'.$data;
$curl = curl_init();
curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
curl_setopt($curl,CURLOPT_URL,$url);
$xml = curl_exec($curl);
curl_close($curl);
$xml = simplexml_load_string($xml);
$reference = $xml->reference; // pedido
$status = $xml->status; // status da compra
if($reference && $status){
   /// Atualizar o pedido no banco de dados
}
?>
