<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if($_POST == "Acessar"){
  $login = $_POST["LoginAcesso"];
  $senha = $_POST["SenhaAcesso"];
  echo $metodos->validarUsuarios($login,$senha);
}
if($_SESSION["ErroLogin"] < time()){
  unset($_SESSION["ErroLogin"]);
}
if($_SESSION["ErroEnvio"] < time()){
  unset($_SESSION["ErroEnvio"]);
}
if($_SESSION["Enviado"] < time()){
  unset($_SESSION["Enviado"]);
}
if($_POST["Submit"] == "Enviar"){

  require 'phpmailer/PHPMailerAutoload.php';
  require 'phpmailer/class.phpmailer.php';
  $mailer = new PHPMailer;
  $mailer->isSMTP();
  $mailer->SMTPOptions = array(
      'ssl' => array(
          'verify_peer' => false,
          'verify_peer_name' => false,
          'allow_self_signed' => true
      )
  );
  $nome = filter_input(INPUT_POST,"Nome");
  $email = filter_input(INPUT_POST,"Email",FILTER_VALIDATE_EMAIL);
  $telefone = filter_input(INPUT_POST,"Telefone");
  $cidade = filter_input(INPUT_POST,"Cidade");
  $naturalidade = filter_input(INPUT_POST,"Naturalidade");
  $departamento = filter_input(INPUT_POST,"Departamento");
  $texto = filter_input(INPUT_POST,"Mensagem");
  $caminhoAbsoluto = "https://".$_SERVER['SERVER_NAME']."";
  $assunto = "Mensagem enviada pelo site CTCB";
  $mailer->Host = 'mail.ctcb.org.br';
  $mailer->SMTPAuth = true;
  $mailer->IsSMTP();
  $mailer->isHTML(true);
  $mailer->Port = 587;
  $mailer->CharSet = 'UTF-8';
  $mailer->Username = 'naoexclua@ctcb.org.br';
  $mailer->Password = 'confeder@c@o';
  $address = "atendimento@ctcb.org.br";
  $mensagem = "Mensagem enviada no dia " .date("d/m/Y"). " às " .date("H:i"). "<br><br>";
  $mensagem .= "<strong>Nome:</strong> ".$nome."<br>";
  $mensagem .= "<strong>E-mail:</strong> ".$email."<br>";
  $mensagem .= "<strong>Telefone:</strong> ".$telefone."<br>";
  $mensagem .= "<strong>Cidade:</strong> ".$cidade."<br>";
  $mensagem .= "<strong>Estado:</strong> ".$naturalidade."<br>";
  $mensagem .= "<strong>Departamento:</strong> ".$departamento."<br>";
  $mensagem .= "<strong>Mensagem:</strong> " .$texto;
  $mailer->AddAddress($address, "CTCB");
  $mailer->addReplyTo($email, 'Resposta CTCB');
  $mailer->From = 'naoexclua@ctcb.org.br';
  $mailer->FromName = "Mensagem enviada pelo site da CTCB";
  $mailer->Subject = $assunto;
  $mailer->MsgHTML($mensagem);
  $mailer->Send();
    $_SESSION["Enviado"] = time() + 10;
    echo "<script>window.location.href=\"".$caminhoAbsoluto."/fale-conosco/\";</script>";
 
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
        <h3 style="color: #3e4095; font-weight: bold">
CONTATO</h3>
           <div style="margin-top: 20px">
            <p style="text-align: justify">

              <?php if($erro){ ?>
              <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php } ?>

            <?php if($_SESSION["Enviado"]){ ?>
             <div class="alert alert-success" style="text-align: center"><h3><i class="far fa-paper-plane fa-lg"></i> SUA MENSAGEM FOI ENVIADA COM SUCESSO!</h3><p>Agradecemos seu contato. Retornaremos o mais breve possível.</p></div>
            <?php } ?>
<form method="post">
<div class="form-group">
 <label for="nome">Nome:</label>
 <input type="text" name="Nome" class="form-control" value="" id="nome">
</div>

<div class="form-group">
 <label for="email">Email:</label>
 <input type="email" name="Email" class="form-control" value="" id="email">
</div>

<div class="form-group">
 <label for="telefone">Telefone:</label>
 <input type="text" name="Telefone" class="form-control" value="" id="telefone">
</div>

<div class="form-group">
 <label for="cidade">Cidade:</label>
 <input type="text" name="Cidade" class="form-control" value="" id="cidade">
</div>

<div class="form-group">
 <label for="estado">Estado:</label>
 <?php echo $metodos->listarNaturalidade($buscar = null); ?>
</div>

<div class="form-group">
 <label for="departamento">Departamento:</label>
 <select class="form-control" name="Departamento" id="departamento">
   <option value="Atendimento">Atendimento</option>
   <option value="Caçadas">Caçadas</option>
   <option value="Carteiras">Carteiras</option>
   <option value="Clubes">Clubes</option>
   <option value="Jurídico">Jurídico</option>
   <option value="Presidente">Presidente</option>
 </select>
</div>

<div class="form-group">
 <label for="mensagem">Mensagem:</label>
 <textarea name="Mensagem" class="form-control" rows="8" cols="80"></textarea>
</div>

<div class="form-group text-center">
 <button type="submit" name="Submit" value="Enviar" class="btn btn-primary">Enviar</button>
</div>
</form>

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
