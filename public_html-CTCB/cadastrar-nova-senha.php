<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if($_SESSION["NovaSenha"] == false)
{
  echo "<script>window.location.href='".$caminhoAbsoluto."/';</script>";
  exit();
}
if($_POST["Submit"] == "Salvar")
{
    $key = $_SESSION["IdAtirador"];
    $senha = $_POST["Senha"];
    echo $metodos->alterarSenhaRecuperacao($key,$senha);
    
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
      <div class="col-md-12 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold"><i class="fas fa-envelope"></i> CADASTRAR NOVA SENHA</h3>
        <form name="form" method="POST">
            <div class="form-group">
                 <label for="email">Senha: <small>(Entre 6 e 12 caracteres)</small></label>
                 <div class="input-group">
                       <input type="password" name="Senha" class="form-control senha-segura" id="senha" minlength="6" maxlength="12" required>
                       <span class="input-group-btn" id="btn-addon2">
                               <button type="button" class="btn btn-info addon-btn waves-effect waves-light" id="botaoSenha" title="Mostrar senha" onclick="mostrarSenha()">
                                 <i id="ver" class="far fa-eye fa-lg"></i>
                               </button>
                           </span>
                   </div>
               </div>
            <div class="form-group">
               <div align="center">
               <button type="submit" name="Submit" value="Salvar" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
             </div>
           </div>
        </form>
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
