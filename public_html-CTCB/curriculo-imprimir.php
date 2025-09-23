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
  $codigo = $metodos->gerarCodigoAutenticacao($key = null, $tipo = '2');
}else{
  $id = $_REQUEST["key"];
  $codigo = $metodos->gerarCodigoAutenticacao($key = $id, $tipo = '2');
}
list($anoI,$mesI,$diaI) = explode("-",$_REQUEST["datainicio"]);
$diaInicio = $diaI."/".$mesI."/".$anoI;

list($anoF,$mesF,$diaF) = explode("-",$_REQUEST["datafinal"]);
$diaFinal = $diaF."/".$mesF."/".$anoF;
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>habitualidade-ctcb</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
    <link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/sticky-footer/">
    <link href="<?php echo $caminhoAbsoluto; ?>/css/bootstrap.css" rel="stylesheet">
    <link href="<?php echo $caminhoAbsoluto; ?>/css/style.css" rel="stylesheet">
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
    <table width="100%" height="1022" border="0" cellpadding="0" cellspacing="0">
<!-- CABEÇALHO -->
<tr>
    <td width="75" height="100" valign="top"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" width="100" border="0" alt="logo do clube"></td>
  <td width="10"></td>
  <td width="565" valign="top">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td align="right" valign="top">
        <p>Confederação de Tiro e Caça do Brasil</p>
        <br>
        <small>Av. Beira Mar 200 sala 504/2<br>Centro &bull; Rio de Janeiro &bull; RJ &bull; 20030-130<br>CNPJ 12.499.864/0001-89 &bull; Tel.: (21) 2292-0888<br>atendimento@ctcb.org.br &bull; https://www.ctcb.org.br</small></td>
      </tr>
    </table>
  </td>
  </tr>

<!-- CORPO -->
<tr>
  <td width="100%" colspan="3" align="center" valign="middle"><a class="texto14p"><b>CURRICULUM ESPORTIVO</b></a></td>
</tr>
<tr>
  <td valign="top" colspan="3">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="3" align="center"><p style="text-transform: uppercase"><?php echo $visualizar[1]->nome; ?></p></td>
      </tr>
      <tr>
        <td height="10" colspan="3"></td>
      </tr>
      <tr>
        <td colspan="3">
          <table width="100%" border="0" cellpadding="3" cellspacing="0" class="bordasimples">

            <tr>
              <td height="10" colspan="3" style="border: 0px;"></td>
            </tr>
            <tr>
              <td colspan="3" style="border: 0px;">
                <p><?php echo $diaInicio; ?> a <?php echo $diaFinal; ?></p>
                <br>
                <a class="texto14p">Campeonato Brasileiro 2018 CTCB - Novembro</a></td>
            </tr>
            <tr>
              <td width="65%" align="center" style="border: 1px #999 solid;"><a class="texto11p"><b>Prova</b></a></td>
              <td width="30%" align="center" style="border: 1px #999 solid;"><a class="texto11p"><b>Categoria</b></a></td>
              <td width="5%" align="center" style="border: 1px #999 solid;"><a class="texto11p"><b>Rank</b></a></td>
            </tr>
            <?php echo $metodos->curriculo($_SESSION["IdUsuario"],$diaInicio,$diaFinal); ?>
          </table>
        </td>
      </tr>
      <tr>
        <td height="20" colspan="3"></td>
      </tr>
      <tr>
        <td align="center" colspan="3"><p>
                      <?php echo date("d/m/Y") ?> às <?php echo date("H:i"); ?>
                    </p></td>
      </tr>
      <tr>
        <td height="30" colspan="3"></td>
      </tr>
      <tr>
        <td align="center" colspan="3"><img src="/images/assinatura_ary.png"></td> 
      </tr>
      <tr>
        <td align="center" colspan="3"><a class="texto12p">Ary Arsolino Brandão de Oliveira <br>Presidente</a></td>
      </tr>
      <tr>
        <td height="30" colspan="3"></td>
      </tr>
      <tr>
        <td width="65%"></td>
        <td width="20%" align="right"><small>Este QRCode pode ser lido por qualquer app em seu celular ou tablet e consultará diretamente a página deste atleta</small></td>
        <td width="15%" align="right">
          <?php
          $aux = $caminhoAbsoluto.'/qr_img0.50j/php/qr_img.php?';
          $aux .= 'd=&';
          $aux .= 'e=H&';
          $aux .= 's=10&';
          $aux .= 't=J';
          ?>
          <img id="img" src="<?php echo $aux; ?>" style="width: 180px" />
          </td>
      </tr>
    </table>
  </td>
</tr>
</table>
<script type="text/javascript">
   $(document).ready(function(){
     //e.preventDefault();
     var texto = "https://192.168.0.13/Projetos/CTCB/site/validar-declaracoes/?<?php echo $codigo; ?>_<?php echo $visualizar[1]->id_cod_atirador; ?>";
     var nivel = "L";
     var pixels = "8";
     var tipo = $('input[name="img"]:checked').val();
     $('#img').attr('src', '<?php echo $caminhoAbsoluto; ?>/qr_img0.50j/php/qr_img.php?d='+texto+'&e='+nivel+'&s='+pixels+'&t='+tipo);
   });
 </script>
<script>
setTimeout(function () {
   window.print(); window.close();
 }, 1000);
</script>
  </body>
</html>
