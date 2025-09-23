<?php
error_reporting(0);
session_start();
include("classes/metodosClass.php");
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto($_SERVER["HTTPS"]);
$idCategoria = $_REQUEST['id_categoria'];
echo $metodos->listarSubCategoriaCertificado($idCategoria);
?>
