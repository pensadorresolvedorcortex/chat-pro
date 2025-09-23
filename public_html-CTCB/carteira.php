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
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Confederação de Tiro e Caça do Brasil | CTCB</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
<link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/sticky-footer/">
<link href="<?php echo $caminhoAbsoluto; ?>/css/bootstrap.css" rel="stylesheet">
<link href="<?php echo $caminhoAbsoluto; ?>/css/style.css" rel="stylesheet">
  <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.min.js"></script>
  </head>
  <body>
    <div id="fb-root"></div>
<script>
(function(d, s, id)
{
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/pt_BR/sdk.js#xfbml=1&version=v2.3";
  fjs.parentNode.insertBefore(js, fjs);
}
(document, 'script', 'facebook-jssdk'));
</script>
   <div class="col-md-12 fundo-container">
     <div class="container">
     <div class="menu">
      <div class="row">
        <div class="col-md-4">
         <figure>
          <a href="<?php echo $caminhoAbsoluto; ?>/"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" alt="" class="logo img-fluid"></a>
        </figure>
      </div>
      <div class="col-md-8">
        <div class="row offset-md-8">
          <div class="menu-superior"><a href="#">Principal</a> &nbsp; <a href="#">Contato</a> &nbsp; <a href="#">Localização</a></div>
        </div>
        <div class="row">
        <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="#">&nbsp;</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse mobile-uno" id="navbarNav">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" href="#">Importação</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Caçadas</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Curso de Instrutor</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Assessoria Jurídica</a>
            </li>
          </ul>
        </div>
        </nav>
      </div>
        </div>
        <div class="col-md-12">
        <nav class="navbar navbar-expand-lg navbar-light menu-inferior">
        <div class="collapse navbar-collapse menu-inferior-info mobile-duno" id="navbarNav">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" href="#">Atletas</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Clubes</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Regularmento</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Calendário</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Resultados</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Instrutores</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Notícias</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Fotos</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Vídeos</a>
            </li>
          </ul>
        </div>
        </nav>
        </div>
      </div>
    </div>
   </div>
  </div>
  <div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold"><i class="fas fa-address-card fa-lg"></i> CARTEIRA</h3>
        <h5 style="color: #3e4095; font-weight: bold; text-transform: uppercase; margin-top: 30px; color: #000"><i class="fas fa-user"></i> <?php echo $visualizar[1]->nome; ?></h5>
           <div style="margin-top: 20px; text-align: center">
           <button class="btn btn-success" id="btnImprimir" title="Clique para imprimir sua carteira" data-toggle="modal" data-target=".bd-example-modal-lg"><i class="fas fa-print"></i> Imprimir</button>
         </div>
      </div>
      <div class="modal fade bd-example-modal-lg" id="modalImprimir" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
          <div class="modal-dialog modal-lg">
              <div>
                <div class="modal-content">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="exampleModalLabel" style="font-weight: bold"><i class="fas fa-address-card"></i> CARTEIRA CTCB</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top: 10px">
                        <tr>
                         <td align="center" valign="middle">
                          <table width="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="338" height="218" align="center" style="border: #999 solid 1px;">
                                <table width="100%" height="218" border="0" cellpadding="0" cellspacing="0">
                                  <tr>
                                    <td height="9" align="center" valign="middle" bgcolor="#FFFF99"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" width="250" alt="logo" border="0"></td>
                                  </tr>
                                </table>
                              </td>
                               <td align="center" valign="top" style="border: #999 solid 1px;">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                  <tr>
                                    <td align="center" valign="top" colspan="3">
                                      <small>
                                      <div>Av. Beira Mar 200 sala 504/2</div>
                                      <div>Centro &bull; Rio de Janeiro &bull; RJ</div>
                                      <div>(21) 2292-0888</div>
                                      <div>https://www.ctcb.org.br &bull; atendimento@ctcb.org.br</div>
                                    </small>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td></td>
                                    <td colspan="2">
                                      <div><small><strong>Nome:</strong></small></div>
                                      <div><small><?php echo $visualizar[1]->nome; ?></small></div>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td width="10"></td>
                                    <td width="100"><div><small><strong>Matrícula: </strong></small></div><div><small><?php echo $visualizar[1]->codigo; ?></small></div></td>
                                    <td width="108"><div><small><strong>CPF: </strong></small></div><div><small><?php echo $visualizar[1]->cpf; ?></small></div></td>
                                  </tr>
                                  <tr>
                                    <td width="10"></td>
                                    <?php
                                    list($anoC,$mesC,$diaC) = explode("-",$visualizar[1]->data_cadastro);
                                    $dataCadastro = $diaC."/".$mesC."/".$anoC;
                                    ?>
                                    <td width="100"><div><small><strong>Cadastro: </strong></small></div><div><small><?php echo $dataCadastro; ?></small></div></td>
                                    <?php
                                      $dataValidade = $metodos->validadeAtirador($_SESSION["IdUsuario"]);
                                    ?>
                                    <td width="108"><div><small><strong>Validade: </strong></small></div><div><small><?php echo $dataValidade; ?></small></div></td>
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
                    <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"  onClick="window.open('<?php echo $caminhoAbsoluto; ?>/carteira-imprimir/', '_blank', ''); window.close();" ><i class="fas fa-print"></i> Imprimir</button>
                  </div>
                </div>
              </div>
          </div>
      </div>

      <div class="col-md-4 col-xs-12">
        <!-- Menu Lateral -->
        <?php include("menu-logado.php"); ?>
        <!-- Fim do menu lateral -->
        <div class="row" style="padding: 10px">
          <button class="btn btn-danger" style="width: 100%; font-weight: bold" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/validar-documentos/'"><i class="fas fa-check"></i> Validar Documentos</button>
        </div>
        <div class="row" style="padding: 10px">
          <h4 style="width: 100%; text-align: center" class="textoFace">FACEBOOK</h4>
          <div style="width: 100%; text-align: center">
          <div class="fb-like" data-href="https://www.facebook.com/Confederação-de-Tiro-e-Caça-do-Brasil-1407741389444948" data-send="true" data-layout="button_count" data-width="160" data-show-faces="true"></div><br>
          <a href="https://www.facebook.com/Confederação-de-Tiro-e-Caça-do-Brasil-1407741389444948" target="_blank">acessar a fanpage</a>
        </div>
        </div>
        <div class="row" style="padding: 10px;">
          <div style="width: 100%; text-align: center">
          <a href="#" target="_blank"><img src="https://www.ctcb.org.br/images/banners/compre_sem_intermediario.jpg" width="290" alt="PM Cofres" style="border:1px solid #666;" /></a>
        </div>
        </div>
        <div class="row" style="padding: 10px;">
          <div style="width: 100%; text-align: center">
            <button type="button" class="btn btn-primary btn-lg">SEU ANÚNCIO AQUI</button>
        </div>
        </div>
        <div class="row" style="padding: 10px;">
          <div style="width: 100%; text-align: center">
            <div class="card" style="width: 100%; background-color: #59A22D;">
              <h5 style="color: #FFF; font-weight: bold; text-shadow: 2px -2px #000; margin-top: 10px"><i class="fas fa-newspaper"></i> NEWSLETTER</h5>
              <div class="card-body">
                <div class="form-group">
                  <label for="email">Coloque seu email abaixo:</label>
                  <input type="email" class="form-control" id="email" aria-describedby="email" placeholder="Coloque seu email">
                </div>
                <button type="submit" class="btn btn-success">Cadastrar</button>
              </div>
            </div>
          </div>
        </div>
        <div class="row" style="padding: 10px;">
          <div style="width: 100%; text-align: center">
            <h5 style="font-weight: bold">APOIO A ESPORTE</h5>
            <div class="row">
            <div class="col-md-6">
              <a href="https://www.mildot.com.br" target="_blank"><img src="https://www.ctcb.org.br/images/banners/banner_mildot.jpg" width="138" alt="Mildot Comércio de Material de Segurança" style="border:1px solid #666;" /></a>
            </div>
            <div class="col-md-6">
              <a href="https://www.militaria.com.br" target="_blank"><img src="https://www.ctcb.org.br/images/banners/banner_militaria.jpg" width="138" alt="Militaria - Fabrica de Armas e Munições" style="border:1px solid #666;" /></a>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
     <footer>
      <p style="font-size: 13px">
         Principal  |  Atletas  |  Clubes  |  Instrutores  |  Regulamento  |  Calendário  |  Notícias  |  Fotos  |  Vídeos  |  Importação  |  Contato  |  Localização<br><br>
         <i class="fas fa-phone"></i> (21) 2292-0888<br><br>
         <i class="fas fa-map-marker-alt"></i> Av. Beira Mar 200 sala 504/2 - Centro - Rio de Janeiro - RJ - 20030-130
        </p>
     </footer>
  </div>
  </div>
   <script src="<?php echo $caminhoAbsoluto; ?>/js/bootstrap.min.js"></script>
   <script>
   function mostrarSenha(){
   	var botao = document.getElementById("senha");
   	if(botao.type == "password"){
   		botao.type = "text";
       document.getElementById("ver").className="far fa-eye-slash fa-lg";
       document.getElementById("botaoSenha").title="Esconder senha";
   	}else{
   		botao.type = "password";
       document.getElementById("ver").className="far fa-eye fa-lg";
       document.getElementById("botaoSenha").title="Mostrar senha";
   	}
   }
   </script>
  </body>
</html>
