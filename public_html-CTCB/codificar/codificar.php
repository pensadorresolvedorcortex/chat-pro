<?php
$conexao = mysqli_connect('localhost','gestaope_ctb_adm','0Z#]2!=MdVc&','gestaope_ctcb');
$sql = mysqli_query($conexao,"SELECT atirador, cbte_senha FROM atirador;");
function codificar($key){
    $salt = "$" . md5(strrev($key)) . "%";
    $codifica = crypt($key, $salt);
    $codificar = hash('sha512', $codifica);
    return $codificar;
}
while($jm = mysqli_fetch_object($sql)){
      mysqli_query($conexao,"UPDATE atirador SET cbte_senha = '".codificar($jm->cbte_senha)."' WHERE atirador = '".$jm->atirador."';") or die(mysqli_error($conexao));
}
?>
