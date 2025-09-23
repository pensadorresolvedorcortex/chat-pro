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
list($ano,$mes,$dia) = explode("-",$visualizar[1]->data_cadastro);
$dataCadastro = $dia."/".$mes."/".$ano;
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
      <h5 class="modal-title" id="exampleModalLabel" style="font-weight: bold"><i class="fas fa-address-card"></i> GUIA DE TRÁFEGO</h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
  <!-- CABEÇALHO -->
  <tr>
		  <td width="75" height="100" valign="top"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" width="100" border="0" alt="logo do clube"></td>
    <td width="10"></td>
    <td width="565" valign="top">
      <table width="100%" border="0" cellpadding="20" cellspacing="0">
        <tr>
          <td align="right" valign="top">
          <a class="texto14p">Confederação de Tiro e Caça do Brasil</a>
          <br>

          <a class="texto11p">Av. Beira Mar 200 sala 504/2<br>Centro &bull; Rio de Janeiro &bull; RJ &bull; 20030-130<br>CNPJ 12.499.864/0001-89 &bull; Tel.: (21) 2292-0888<br>atendimento@ctcb.org.br &bull; https://www.ctcb.org.br</a></td>
        </tr>
      </table>
    </td>
		</tr>

  <!-- CORPO -->
  <tr>
    <td width="100%" height="40" colspan="3" align="center" valign="middle"><a class="texto14p"><b>DECLARAÇÃO PARA SOLICITAÇÃO DE GUIA DE TRÁFEGO</b></a></td>
  </tr>
  <tr>
    <td valign="top" colspan="3">
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td height="30" colspan="3"></td>
        </tr>
        <tr>
          <td colspan="3">
            <div align="justify"><small style="line-height: 200%; padding: 0, 20px; font-size: 16px">
            A Confederação de Tiro e Caça do Brasil, Certificado de Registro nº 70409/1ªRM, com sede na Av. Beira Mar 200 sala 504/2,
            Centro - CEP 20030-130 - Rio de Janeiro/RJ,
            DECLARA, para fim de comprovação para solicitação de Guia de Tráfego junto ao Exército Brasileiro,
            que <b><?php echo $visualizar[1]->nome; ?></b>, CR nº <b><?php echo $visualizar[1]->cr; ?></b>, está regularmente inscrito nesta entidade
            sob o nº <?php echo $visualizar[1]->codigo ?>, datado de <?php echo $dataCadastro; ?> e que participou de treinamentos/competições que justificam a
            solicitação de Guia de Tráfego pleiteada.
            <p></p>
            Esta Confederação de Tiro e Caça do Brasil dispõe dos registros que comprovam a participação do referido atirador desportivo em treinamento/competições.
            <p></p>
            Esta declaração tem validade de 90 dias.
          </small></div>
          </td>
        </tr>
        <tr>
          <td height="40" colspan="3"></td>
        </tr>
        <tr>
        <td align="center" colspan="3">Rio de Janeiro, <?php echo date("d"); ?> de <?php echo $metodos->mesExtenso(date("m")); ?> de <?php echo date("Y"); ?> </td>
        </tr>
        <tr>
          <td height="30" colspan="3"></td>
        </tr>
        <tr>
        <td align="center" colspan="3"><img src="/images/assinatura_ary.png"></td> 
      </tr>        
        <tr>
          <td align="center" colspan="3">Ary Arsolino Brandão de Oliveira<br>Presidente</td>
        </tr>
        <tr>
          <td height="70" colspan="3"></td>
        </tr>
        <tr>
          <td width="14%" align="right"><small>Este QRCode pode ser lido por qualquer app em seu celular ou tablet e consultará diretamente a página de validação</small></td>
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
          <td width="67%">
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
    <button type="button" class="btn btn-primary" onClick="window.open('<?php echo $caminhoAbsoluto; ?>/guias-imprimir/?key=<?php echo $id; ?>', '_blank', ''); window.close();" ><i class="fas fa-print"></i> Imprimir</button>
</div>
</div>
<script type="text/javascript">
   //$('#botao').click(function(e){
   $(document).ready(function(){
     //e.preventDefault();
     var texto = "<?php echo $caminhoAbsoluto; ?>/validar-declaracoes/?<?php echo $codigo; ?>_<?php echo $visualizar[1]->id_cod_atirador; ?>_num=5";
     var nivel = "L";
     var pixels = "8";
     var tipo = $('input[name="img"]:checked').val();
     $('#img').attr('src', '<?php echo $caminhoAbsoluto; ?>/qr_img0.50j/php/qr_img.php?d='+texto+'&e='+nivel+'&s='+pixels+'&t='+tipo);
   });
 </script>
  </body>
</html>
