<?php
error_reporting(0);
require_once("classes/metodosClass.php");
$metodos = new metodosClass();
$email = filter_input(INPUT_POST,"EmailVerificar",FILTER_VALIDATE_EMAIL);
if($email == false){
   echo "<div class='alert alert-danger'><i class=\"fas fa-exclamation-triangle\"></i> Favor colocar um e-mail válido!</div>";
}else{
   echo $metodos->verificarEmailAtirador($email);
}

?>
