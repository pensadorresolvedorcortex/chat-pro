<?php
error_reporting(0);
require_once("classes/metodosClass.php");
$metodos = new metodosClass();
$key = $_SERVER["QUERY_STRING"];
echo $metodos->validarConta($key);
