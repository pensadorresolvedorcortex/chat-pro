<?php
$host = mysqli_connect('localhost','root','sucesso','ctcb');
function codificar($key) {
    $salt = "$" . md5(strrev($key)) . "%";
    $codifica = crypt($key, $salt);
    $codificar = hash('sha512', $codifica);
    return $codificar;
}
//echo codificar("acesso@ctcb");


$sql = mysqli_query($host,"SELECT * FROM clube");
while($jm = mysqli_fetch_object($sql)){
  //echo codificar($jm->evento)."<br>";
   mysqli_query($host,"UPDATE clube SET senha = '".codificar($jm->senha)."' WHERE clube = '".$jm->clube."';");
   echo "UPDATE clube SET senha = '".codificar($jm->senha)."' WHERE clube = '".$jm->clube."';";
}

?>
