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
  $codigo = $metodos->gerarCodigoAutenticacao($key = null,$tipo = '4');
}else{
  $id = $_REQUEST["key"];
  $codigo = $metodos->gerarCodigoAutenticacao($key = $id, $tipo = '4');
}
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
<head>
  <meta charset="utf-8">
  <title>modalidade-ctcb</title>
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

<!-- TABELA DE TESTE -->

  <table style="border-collapse: collapse; width: 100%;" border="0">
<tbody>
<tr>
<td style="width: 27.3674%;"><img src="https://ctcb.org.br/images/logo.png" alt="Logo CTCB" /></td>
<td style="width: 14.8674%;">&nbsp;</td>
<td style="width: 57.7651%; text-align: right;"><a class="texto14p">Confedera&ccedil;&atilde;o de Tiro e Ca&ccedil;a do Brasil</a><br /><a class="texto11p">Av. Beira Mar 200 sala 504/2<br />Centro &bull; Rio de Janeiro &bull; RJ &bull; 20030-130<br />CNPJ 12.499.864/0001-89 &bull; Tel.: (21) 2292-0888<br />atendimento@ctcb.org.br &bull; https://www.ctcb.org.br</a></td>
</tr>
</tbody>
</table>
         <?php
          list($anoData,$mesData,$diaData) = explode("-",$visualizar[1]->data_cadastro);
          $dataCadastro = $diaData."/".$mesData."/".$anoData;
          ?>
<p style="text-align: center;"><strong>DECLARA&Ccedil;&Atilde;O DE MODALIDADE E PROVA</strong></p>
<div align="justify"><a class="texto14p">A Confedera&ccedil;&atilde;o de Tiro e Ca&ccedil;a do Brasil, Certificado de Registro n&ordm; 70409/1&ordf;RM, com sede na Av. Beira Mar 200 sala 504/2, Centro, CEP 20030-130, Rio de Janeiro/RJ, DECLARA, mediante solicita&ccedil;&atilde;o de&nbsp;<strong><?php echo $visualizar[1]->nome; ?></strong>, CR n&ordm;&nbsp;<strong><?php echo $visualizar[1]->cr; ?></strong>, regularmente inscrito nesta entidade sob o n&ordm; <?php echo $visualizar[1]->codigo ?>, datado de <?php echo $dataCadastro; ?> e para fim de comprova&ccedil;&atilde;o junto ao Ex&eacute;rcito Brasileiro, que promove, realiza ou sedia competi&ccedil;&otilde;es e provas de tiro desportivo, conforme quadro abaixo. </a><strong>*</strong><u>Nas modalidades de 3Gun, Tiro de Precis&atilde;o, Simulado de Ca&ccedil;a e Tiro de Ca&ccedil;a, podem ser usados supressores e/ou freio de boca.</u></div>
<div align="justify">&nbsp;</div>
<div style="text-align: center;" align="justify">
<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
  <tbody>
    <tr>
      <td style="text-align:center; width:333px"><strong>PROVA</strong></td>
      <td style="text-align:center; width:188px"><strong>MODALIDADE</strong></td>
      <td style="text-align:center; width:658px"><strong>TIPO DE ARMA E CALIBRE</strong></td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Trap 50 Calibre Maior</td>
      <td style="text-align:center; width:188px">TRAP AMERICANO</td>
      <td style="text-align:center; width:658px">Espingarda alma lisa calibre 12ga, 16ga e 20ga</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Trap 50 Calibre Menor</td>
      <td style="text-align:center; width:188px">TRAP AMERICANO</td>
      <td style="text-align:center; width:658px">Espingarda alma lisa calibre 24ga, 28ga, 32ga e 36ga</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precision 10m standard shot Mira aberta</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Pistola/Rev&oacute;lver at&eacute; 5 1/2 pol,6.35mm, 7.65mm, 32-20win , 32spl, 38spl.380Acp</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precision 10m standard shot fogo circular mira aberta</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Pistola/Rev&oacute;lver at&eacute; 5 1/2 pol, calibre 17hmr, 22lr/short, .22 WRF</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precision 10m hard shot ( Mira Aberta )</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Pistola/Revolver at&eacute; 5 1/2 pol, calibre 38super, 40sw, 10mm, 357sig, 357mag, 45acp, 9mm, .45 LC , 44-40 win</td>
    </tr>

    <tr>
      <td style="text-align:center; width:333px">Big Shot Precision - Precisão a 15 metros mira aberta Revolver / Pistola</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Pistola/Revolver cano livre, calibre 44mag,.454 cassul, .460 smith, .500 Smith&amp;Wesson e .50 AE (enquadra nesta prova em especial o calibre .357 mag / 38spl com cano de 6 pol. Ou acima)</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precision Rifle / Carbine standard shot 25 metros fogo circular</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Carabina / Rifle 22lr/ short , 17hmr, 22 WRF</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precision Rifle / Carbine standard shot 25mfogo central Fogo Central</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Carabina / Rifle 38spl, 32-20, 44-40, .9mm, .40 SW, 357 mag , .30 carbine .380 ACP, .45 ACP , 32 auto , 38-40 win</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precision 25 metros hard shot Small barrel semi &ndash; automatic disparo sem apoio mira aberta ou reddot se for luneta Maximo 06 aumentos &eacute; permitido uso de compensadores , furos no cano , supressores de som e ou luz , medida m&aacute;xima de comprimento 36mmx177mm</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Carabina / fuzil com cano de at&eacute; 20 polegadas fuzil Mira aberta , reddot e ou luneta no Maximo 06 aumentos calibres de .30 e abaixo 308, 223, 243, .30-06 win, .556x45, .7,62x51, .7,62x39 ( permitido compensadores e supressores medida m&aacute;xima 36mmx177mm medidos a partir do final do raiamento do cano )</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precision 50 metros hard shot disparo sem apoio mira aberta reddot ou luneta 06 aumentos Maximo tiro em p&eacute;</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Carabina fuzil calibre restrito com luneta ou aparelho &oacute;ptico de ate 6 aumentos 308, 300win mag, 222, 223, 243, 6mm br, 7x57mm, 7mm mag , 22.250 rem , 708 rem .30-06 win .44mag .454 cassul .30-30 win.375 HH, 45-70 Win. .556x45, .7,62x51, .7,62x39 SEM APOIO FRONTAL &ndash; tiro em p&eacute;</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">ALVO DE 06 CENTROS; SNIPER CIRCULAR FIRE 100 metros hard shot com luneta ou aparelho &oacute;ptico disparo com apoio</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Carabina com mira &oacute;ptica livre, disparo deitado, sentado ou em p&eacute; somente com apoio frontal calibres permitidos fogo circular. 17HMR, .22 LR/Short e .22 MAG / WRF - dispara-se 04 tiros em cada um dos 05 centros totalizando 20 disparos &ndash; ficando o 01 centro para ensaio livre</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precision 100 metros hard shot com luneta livre ou aparelho &oacute;ptico disparo com apoio FRONTAL comprimento do cano livre, permitido uso de compensadores , furos no cano , supressores de som e ou luz , medida m&aacute;xima de comprimento 36mmx177mm</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Carabina fuzil com aparelho &oacute;ptico livre tiro deitado, sentado ou em p&eacute; com apoio frontal comprimento do cano livre; 308, 300win mag, 222, 223, 243, 22-250 , 6mm, 7x57mm, 708 rem .30-06 win .44mag .454 cassul .30-30 win .375 HH .556x45, .7,62x51, .7,62x39 - dispara-se 02 tiros em cada um dos 05 centros totalizando 10 disparos &ndash; ficando o 01 centro para ensaio livre ( permitido compensadores e supressores medida m&aacute;xima 36mmx177mm medidos a partir do final do raiamento do cano )</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Precis&atilde;o Espingarda 25 Metros Balote &uacute;nico. Mira aberta</td>
      <td style="text-align:center; width:188px">TIRO DE PRECIS&Atilde;O</td>
      <td style="text-align:center; width:658px">Espingarda alma lisa calibre 12ga, 16ga, 20ga, 28 GA, 24 GA, 36 GA sem apoio frontal tiro em p&eacute;</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Tiro R&aacute;pido 10 Metros</td>
      <td style="text-align:center; width:188px">TIRO R&Aacute;PIDO</td>
      <td style="text-align:center; width:658px">Revolver / Pistola calibres .38spl, .357mag (canos de at&eacute; 4 polegadas) .9mm .40sw .45acp (canos de at&eacute; 5 polegadas)</td>
    </tr>
    <tr>
      <td style="text-align:center; width:333px">Tiro de Defesa 10m</td>
      <td style="text-align:center; width:188px">TIRO DE DEFESA</td>
      <td style="text-align:center; width:658px">Revolver / Pistola calibres .38spl, .357mag (canos de at&eacute; 4 polegadas) .9mm .40sw .45acp (canos de at&eacute; 5 polegadas)</td>
    </tr>
  </tbody>
</table>

<p>&nbsp;</p>

<p>&nbsp;</p>
</div>

<!--FIM DA TABELA DE TESTE-->

<table>
              <tr>
                <td height="20" colspan="3"></td>
              </tr>
              <tr>
                <td colspan="3"><small>Esta declaração tem validade de 90 dias.</small></td>
              </tr>
              <tr>
                <td height="50" colspan="3"></td>
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
                <td align="center" colspan="3"><small>Ary Arsolino Brandão de Oliveira<br>Presidente</small></td>
              </tr>
              <tr>
                <td height="90" colspan="3"></td>
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
                      <td align="center"><a class="texto11p"><b>Código de Autenticação</b></a></td>
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
      <script type="text/javascript">
       $(document).ready(function(){
             //e.preventDefault();
         var texto = "<?php echo $caminhoAbsoluto; ?>/validar-declaracoes/?<?php echo $codigo; ?>_<?php echo $visualizar[1]->id_cod_atirador; ?>_num=4";
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
