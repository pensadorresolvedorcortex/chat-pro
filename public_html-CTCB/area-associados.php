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
 <div class="col-md-12 fundo-container">
    <?php include("header.php") ?>
  </div>
  <div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">&nbsp;</h3>
        <div >
        <h3>Atenção</h3>
        </i> Prezado Associado, o sistema detectou e bloqueou automáticamente vários associados que estão com insconsistência nos dados cadastrais, tais como: e-mail e telefone celular repetido. Por favor, entre em contato para validar seu cadastro. atendimento@ctcb.org.br</div>
        <p>
        <p>
        <h3 style="color: #3e4095; font-weight: bold">ÁREA DO ASSOCIADO</h3>
    <!--    <small><i class="fas fa-lock"></i> <a href="https://www.google.com/recaptcha/intro/v3.html" target="_blank" style="color: #000" title="Visite o site">Usando o reCAPTCHA v3</a></small> -->
           <div style="margin-top: 20px">
            <p>Insira seu e-mail se for associado ou seu login para despachantes e usuários do sistema.</p>
            <?php if($_SESSION["ErroLogin"]){ ?>
              <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Login ou senha inválidos! Por favor, entre em contato com a CTCB - URGENTE!! atendimento@ctcb.org.br</div>
            <?php } ?>
                <?php if($_SESSION["Sucesso"]){ ?>
              <div class="alert alert-success"><i class="fas fa-check"></i> Senha alterada com sucesso!</div>
            <?php } ?>
            
          <!--  <?php if($_SESSION["ErroCaptcha"]){ ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro na validação da chave de segurança!</div>
          <?php } ?>
            <form method="post" action="<?php echo $caminhoAbsoluto; ?>/validar-captcha/"> -->
            <form method="post" action="#!">
              <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" value="">
               <div class="form-group">
                 <label for="tipoAcesso"><i class="fas fa-arrow-right"></i> Tipo de Acesso:</label><br>
                 <input type="radio" name="TipoAcesso" value="A" checked id="tipoAcesso"> Atirador
                 <input type="radio" name="TipoAcesso" value="D" id="tipoAcesso"> Despachantes
               </div>
               <div class="form-group">
                 <label for="login"><i class="fas fa-user"></i> Login:</label>
                 <input type="text" name="LoginAcesso" class="form-control" id="login">
               </div>
               <div class="form-group">
                 <label for="pwd"><i class="fas fa-key"></i> Senha:</label>
                 <input type="password" name="SenhaAcesso" class="form-control" id="pwd">
               </div>
               <div class="form-group">
               <div align="center">
               <button type="submit" class="btn btn-primary">ACESSAR</button>
             </div>
           </div>
          <div align="center">
             <a href="<?php echo $caminhoAbsoluto; ?>/cadastro-associado/" class="link">Quero me associar</a> | <a href="#" data-toggle="modal" data-target="#myModal" class="link">Esqueci a senha</a>
          </div>
             </form>
        </div>
      </div>
      
       <div class="col-md-4 col-xs-12">
         <?php if($_SESSION["Logado"] == false){
           include("menu-nao-logado.php");
         }else{
          include("menu-logado.php");
        }?>
        
      </div><?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if($_POST){
  $tipoAcesso = $_POST["TipoAcesso"];
  $login = $_POST["LoginAcesso"];
  $senha = $_POST["SenhaAcesso"];
  echo $metodos->validarUsuarios($tipoAcesso,$login,$senha);
}
if($_SESSION["ErroLogin"] < time()){
  unset($_SESSION["ErroLogin"]);
}
?>
    <footer>
      <?php include("footer.php") ?>
    </footer>
  </div>
  </div>
<!-- The Modal -->
<div class="modal fade" id="myModal">
    <form method="post" id="contact-form">
      <div class="modal-dialog">
        <div class="modal-content">
          <!-- Modal Header -->
          <div class="modal-header bg-info text-white">
            <h4 class="modal-title">Esqueceu a senha?</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <!-- Modal body -->
          <div class="modal-body">
            <div id="success"></div>
            <p>Isso acontece! Para recuperar, digite seu e-mail abaixo:</p>
            <div class="form-group">
              <div id="success"></div>
              <input type="email" name="EmailVerificar" class="form-control" placeholder="Digite seu e-mail" id="email">
            </div>
          </div>
          <!-- Modal footer -->
          <div class="modal-footer">
            <button type="submit" id="submit" class="btn btn-success">Enviar</button>
            <button type="button"class="btn btn-light" data-dismiss="modal">Lembrei</button>
          </div>
        </div>
      </div>
    </form>
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
     $('#submit').click(function() {
        $.post("<?php echo $caminhoAbsoluto; ?>/validar-email.php", $("#contact-form").serialize(), function(response) {
        $('#success').html(response);
        $("div.alert").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
        $('#email').val('');
      });
      return false;
    });
</script>
   <script>
   $(document).ready(function(){
          $("div.alert").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
   });
   </script>
  </body>
</html>
