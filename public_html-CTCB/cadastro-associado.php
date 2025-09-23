<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if($_SESSION["CPFCadastrado"] < time()){
  unset($_SESSION["CPFCadastrado"]);
}
if(isset($_SESSION["CPF"])){
  unset($_SESSION["CPF"]);
}
if($_POST["Submit"] == "ComCPF"){
  function validaCPF($cpf = null) {
  	if(empty($cpf)) {
  		return false;
  	}
    $cpf = preg_replace("/[^0-9]/", "", $cpf);
	  $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
  	if(strlen($cpf) != 11){
		   return false;
	  }else if(
      $cpf == '00000000000' ||
  		$cpf == '11111111111' ||
  		$cpf == '22222222222' ||
  		$cpf == '33333333333' ||
  		$cpf == '44444444444' ||
  		$cpf == '55555555555' ||
  		$cpf == '66666666666' ||
  		$cpf == '77777777777' ||
  		$cpf == '88888888888' ||
  		$cpf == '99999999999'){
		return false;
	  }else{
  		for ($t = 9; $t < 11; $t++) {
                          for ($d = 0, $c = 0; $c < $t; $c++) {
                                     $d += $cpf[$c] * (($t + 1) - $c);
                          }
                          $d = ((10 * $d) % 11) % 10;
                          if($cpf[$c] != $d) {
                                    return false;
                          }
  	  }
		return true;
 	 }
 } // fim da função
 $cpf = $_POST["CPF"];
 if(validaCPF($cpf) == false){
    $erro = true;
 }else{
   echo $metodos->validarCadastro($cpf);
 }
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

<div class="col-md-12 fundo-container">
    <?php include("header.php") ?>
</div>
  <div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">CADASTRO DE ASSOCIADO</h3>
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i> <strong>ATENÇÃO:</strong><br><br>
          Este é um formulário de pré-cadastro que dependerá da aprovação da diretoria e está passível de pedido de comprovação de dados. 
          Você será informado por e-mail.<br>
          <span style="color: red;">Ao digitar o seu CPF poderá verificar se esta filiado e tem cadastro. Receberá um email em até 5 minutos. Caso não receba é porque o seu e-mail está errado e deve se recadastrar, para atualizar seus dados. Se foi você quem fez o seu cadastro ou o seu despachante, envie todos os dados para serem conferidos e lhe enviaremos o acesso ao cadastro para atualizar. Clique em <strong><a href="<?php echo $caminhoAbsoluto; ?>/area-associados/">"Esqueceu a Senha"</a></strong> para ir para a área de login dos associados. Lembre-se , tudo vai valer para o Nível III e Habitualidade. <strong>Entre em contato por e-mail em: atendimento@ctcb.org.br ou pelo Whatsapp 21 98388-5061.</strong></span>

        </div>
           <div style="margin-top: 20px">
             <?php if($erro == true){ ?>
             <div class="alert alert-danger" id="erro"><i class="fas fa-exclamation-triangle"></i> CPF inválido! Favor digitar seu CPF corretamente!</div>
            <?php } ?>
            <?php if($_SESSION["CPFCadastrado"]){ ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Este CPF já consta cadastrado! <a href="#"  data-toggle="modal" data-target="#myModal">Esqueceu a senha?</a></div>
           <?php } ?>
            <form method="post" action="#!">
               <div class="form-group">
                 <label for="cpf">Digite seu CPF:</label>
                 <input type="text" name="CPF" class="form-control" id="cpf" data-inputmask="'alias': '(99)99999-9999'">
               </div>
               
           <!--<div class="form-group">
                 <label for="email">Digite seu E-mail:</label>
                 <input type="email" name="email" class="form-control" id="email">
               </div>
             -->
              <div class="form-group">
               <div align="center">
               <button type="submit" name="Submit" value="ComCPF" class="btn btn-success">Prosseguir <i class="fas fa-angle-double-right"></i></button>

            
             </div>
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
        
      </div>

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

    <footer>
      <?php include("footer.php") ?>
    </footer>
  </div>
  </div>


   <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.min.js"></script>
   <script src="<?php echo $caminhoAbsoluto; ?>/js/bootstrap.min.js"></script>
   <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.maskedinput-master/dist/jquery.maskedinput.js" type="text/javascript"></script>
   <script type="text/javascript">
       $(function() {
           $.mask.definitions['~'] = "[+-]";
           $("#cpf").mask("999.999.999-99");
       });
   </script>
   <script>
   $(document).ready(function(){
          $("#erro").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
         });
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






   </body>
</html>
