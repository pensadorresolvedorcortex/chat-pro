<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
$login = $_POST["Login"];
$senha = $_POST["Senha"];
$key = $_POST["g-recaptcha-response"];
$captcha = $metodos->recaptcha($key);
if($captcha->success == true){
   echo $metodos->validarUsuarios($login,$senha);
}else{
  $_SESSION["ErroCaptcha"] = time() + 5;
  echo "<script>window.location.href='".$caminhoAbsoluto."/area-associados/';</script>";
}
?>
