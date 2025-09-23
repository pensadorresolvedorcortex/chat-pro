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
  $codigo = $metodos->gerarCodigoAutenticacao($key = null,$tipo = '6');
}else{
  $id = $_REQUEST["key"];
  $tabelaC = "atirador";
  $idTabelaC = "atirador";
  $idBuscaC = $_SESSION["IdUsuario"];
  $visualizarC = $metodos->visualizar($tabelaC, $idTabelaC, $idBuscaC);
  if($visualizarC[0] > 0){
     $codigo = $metodos->gerarCodigoAutenticacao($key = $id, $tipo = '6');
  }else{
    echo "<script>window.location.href='".$caminhoAbsoluto."/guia-trafego/'</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>atividades-ctcb</title>
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
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
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
  <tr><td style="height: 30px"></td></tr>
<!-- CORPO -->
<tr>
  <td width="100%" height="40" colspan="3" align="center" valign="middle" style="font-size: 18px; font-weight: bold">DECLARAÇÃO DE ATIVIDADE NA CONFEDERAÇÃO</td>
</tr>
<tr>
  <td valign="top" colspan="3">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td height="40" colspan="3"></td>
      </tr>
      <tr>
        <td colspan="3">
          <div align="justify"><small style="line-height: 200%; padding: 0, 20px; font-size: 23px">
          A Confederação de Tiro e Caça do Brasil, Certificado de Registro nº 70409/1ªRM, com sede na Av. Beira Mar 200 sala 504/2,
          Centro - CEP 20030-130 - Rio de Janeiro/RJ,
          DECLARA, que <b><?php echo $visualizar[1]->nome; ?></b>, CPF nº <b><?php echo $visualizar[1]->cpf; ?></b>, está regularmente inscrito nesta entidade
          sob o nº <?php echo $visualizar[1]->codigo; ?>, datado de <?php echo $dataCadastro; ?> e que participou de treinamentos/competições listados abaixo.
          <p></p>
          Esta declaração tem validade de 90 dias.
        </small></div>
        </td>
      </tr>
      <tr>
        <td height="20" colspan="3"></td>
      </tr>
      <tr>
        <td colspan="3">
          <?php echo $metodos->eventoAtirador($_SESSION["IdUsuario"]); ?>
        </td>
      </tr>
      <tr>
        <td height="30" colspan="3"></td>
      </tr>
      <tr>
      <td align="center" colspan="3"><small>Rio de Janeiro, <?php echo date("d"); ?> de <?php echo $metodos->mesExtenso(date("m")); ?> de <?php echo date("Y"); ?> </small></td>
      </tr>
      <tr>
        <td height="50" colspan="3"></td>
      </tr>
      <tr>
        <td align="center" colspan="3"><img src="/images/assinatura_ary.png"></td> 
      </tr>
      <tr>
        <td align="center" colspan="3"><p>Ary Arsolino Brandão de Oliveira<br>Presidente</p></td>
      </tr>
      <tr>
        <td height="50" colspan="3"></td>
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
              <td align="center"><small>Este documento pode ser validado no site https://www.ctcb.org.br</small></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </td>
</tr>
</table>
        <script type="text/javascript">
           $(document).ready(function(){
             //e.preventDefault();
             var texto = "<?php echo $caminhoAbsoluto; ?>/validar-declaracoes/?<?php echo $codigo; ?>_<?php echo $visualizar[1]->id_cod_atirador; ?>_num=6";
             var nivel = "L";
             var pixels = "8";
             var tipo = $('input[name="img"]:checked').val();
             $('#img').attr('src', '<?php echo $caminhoAbsoluto; ?>/qr_img0.50j/php/qr_img.php?d='+texto+'&e='+nivel+'&s='+pixels+'&t='+tipo);
           });
         </script>
        <script>
        setTimeout(function () {
           window.print(); window.close();
         }, 500);
        </script>
  </body>
</html>
