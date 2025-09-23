<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if($_SESSION["Logado"] == false){
  echo "<script>window.location.href='".$caminhoAbsoluto."/';</script>";
  exit();
}
$tabela = "atirador";
$idTabela = "atirador";
$idBusca = $_SESSION["IdUsuario"];
$visualizar = $metodos->visualizar($tabela, $idTabela, $idBusca);

if(!isset($_REQUEST["key"])){
  $key = null;
  $codigo = $metodos->gerarCodigoAutenticacao($key = null, $tipo = '5');
}else{
  $id = $_REQUEST["key"];
  $codigo = $metodos->gerarCodigoAutenticacao($key = $id, $tipo = '5');
}

$evento = $_REQUEST["evento"];
$prova = $_POST["prova"];
$tabelaA = "evento_atirador";
$idTabelaA = "evento_atirador";
$idBuscaA = $evento;
$visualizarA = $metodos->visualizar($tabelaA, $idTabelaA, $idBuscaA);

$tabelaE = "evento";
$idTabelaE = "evento";
$idBuscaE = $evento;
$visualizarE = $metodos->visualizar($tabelaE, $idTabelaE, $idBuscaE);

$tabelaEP = "evento_prova";
$idTabelaEP = "evento_prova";
$idBuscaEP = $prova;
$visualizarEP = $metodos->visualizar($tabelaEP, $idTabelaEP, $idBuscaEP);

$tabelaP = "prova";
$idTabelaP = "prova";
$idBuscaP = $visualizarEP[1]->prova;
$visualizarP = $metodos->visualizar($tabelaP, $idTabelaP, $idBuscaP);
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
    <link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/sticky-footer/">
    <link href="<?php echo $caminhoAbsoluto; ?>/css/bootstrap.css" rel="stylesheet">
    <link href="<?php echo $caminhoAbsoluto; ?>/css/style.css" rel="stylesheet">
    <style media="screen">
      .texto11p{
         font-size: 12px;
      }
    </style>
      <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.min.js"></script>
      <script>
      /*
      $("#modalImprimir").on('hidden.bs.modal',function(){
        alert("aqui");
        location.reload();
      });
      */
      $('.fechar').on('click', function(){
        location.reload();
      });
      </script>
  </head>
  <body>
    <div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title" id="exampleModalLabel" style="font-weight: bold"><i class="fas fa-address-card"></i> CERTIFICADO</h5>
      <button type="button" class="close fechar" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <table width="100%" height="931" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="135" colspan="2"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" width="100" border="0" alt="logo clube"></td>
    <td width="516" align="center"><a class="texto30x">CERTIFICADO DE PARTICIPAÇÃO</a><br><a class="texto18x">Confederação de Tiro e Caça do Brasil</a></td>
		</tr>
  <tr>
		  <td width="100" height="795" bgcolor="#068a50"></td>
		  <td width="35"></td>
		  <td valign="top">
				  <table width="100%" border="0" cellpadding="0" cellspacing="0">
               <tr>
								  <td colspan="3" height="30"></td>
								</tr>
                <tr>
								  <td height="50" align="right" colspan="3">
                      <h2><?php echo $metodos->verPontuacao($_SESSION["IdUsuario"],$visualizarEP[1]->evento,$prova); ?></h2>
                  </td>
								</tr>
                 <tr>
								  <td height="40" colspan="3"></td>
								</tr>
						  <tr>
										<td height="50" colspan="3"><p style="font-weight: bold"><?php echo $visualizarE[1]->nome; ?></p></td>
								</tr>
						  <tr>
										<td height="50" colspan="3"><p style="font-weight: bold"><?php echo $visualizarP[1]->nome; ?></p></td>
								</tr>
						  <tr>
										<td height="50" colspan="3"><p style="font-weight: bold">Resultado: <?php echo $metodos->notaFinal($_SESSION["IdUsuario"],$visualizarEP[1]->evento); ?></p></td>
								</tr>
						  <tr>
										<td height="50" colspan="3"><p style="font-weight: bold">Categoria: Principal</p></td>
								</tr>
						  <tr>
										<td height="50" colspan="3"><p style="font-weight: bold; text-transform: uppercase"><?php echo $visualizar[1]->nome; ?></p></td>
								</tr>
						  <tr>
										<td height="50" colspan="3">
                      <?php list($ano,$mes,$dia) = explode("-",$visualizarE[1]->data_inicio) ?>
                      <p style="font-weight: bold"><?php echo $dia."/".$mes."/".$ano; ?></p>
                    </td>
								</tr>
						  <tr>
										<td colspan="3" height="130"></td>
								</tr>
        <tr>
          <td width="19%" align="right"><small>Este QRCode pode ser lido por qualquer app em seu celular ou tablet e consultará diretamente a página de validação</small></td>
          <td width="19%" align="center">
            <?php
            $aux = $caminhoAbsoluto.'/qr_img0.50j/php/qr_img.php?';
            $aux .= 'd=&';
            $aux .= 'e=H&';
            $aux .= 's=10&';
            $aux .= 't=J';
            ?>
            <img id="img" src="<?php echo $aux; ?>" style="width: 180px" />
            </td>
          <td width="62%">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center"><small><b>Código de Autenticação</b></small></td>
              </tr>
              <tr>
                <td align="center"><p><b><?php echo $codigo; ?></b></p></td>
              </tr>
              <tr>
                <td align="center"><small>emitido em <?php echo date("d/m/Y") ?> às <?php echo date("H:i"); ?></small></td>
              </tr>
              <tr>
                <td height="5"></td>
              </tr>
              <tr>
                <td align="center"><a class="texto11p">Este documento pode ser validado no site https://www.ctcb.org.br</a></td>
              </tr>
            </table>
          </td>
        </tr>
						</table>
				</td>
  </tr>
</table>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default fechar" data-dismiss="modal">Fechar</button>
    <button type="button" class="btn btn-primary" onClick="window.open('<?php echo $caminhoAbsoluto; ?>/certificado-imprimir/?evento=<?php echo $visualizarEP[1]->evento; ?>&prova=<?php echo $prova;  ?>&key=<?php echo $id; ?>', '_blank', ''); window.close();" ><i class="fas fa-print"></i> Imprimir</button>
</div>
</div>
<script type="text/javascript">
   //$('#botao').click(function(e){
   $(document).ready(function(){
     //e.preventDefault();
     var texto = "<?php echo $caminhoAbsoluto; ?>/validar-declaracoes/?<?php echo $codigo; ?>_<?php echo $visualizar[1]->id_cod_atirador; ?>";
     var nivel = "L";
     var pixels = "8";
     var tipo = $('input[name="img"]:checked').val();
     $('#img').attr('src', '<?php echo $caminhoAbsoluto; ?>/qr_img0.50j/php/qr_img.php?d='+texto+'&e='+nivel+'&s='+pixels+'&t='+tipo);
   });
 </script>
  </body>
</html>
