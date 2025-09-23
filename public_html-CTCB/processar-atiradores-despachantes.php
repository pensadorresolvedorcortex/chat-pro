<?php
error_reporting(0);
session_start();
require_once("classes/metodosClass.php");
$metodos = new metodosClass();
$atirador = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING);
echo $metodos->buscarAtiradoresDespachantes($atirador);
?>
