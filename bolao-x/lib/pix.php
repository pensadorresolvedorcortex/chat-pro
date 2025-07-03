<?php
class Bolaox_Pix {
    public static function emv($id, $val){
        return $id . sprintf('%02d', strlen($val)) . $val;
    }
    public static function crc16($payload){
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
    public static function payload($key,$merchant='BOLAO X',$city='BRASILIA',$txid='***',$amount=''){
        $payload  = self::emv('00','01');
        $payload .= self::emv('26', self::emv('00','BR.GOV.BCB.PIX').self::emv('01',$key));
        $payload .= self::emv('52','0000');
        $payload .= self::emv('53','986');
        if($amount !== ''){
            $payload .= self::emv('54',number_format($amount,2,'.',''));
        }
        $payload .= self::emv('58','BR');
        $payload .= self::emv('59',$merchant);
        $payload .= self::emv('60',$city);
        $payload .= self::emv('62', self::emv('05',$txid));
        $to_crc = $payload.'6304';
        $crc = self::crc16($to_crc.'0000');
        return $to_crc.$crc;
    }
    public static function qr_base64($payload){
        if(!function_exists('imagepng')){return '';}
        require_once __DIR__.'/qrcode.php';
        $qr = QRCode::getMinimumQRCode($payload, QR_ERROR_CORRECT_LEVEL_M);
        $img = $qr->createImage(10,4);
        if(!$img){return '';}
        $size = imagesx($img);
        $scaled = imagescale($img,$size*2,$size*2);
        imagedestroy($img);
        ob_start();
        imagepng($scaled);
        $data = ob_get_clean();
        imagedestroy($scaled);
        return 'data:image/png;base64,'.base64_encode($data);
    }
}
