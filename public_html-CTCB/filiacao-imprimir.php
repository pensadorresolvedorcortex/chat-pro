  <?php
  error_reporting(0);
  session_start();
  require_once('classes/metodosClass.php');
  $metodos = new metodosClass();
  $caminhoAbsoluto = $metodos->caminhoAbsoluto();
  if($_SESSION["Logado"] == false)
  {
    echo "<script>window.location.href='".$caminhoAbsoluto."/';</script>";
    exit();
  }
  $tabela = "atirador";
  $idTabela = "atirador";
  $idBusca = $_SESSION["IdUsuario"];
  $visualizar = $metodos->visualizar($tabela, $idTabela, $idBusca);
  if(!isset($_REQUEST["key"]))
  {
    $codigo = $metodos->gerarCodigoAutenticacao($key = null,$tipo = '1');
  }
  else
  {
    $id = $_REQUEST["key"];
    $codigo = $metodos->gerarCodigoAutenticacao($key = $id, $tipo = '1');
  }
  ?>
  <!DOCTYPE html>
  <html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>filiacao-entidade-ctcb</title>
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
                <small>Confederação de Tiro e Caça do Brasil</small>
                <br>
                <small>Confedera&ccedil;&atilde;o de Tiro e Ca&ccedil;a do Brasil</small> <br /><small>Av. Beira Mar 200 sala 504/2<br />Centro &bull; Rio de Janeiro &bull; RJ &bull; 20021-060<br />CNPJ 12.499.864/0001-89 &bull; Tel.: (21) 96577-7223<br />atendimento@ctcb.org.br &bull; https://www.ctcb.org.br</small></td>
              </tr>
            </table>
          </td>
        </tr>

          <!-- CORPO -->
          <tr>
            <td width="100%" height="0" colspan="3" align="center" valign="middle" style="font-size: 18px; font-weight: bold">
                <p><strong>DECLARA&Ccedil;&Atilde;O DE FILIA&Ccedil;&Atilde;O A ENTIDADE DE TIRO</strong></p>
            </td>
          </tr>
              <tr>
              <td colspan="3" valign="top">
                <table style="width: 100%; height: 885px;" border="0" width="100%" cellspacing="0" cellpadding="0">
                  <tbody>
                    <tr style="height: 276px;">
                      <td style="width: 100%; height: 276px;" colspan="3" height="20">
                        <table style="border-collapse: collapse; width: 100%; height: 126px;" border="1">
                          <tbody>
                            <tr style="height: 18px;">
                              <td style="width: 99.9999%; height: 18px; text-align: center;" colspan="3"><span style="text-decoration: underline;"><strong>DADOS DA ENTIDADE</strong></span></td>
                            </tr>
                            <tr style="height: 18px;">
                              <td style="width: 33.3334%; text-align: left; height: 18px;"><strong>NOME DA ENTIDADE:</strong></td>
                              <td style="width: 36.8843%; height: 18px; text-align: center;">CONFEDERA&Ccedil;&Atilde;O DE TIRO E CA&Ccedil;A DO BRASIL</td>
                              <td style="width: 29.7822%; height: 18px;"><strong>CNPJ:</strong> 12.499.864/0001-89</td>
                            </tr>
                            <tr style="height: 18px;">
                              <td style="width: 33.3334%; height: 18px;"><strong>CERTIFICADO DE REGISTRO:</strong></td>
                              <td style="width: 36.8843%; height: 18px; text-align: center;">70409 /1&ordf; RM</td>
                              <td style="width: 29.7822%; height: 18px;"><strong>Data de Emissão:</strong> <br />23/06/2025<br /><strong>Data de Validade:</strong><br />24/06/2027</td>
                            </tr>
                            <tr style="height: 18px;">
                              <td style="width: 33.3334%; height: 18px;"><strong>ENDERE&Ccedil;O:</strong></td>
                              <td style="width: 66.6665%; height: 18px;" colspan="2">Av. Beira Mar, 200 sala 504/2 - Centro - Rio de Janeiro - RJ<br />CEP 20021-060</td>
                            </tr>
                            <tr style="height: 18px;">

                              <td style="width: 33.3334%; height: 18px;"><strong>FILIA&Ccedil;&Atilde;O ENTIDADE DE TIRO:</strong></td>
                              <?php list($anoC,$mesC,$diaC) = explode("-",$visualizar[1]->data_cadastro); ?>
                              <td style="width: 36.8843%; height: 18px;" colspan="2"><strong>N&uacute;mero:</strong>&nbsp; <?php echo $visualizar[1]->codigo; ?> | <strong>Data de Cadastro: </strong> &nbsp; <?php echo $diaC; ?>/<?php echo $mesC; ?>/<?php echo $anoC; ?></td>

                              <tr style="height: 18px;">
                                <td style="width: 99.9999%; text-align: center; height: 0px;" colspan="3"><br /><span style="text-decoration: underline;"><strong>DADOS DO REQUERENTE</strong></span></td>
                              </tr>
                            </tbody>
                          </table>
                          <table style="border-collapse: collapse; width: 100%; height: 108px;" border="1">
                            <tbody>
                              <tr style="height: 18px;">
                                <td style="width: 47.0171%; height: 18px;"><strong>NOME COMPLETO:</strong> <?php echo $visualizar[1]->nome; ?>&nbsp;</td>
                              </tr>
                              <tr style="height: 18px;">
                                <td style="width: 47.0171%; height: 18px;"><strong>CERTIFICADO DE REGISTRO:</strong>&nbsp; <?php echo $visualizar[1]->cr; ?></td>
                              </tr>
                              <tr style="height: 18px;">
                                <td style="width: 47.0171%; height: 18px;"><strong>VALIDADE DO CR: </strong><?php echo $visualizar[1]->cr_validade; ?></td>
                              </tr>
                              <tr style="height: 18px;">
                                <td style="width: 47.0171%; height: 18px;"><strong>CPF/MF: </strong>&nbsp; <?php echo $visualizar[1]->cpf; ?></td>
                              </tr>
                              <tr style="height: 18px;">
                                <td style="width: 47.0171%; height: 18px;"><strong>ENDERE&Ccedil;O:</strong>&nbsp; <?php echo $visualizar[1]->endereco; ?> -
                                 <?php echo $visualizar[1]->bairro; ?> - <?php echo $visualizar[1]->cidade; ?> - <?php echo $visualizar[1]->sigla; ?> CEP: <?php echo $visualizar[1]->cep; ?> </td>
                               </tr>

                             </tbody>
                           </table>
                           <p>&nbsp;</p>
                         </td>
                       </tr>
                       <tr style="height: 140px;">
                        <td style="width: 100%; height: 140px;" colspan="3">
                          <div align="justify">
                            <p style="line-height: 200%; padding: 0, 20px;"><?php list($anoC,$mesC,$diaC) = explode("-",$visualizar[1]->data_cadastro); ?> A <strong>Confedera&ccedil;&atilde;o de 
                            Tiro e Ca&ccedil;a do Brasil</strong>, CNPJ: 12.499.864/0001-89, Certificado de Registro n&ordm; <strong>70409/1&ordf;RM</strong>, 
                            com endere&ccedil;o na Av. Beira Mar 200 sala 504/2, Centro - CEP 20021-060 - Rio de Janeiro/RJ, DECLARA, para fim de comprova&ccedil;&atilde;o de filia&ccedil;&atilde;o junto ao 
                            Ex&eacute;rcito Brasileiro, nos termos do contido no inciso XVII do art. 2&deg; do Decreto 11.615, de 21 de Julho de 2023, e sob as penas da lei, que o cidad&atilde;o <strong><?php echo $visualizar[1]->nome; ?></strong>, 
                            CPF N&ordm; <strong> <?php echo $visualizar[1]->cpf; ?></strong>, pertence aos quadros desta entidade sob o n&deg; de matricula <strong><?php echo $visualizar[1]->codigo; ?></strong>, datado de <strong><?php echo $diaC; ?>/<?php echo $mesC; ?>/<?php echo $anoC; ?></strong> , conforme os dados de filia&ccedil;&atilde;o acima descritos.</p>
                          </div>
                        </td>
                      </tr>
   
                      <tr style="height: 46px;">
                        <td style="width: 100%; height: 46px;" colspan="3">
                          <p>Esta declara&ccedil;&atilde;o tem validade de 90 dias.</p>
                        </td>
                      </tr>
                      <tr style="height: 20px;">
                        <td style="width: 100%; height: 20px;" colspan="3" height="20">&nbsp;</td>
                      </tr>
                      <tr style="height: 46px;">
                        <td style="width: 100%; height: 46px;" colspan="3" align="center">
                          <p>Rio de Janeiro, <?php echo date("d"); ?> de <?php echo $metodos->mesExtenso(date("m")); ?> de <?php echo date("Y"); ?></p>
                        </td>
                      </tr>
                      <tr style="height: 20px;">
                        <td style="width: 100%; height: 20px;" colspan="3" height="20">&nbsp;</td>
                      </tr>
                      <tr><td style="width: 100%; height: 20px;" colspan="3" align="center">______________________________________________</td></tr>
                      <tr style="height: 20px;">
                        <!--<td style="width: 100%; height: 20px;" colspan="3" align="center"><img src="/images/assinatura_ary.png" /></td>-->

                        <td style="width: 100%; height: 20px;" colspan="3" align="center">Confederação de Tiro e Caça pdo Brasil <br>CNPJ 12.499.864/0001-89</td>

                        <tr style="height: 20px;">
                          <td style="width: 100%; height: 20px;" colspan="3" height="20">&nbsp;</td>
                        </tr>
                        <br>
                        <tr><td style="width: 100%; height: 20px;" colspan="3" align="center">______________________________________________</td></tr>
                        <tr style="height: 20px;">
                          <td style="width: 100%; height: 20px;" colspan="3" align="center"><?php echo $visualizar[1]->nome; ?></td>
                        </tr>

                        <tr style="height: 20px;">
                          <td style="width: 100%; height: 20px;" colspan="3" align="center"><?php echo $visualizar[1]->cpf; ?></td>
                        </tr>
                        <tr style="height: 64px;">
                          <!--<p>Ary Arsolino Brand&atilde;o de Oliveira<br />Presidente</p>-->
                        </td>
                      </tr>
                   </table> 
                    <tr>
                      <td height="90" colspan="3"></td>
                    </tr>
                    <tr>
                      <td width="14%" align="right"><small>Este QRCode pode ser lido por qualquer app em seu celular ou tablet e consultará diretamente a página de validação</a></td>
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
                 var texto = "<?php echo $caminhoAbsoluto; ?>/validar-declaracoes/?<?php echo $codigo; ?>_<?php echo $visualizar[1]->id_cod_atirador; ?>_num=1";
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
