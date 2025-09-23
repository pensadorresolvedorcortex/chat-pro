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
$tabela = 'noticia';
$idTabela = 'id_noticia';
$idBusca = $_SERVER["QUERY_STRING"];
$visualizar = $metodos->visualizar($tabela,$idTabela,$idBusca);
if($visualizar[0] == 0)
{
  echo "<script>window.location.href='".$caminhoAbsoluto."/noticias/'</script>";
  exit();
}
list($ano,$mes,$dia) = explode("-",$visualizar[1]->data);
$dataNoticia = $dia."/".$mes."/".$ano;
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
       <div class="menu-superior"><a href="<?php echo $caminhoAbsoluto; ?>/">Principal</a> &nbsp; <a href="<?php echo $caminhoAbsoluto; ?>/fale-conosco/">Contato</a> &nbsp; <a href="<?php echo $caminhoAbsoluto; ?>/localizacao/">Localização</a></div>
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
         <li class="nav-item active">
           <a class="nav-link active" href="<?php echo $caminhoAbsoluto; ?>/noticias/"  alt="Ir para a página de notícias">Notícias</a>
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
        <h3 style="color: #3e4095; font-weight: bold">NOTÍCIAS</h3>
           <div style="margin-top: 20px">




             <table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td align="right"><button class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/noticias/'">Voltar</button></td>
</tr>
<tr>
<td height="10"></td>
</tr>
<tr>
<td><span style='font-size: 24px'><b><?php echo $visualizar[1]->titulo; ?></b></span>
  <br>
  <a style='font-size: 14px'><?php echo $dataNoticia; ?></a></td>
</tr>
<tr>
<td height="10"></td>
</tr>
<tr>
<td>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td valign="top">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td><div align="justify">
  <a style="font-size: 14px">
<?php echo $visualizar[1]->materia; ?>
  </a>
</div></td>
</tr>
<tr>
<td height="15"></td>
</tr>
<tr>
<td><a class="texto11p">fonte: </a><a class="texto11y">ctcb</a></td>
</tr>
</table>
</td>
<td width="15"></td>
<td width="300" valign="top" align="center">

<!-- Foto -->
<?php
$foto = 'imagens-noticias/pequena/'.$visualizar[1]->id_noticia.'_1.jpg';
if(file_exists($foto))
{
 echo $metodos->mostrarFotosNoticias($visualizar[1]->id_noticia);
}
?>
<!-- downloads -->
<?php echo $metodos->mostrarArquivosNoticias($visualizar[1]->id_noticia); ?>
</div>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td height="10"></td>
</tr>
</table>






           <?php //echo $metodos->visualizarNoticias($visualizar[1]->noticias); ?>

        </div>
      </div>
    </div>
     <footer>
     <p style="font-size: 13px">
        <a href="<?php echo $caminhoAbsoluto; ?>/" style="color: #FFF">Principal</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/atletas/" style="color: #FFF">Atletas</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/clubes/" style="color: #FFF">Clubes</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/instrutores/" style="color: #FFF">Instrutores</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/regulamento/" style="color: #FFF">Regulamento</a>  |
        
        <a href="<?php echo $caminhoAbsoluto; ?>/noticias/" style="color: #FFF">Notícias</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/fotos/" style="color: #FFF">Fotos</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/videos/" style="color: #FFF">Vídeos</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/importacao/" style="color: #FFF">Importação</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/fale-conosco/" style="color: #FFF">Contato</a>  |
        <a href="<?php echo $caminhoAbsoluto; ?>/localizacao/" style="color: #FFF">Localização</a><br><br>
        <i class="fas fa-phone"></i> (21) 2292-0888<br><br>
        <i class="fas fa-map-marker-alt"></i> Av. Beira Mar 200 sala 504/2 - Centro - Rio de Janeiro - RJ - 20030-130
       </p>
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
