<?php
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
?>
  <?php echo $metodos->listarClubes(); ?>
