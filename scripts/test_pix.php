<?php
require __DIR__ . '/../bolao-x/lib/pix.php';

$key='12345678900';
$tx='TESTE123';
$payload=Bolaox_Pix::payload($key,'BOLAO X','BRASILIA',$tx);
$img=Bolaox_Pix::qr_base64($payload);
file_put_contents('/tmp/payload.txt',$payload);
if($img){
    file_put_contents('/tmp/sample_pix.png', base64_decode(substr($img,22)));
}
?>
