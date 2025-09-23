<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if(!isset($_SESSION["NomeAtiradorBusca"]))
{
  list($nome,$texto) = explode("(",filter_input(INPUT_POST, 'Nome', FILTER_SANITIZE_STRING));
  $_SESSION["NomeAtiradorBusca"] = $nome;
}
if($_SESSION["ErroAtleta"] < time())
{
  unset($_SESSION["ErroAtleta"]);
}
$tabela = 'atirador';
$idTabela = 'nome';
$idBusca = $_SESSION["NomeAtiradorBusca"];
$visualizar = $metodos->visualizar($tabela,$idTabela,$idBusca);
if($visualizar[0] == 0)
{
  $_SESSION["ErroAtleta"] = time() + 5;
  echo "<script>window.location.href='".$caminhoAbsoluto."/atletas/'</script>";
  exit();
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
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
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
<script>
   function mostrar(valor)
   {
     var valor;
     if(valor == 1)
     {
       document.getElementById("resultados").style.display='block';
       document.getElementById("graficos").style.display='none';
       document.getElementById("btnResultados").style.display='none';
       document.getElementById("btnGraficos").style.display='block';
     }
     if(valor == 2)
     {
       document.getElementById("graficos").style.display='block';
       document.getElementById("resultados").style.display='none';
       document.getElementById("btnGraficos").style.display='none';
       document.getElementById("btnResultados").style.display='block';
     }
   }
</script>
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
         <li class="nav-item active">
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
      <div class="col-md-12 col-xs-12" style="margin-top: 20px">
           <div style="margin-top: 10px">
        <div class="row" style="margin-top: 10px">
             <div class="col-md-2">
               <img src="<?php echo $caminhoAbsoluto; ?>/images/sem-foto.png" class="img img-thumbnail" style="width: 180px">
               <?php
                 list($ano, $mes, $dia) = explode('-', $visualizar[1]->data_nascimento);
                $hoje = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $nascimento = mktime( 0, 0, 0, $mes, $dia, $ano);
                $idade = floor((((($hoje - $nascimento) / 60) / 60) / 24) / 365.25);
               ?>
               <div style="text-align: center; font-weight: bold"> <?php echo $idade; ?> anos</div>

               <div style="text-align: center; margin-top: 10px">
                 <button type="button" class="btn btn-success btn-sm btn-block" name="button" onclick="mostrar(1)"  id="btnResultados" style="display: none" title="Visualizar os resultados"><i class="fas fa-user-check"></i> Resultados</button>
               </div>

                <div style="text-align: center; margin-top: 10px">
                 <button type="button" class="btn btn-primary btn-sm btn-block" name="button" onclick="mostrar(2)" id="btnGraficos" style="display: block" title="Visualizar os gráficos"><i class="fas fa-chart-bar fa-lg"></i> Gráficos</button>
               </div>

               <div style="text-align: center; margin-top: 10px">
                 <button type="button" class="btn btn-warning btn-sm btn-block" name="button" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/atletas/'"><i class="fas fa-angle-double-left"></i> Voltar</button>
               </div>
             </div>
             <div class="col-md-10">
               <div class="text-center"><h3 style="color: #3e4095; font-weight: bold"><?php echo $visualizar[1]->nome; ?></h3></div>
              <?php // echo $visualizar[1]->atirador; ?>
              <div id="resultados" style="display: block">
               <form class="" action="<?php echo $caminhoAbsoluto; ?>/detalhes-atletas/" method="post">
                   <?php
                        if($_POST["Prova"])
                        {
                          $prova = $_POST["Prova"];
                        }
                        else
                        {
                         $prova = "";
                        }
                        echo $metodos->listarProvasAtletas($visualizar[1]->atirador,$prova);
                   ?>
               </form>
             </div>
             <div id="graficos" style="display: none;margin-top: 50px">
                   <span style="text-align: center;"><h4><i class="fas fa-exclamation-triangle"></i> Em desenvolvimento!</h4></span>
             </div>
             </div>
          </div>
        </div>
      </div>
    </div>
    <div style="height: 230px"></div>
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
   <script>
        $(function () {
            $("#atirador").autocomplete({
                source: '<?php echo $caminhoAbsoluto; ?>/processar-busca-atiradores.php'
            });
        });
    </script>
  </body>
</html>
