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
        <h3 style="color: #3e4095; font-weight: bold">VALIDAR DOCUMENTOS</h3>
        <div class="alert alert-primary">
          <div class="row">
            <div class="col-md-1"><i class="far fa-lightbulb fa-3x"></i></div>
            <div class="col-md-11 tex-left">Aqui você poderá digitar os códigos de validação impressos em nossos documentos oficiais e verificar sua autenticidade.</div>
          </div>
        </div>
           <div style="margin-top: 20px">
             <?php if($erro == true){ ?>
             <div class="alert alert-danger" id="erro"><i class="fas fa-exclamation-triangle"></i> CPF inválido! Favor digitar seu CPF corretamente!</div>
            <?php } ?>
            <?php if($_SESSION["CPFCadastrado"]){ ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Este CPF já consta cadastrado! <a href="#">Esqueceu a senha?</a></div>
           <?php } ?>
            <h5><i class="fas fa-caret-right"></i> Certificados</h5>
            <form method="post" action="<?php echo $caminhoAbsoluto; ?>/validar-declaracoes/">
          <input type="hidden" name="Validar" value="Sim">
           <div class="form-group">
             <input type="text" class="form-control" name="CodigoAutenticacao" aria-describedby="emailHelp">
             <small id="emailHelp" class="form-text text-muted">Coloque o código de autenticação.</small>
           </div>
           <div class="form-group" align="center">
             <button class="btn btn-primary"><i class="fas fa-check"></i> Verificar Certificado</button>
           </div>
           </form>

          <h5><i class="fas fa-caret-right"></i> Declarações</h5>
          <form method="post" action="<?php echo $caminhoAbsoluto; ?>/validar-declaracoes/">
          <input type="hidden" name="Validar" value="Sim">
          <div class="form-group">
           <input type="text" class="form-control" name="CodigoAutenticacao"  aria-describedby="emailHelp">
           <small id="emailHelp" class="form-text text-muted">Coloque o código de autenticação.</small>
          </div>
          <div class="form-group" align="center">
           <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Verificar Declaração</button>
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
   </body>
</html>
