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

    <?php include("header.php") ?>

</div>

<!-- ATÉ AQUI -->





<div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">VÍDEOS</h3>
        <div style="margin-top: 20px">
          <p style="text-align: justify">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
             <tr>
               <td height="1" colspan="3" bgcolor="#CCCCCC"></td>
             </tr>
             <tr>
               <td colspan="3">
                 <table width="100%" border="0" cellpadding="0" cellspacing="0">
                   <tr>
                     <td height="22" colspan="2"><a class="texto24p">
                       <strong>
                         <i class="far fa-calendar-check"></i>
                         <?php
                         if($_SERVER["QUERY_STRING"] == "")
                         {
                           echo  date("Y");
                         }
                         else
                         {
                           echo $_SERVER["QUERY_STRING"];
                         }
                         ?>
                       </strong>
                     </a>
                   </td>

                   <td height="22" align="right"><a class="texto11p">Escolha o ano:&nbsp;</a>
                     <?php
                     $anoEvento = $_SERVER["QUERY_STRING"];
                     echo $metodos->listarAnosVideos();
                     ?>
                   </td>
                 </tr>
               </table>
             </tr>
             <tr>
               <td height="1" colspan="3" bgcolor="#CCCCCC"></td>
             </tr>

           </table>
           <br>
           <?php
           if($_SERVER["QUERY_STRING"] == "")
           {
            $ano = date("Y");
          }
          else
          {
            $ano =  $_SERVER["QUERY_STRING"];
          }
          echo $metodos->listarVideosAno($ano);
          ?>
        </p>
      </div>
    </div>
    <div class="col-md-4 col-xs-12">
     <?php if($_SESSION["Logado"] == false){
       include("menu-nao-logado.php");
     }else{
      include("menu-logado.php");
    }?>


  </div>
</div>
<footer>
  <?php include("footer.php") ?>
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
