<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if($_POST){
  $login = $_POST["LoginAcesso"];
  $senha = $_POST["SenhaAcesso"];
  echo $metodos->validarUsuarios($login,$senha);
}
if($_SESSION["ErroLogin"] < time()){
  unset($_SESSION["ErroLogin"]);
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




<!-- COPIAR DAQUI -->

<div class="col-md-12 fundo-container">
  <div class="container">
  <div class="menu">
   <div class="row">
     <div class="col-md-4">
      <figure>
       <a href="<?php echo $caminhoAbsoluto; ?>/"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" alt="Logomarca da CTCB em letras azuis e partes da arma em verde" title="Voltar para a página inicial" class="logo img-fluid"></a>
     </figure>
   </div>
   <div class="col-md-8">
     <div class="row offset-md-8">
       <div class="menu-superior"><a href="<?php echo $caminhoAbsoluto; ?>/">Principal</a> &nbsp; <a href="<?php echo $caminhoAbsoluto; ?>/fale-conosco/">Contato</a> &nbsp; <a href="<?php echo $caminhoAbsoluto; ?>/localizacao/" class="active">Localização</a></div>
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
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/importacao/" alt="Ir para a página de importação">Importação</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/cacadas/" alt="Ir para a página de caçadas">Caçadas</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/curso-instrutor/" alt="Ir para a página de curso de instrutor">Curso de Instrutor</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/assessoria-juridica/"  alt="Ir para a página de assessoria jurídica">Assessoria Jurídica</a>
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
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/atletas/"  alt="Ir para a página de atletas">Atletas</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/clubes/"  alt="Ir para a página de clubes">Clubes</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/regulamento/"  alt="Ir para a página de regularmento">Regulamento</a>
         </li>
         
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/resultados/"  alt="Ir para a página de resultados">Resultados</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/instrutores/"  alt="Ir para a página de instrutores">Instrutores</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/noticias/"  alt="Ir para a página de notícias">Notícias</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/fotos/"  alt="Ir para a página de fotos">Fotos</a>
         </li>
         <span class="linha-vertical"></span>
         <li class="nav-item">
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/videos/"  alt="Ir para a página de vídeos">Vídeos</a>
         </li>
       </ul>
     </div>
     </nav>
     </div>
   </div>
 </div>
</div>
</div>

<!-- ATÉ AQUI -->





  <div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-12 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">LOCALIZAÇÃO</h3>
           <div style="margin-top: 20px">
           
               

<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3675.0771653985666!2d-43.17239728447963!3d-22.910524485009912!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9981de7a0a3e19%3A0x49fff99a2eba29a8!2sAv.+Beira+Mar%2C+200+-+Centro%2C+Rio+de+Janeiro+-+RJ%2C+20030-130!5e0!3m2!1spt-BR!2sbr!4v1515418769320" width="100%" height="900" frameborder="0" style="border:0" allowfullscreen></iframe></td>
	 		   
			 
            
        </div>
      </div>
      
	  
	  
	  
	  
	  
    </div>
   <footer>
      <?php require_once ("footer.php") ?>
  </footer>
  </div>
  </div>
<!-- The Modal -->
<div class="modal fade" id="myModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header bg-info text-white">
        <h4 class="modal-title">Esqueceu a senha?</h4>
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <!-- Modal body -->
      <div class="modal-body">
        <p>Isso acontece! Para recuperar, digite seu e-mail abaixo:</p>
        <div class="form-group">
          <input type="email" class="form-control" placeholder="Digite seu e-mail" id="email">
        </div>
      </div>
      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-success">Enviar</button>
        <button type="button" class="btn btn-warning" data-dismiss="modal">Lembrei</button>
      </div>
    </div>
  </div>
</div>
   <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.min.js"></script>
   <script src="<?php echo $caminhoAbsoluto; ?>/js/bootstrap.min.js"></script>
   <!--<script src='https://www.google.com/recaptcha/api.js?render=6LcAyXwUAAAAAPA3mlipiFmxhRiieq2fJtrRZDgb'></script>-->
   <script>
   /*
     grecaptcha.ready(function() {
     grecaptcha.execute('6LcAyXwUAAAAAPA3mlipiFmxhRiieq2fJtrRZDgb', {action: 'login'})
     .then(function(token) {
       console.log(token);
       document.getElementById('g-recaptcha-response').value=token;
     });
     });
     */
   </script>
   <script>
   $(document).ready(function(){
          $("div.alert").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
   });
   </script>
  </body>
</html>
