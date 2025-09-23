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
if($_POST["Submit"] == "Alterar"){
  $novaSenha = $_POST["NovaSenha"];
  echo $metodos->alterarSenhaAtirador($novaSenha);
}
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
        <h3 style="color: #3e4095; font-weight: bold">DECLARAÇÃO RANKING</h3>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Você está credenciado como atirador Nível III - Nacional.</div>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> São necessários 8 eventos para imprimir esta declaração.</div>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Você tem acumulado nos últimos 12 meses: 1 eventos (as provas de ar comprimido não são utilizadas para habitualidade).</div>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Já existe uma declaração preparada nesta data. Consulte abaixo e reemita a sua declaração se for o caso.</div>
        <p>Confira abaixo as declarações já emitidas:</p>
         <table class="table table-striped table-bordered">
           <thead>
             <tr>
               <td style="background-color: #4682B4; text-align: center; color: #FFF">Data</td>
               <td style="background-color: #4682B4; text-align: center; color: #FFF">Autenticação</td>
               <td style="background-color: #4682B4; text-align: center; color: #FFF">Reenviar</td>
             </tr>
             <tr>
               <td style="text-align: center">11/10/2018</td>
               <td style="text-align: center">2018120300794101</td>
               <td style="text-align: center"><button type="button" name="button" class="btn btn-primary" title="Reenvie sua declaração de filiação de entidade"><i class="fas fa-print"></i></button> </td>
             </tr>
           </thead>
         </table>
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
  <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.min.js"></script>
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
