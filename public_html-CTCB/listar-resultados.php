 <?php
error_reporting(0);
session_start();
include("classes/metodosClass.php");
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto($_SERVER["HTTPS"]);
$tabela = "evento";
$idTB = "evento";
$id = $_REQUEST['key'];
$visualizar = $metodos->visualizar($tabela,$idTB,$id);
list($anoI,$mesI,$diaI) = explode("-",$visualizar[1]->data_inicio);
$dataInicio = $diaI."/".$mesI."/".$anoI;
list($anoT,$mesT,$diaT) = explode("-",$visualizar[1]->data_termino);
$dataTermino = $diaT."/".$mesT."/".$anoT;
$tabelaP = "prova";
$idTBP = "prova";
$idP = $_REQUEST["prova"];
$visualizarP = $metodos->visualizar($tabelaP,$idTBP,$idP);

?>
<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CTCB</title>
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
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="exampleModalLabel">Resultado do evento <?php echo $visualizar[1]->nome; ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">



        <table width="100%" height="100%" border="0" cellpading="0" cellspacing="0">
                		<tr>
                				<td width="270" height="90" valign="middle"><img border="0" src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" width="150"></td>
                				<td width="450" valign="top" align="right" valign="top"><a class="texto14p"><br>Confederação de Tiro e Caça do Brasil<br>https://www.ctcb.org.br - atendimento@ctcb.org.br</a></td>
                		</tr>
                  <tr>
                    <td height="10" colspan="2"></td>
                  </tr>
                  <tr>
                    <td valign="top" colspan="2">
                      <div align="left">
                        <table width="700" border="0" cellpadding="0" cellspacing="0">
                        		<tr>
                        				<td height="20"><a class="texto18x"><b><?php echo $visualizar[1]->nome; ?></b></a><a class="texto14x"><br><?php echo $dataInicio; ?>  a  <?php echo $dataTermino; ?></a></td>
                        		</tr>
                        </table>
                      </div>

                      <div align="left">
                        <table border="0" cellpadding="0" cellspacing="0">
                        		<tr>
                        				<td height="5"></td>
                        		</tr>
                        		<tr>
                        				<td height="20"><span style="font-size: 18px; font-weight: bold"><?php echo $visualizarP[1]->nome; ?></span></td>
                        		</tr>
                        		<tr>
                        				<td>

                                  <table border="0" cellspacing="0" class="table table-bordered">
                                  								<tr bgcolor="#4682B4" height="20">
                                  										<td width="20" height="15" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>CL</b></a></td>
                                  										<td width="35" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>Cód.</b></a></td>
                                  										<td width="50" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>Clube</b></a></td>
                                  										<td width="345" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>Nome</b></a></td>
                                  										<td width="25" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>R1</b></a></td>
                                  										<td width="25" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>R2</b></a></td>
                                  										<td width="25" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>R3</b></a></td>
                                  										<td width="25" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>R4</b></a></td>
                                  										<td width="25" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>R5</b></a></td>
                                  										<td width="25" align="center" style="color: #FFF; text-align: center"><a class="texto9b"><b>TT</b></a></td>
                                  								</tr>
                                      <?php echo $metodos->listarResultadosEventos($visualizar[1]->evento); ?>
                                  </table>
                                </td>
                           </tr>
                           <tr>
                        		  <td height="5"></td>
                        		</tr>
                        		<tr>
                        		  <td height="10" align="right"><a class="texto14y"><b>Nº de Atletas: <?php echo $metodos->contarAtletasEventos($visualizar[1]->evento); ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Inscrições: <?php echo $metodos->contarAtletasEventos($visualizar[1]->evento); ?></b></a></td>
                        		</tr>
                         </table>
                      </div>
                      <table width="100%" border="0" cellpadding="0" cellspacing="0">
        		<tr>
        		  <td height="5"></td>
        		</tr>
        		<!--<tr>
        		  <td height="10" align="right"><a class="texto14y"><b>Nº de Participações: 302&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total de Inscrições:<?php echo $metodos->contarAtletasEventos($visualizar[1]->evento); ?></b></a></td>
        		</tr>-->
        </table>

                    </td>
                  </tr>
                </table>



      <?php //$metodos->listarResultadosEvento($visualizar[1]->evento); ?>
      </div>
      <div class="modal-footer">
        <form method="post" action="<?php echo $caminhoAbsoluto; ?>/excluir-armas/">
          <input type="hidden" name="key" value="<?php echo $_REQUEST['key']; ?>">
          <button type="button" class="btn btn-success" data-dismiss="modal"> Fechar</button>
      </form>
      </div>
    </div>
  </div>
 </body>
</html>
