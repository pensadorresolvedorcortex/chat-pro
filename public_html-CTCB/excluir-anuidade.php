 <?php
error_reporting(0);
session_start();
include("classes/metodosClass.php");
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto($_SERVER["HTTPS"]);
$tabela = "anuidade_tipo";
$idTB = "anuidade_tipo";
$id = $_REQUEST['key'];
$visualizar = $metodos->visualizar($tabela,$idTB,$id);
if($_POST["Submit"] == "Excluir"){
   $id = $_POST["key"];
   echo $metodos->excluirAnuidade($id);
 }
?>
<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CTCB | Gestão de Controle</title>

    <!-- Bootstrap -->
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <!-- bootstrap-progressbar -->
    <link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <!-- JQVMap -->
    <link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

    <!-- Custom Theme Style -->
    <link href="build/css/custom.min.css" rel="stylesheet">
<script>
$('#myModal').on('hidden.bs.modal',function(){
location.reload();
// window.alert('hidden event fired!');

});
</script>
  </head>
    <body>

  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #DC143C; color: #FFF">
        <h5 class="modal-title" id="exampleModalLabel"><i class="far fa-trash-alt"></i> Excluir anuidade</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      Tem certeza que deseja excluir a anuidade <strong><?php echo $visualizar[1]->nome; ?></strong>?
      </div>
      <div class="modal-footer">
        <form method="post" action="<?php echo $caminhoAbsoluto; ?>/excluir-anuidade/">
          <input type="hidden" name="key" value="<?php echo $_REQUEST['key']; ?>">
          <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-reply" aria-hidden="true"></i> Cancelar</button>
          <button type="submit" name="Submit" value="Excluir" class="btn btn-danger"> <i class="far fa-trash-alt"></i> Excluir</button>
      </form>
      </div>
    </div>
  </div>
 </body>
</html>
