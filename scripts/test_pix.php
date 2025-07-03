<?php
require __DIR__ . '/../bolao-x/lib/qrcode.php';
function pix_emv($id,$val){return $id . sprintf('%02d', strlen($val)) . $val;}
function pix_crc16($payload){
    $crc = 0xFFFF;
    for($i=0;$i<strlen($payload);$i++){
        $crc ^= ord($payload[$i]) << 8;
        for($b=0;$b<8;$b++){
            if($crc & 0x8000){
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc <<= 1;
            }
            $crc &= 0xFFFF;
        }
    }
    return sprintf('%04X',$crc);
}
function pix_payload($key,$txid){
    $payload = pix_emv('00','01');
    $payload .= pix_emv('26',pix_emv('00','BR.GOV.BCB.PIX').pix_emv('01',$key));
    $payload .= pix_emv('52','0000');
    $payload .= pix_emv('53','986');
    $payload .= pix_emv('58','BR');
    $payload .= pix_emv('59','BOLAO X');
    $payload .= pix_emv('60','BRASILIA');
    $payload .= pix_emv('62',pix_emv('05',$txid));
    $to_crc = $payload.'6304';
    $crc = pix_crc16($to_crc.'0000');
    return $to_crc.$crc;
}
$key='12345678900';
$tx='TESTE123';
$payload=pix_payload($key,$tx);
$qr=QRCode::getMinimumQRCode($payload, QR_ERROR_CORRECT_LEVEL_H);
$img=$qr->createImage(10,4);
$size=imagesx($img);
$scaled=imagescale($img,$size*2,$size*2);
imagedestroy($img);
imagepng($scaled,'/tmp/sample_pix.png');
imagedestroy($scaled);
file_put_contents('/tmp/payload.txt',$payload);
?>
