<?php
error_reporting(0);
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
}
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if(isset($_SESSION["NomeAtiradorBusca"]))
{
  unset($_SESSION["NomeAtiradorBusca"]);
}
if($_POST){
  $login = $_POST["LoginAcesso"];
  $senha = $_POST["SenhaAcesso"];
  echo $metodos->validarUsuarios('A', $login, $senha);
}
if (isset($_SESSION["ErroLogin"]) && $_SESSION["ErroLogin"] < time()){
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
<!-- COPIAR DAQUI -->

<div class="col-md-12 fundo-container">
  <?php include("header.php") ?>
</div>
<!-- ATÉ AQUI -->
  <div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-12 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold"><i class="fas fa-trophy"></i>   ATLETAS</h3>
           <div style="margin-top: 20px">
             <?php if (!empty($_SESSION["ErroAtleta"])){ ?>
               <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Nenhum atleta encontrado!</div>
             <?php } ?>
               <form class="" action="<?php echo $caminhoAbsoluto; ?>/detalhes-atletas/" method="post">
                 <div class="justify-content-center align-items-center row">
                 <div class="col-md-8 align-self-center text-left">
                  <div class="form-group">
                    <label>Digite o nome do atleta abaixo:</label>  <span id="loading_data_icon"></span>
                   <div class="input-group">
                       <input type="text" name="Nome" class="form-control col-md-8" placeholder="Digite o nome do atleta" id="atirador" aria-label="Buscar por nome" aria-describedby="btnGroupAddon2">
                      <div class="input-group-prepend">
                      <button type="submit" class="input-group-text" id="btnBuscar" style="cursor: pointer"><i class="fas fa-search"></i></button>
                    </div>
                  </div>
                  </div>
                </div>
              </div>
             </form>
          <div style="height: 300px"></div>
        </div>
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
     $(document).ready(function() {
       $( "#atirador" ).autocomplete({
           source: function(request, response){
           $('#loading_data_icon').html('<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>');    // showing loading icon
           $.ajax({
              url: '<?php echo $caminhoAbsoluto; ?>/processar-busca-atiradores.php',
              dataType: "json",
              data: {
                    'term' : request.term,
                    'empSearch' : 1
                    },
                    success: function(data) {
                        response(data);
                        $('#loading_data_icon').html('');
                    }
                });
            }
       });
     });
     </script>


   <!--
   <script>
        $(function () {
            $("#atirador").autocomplete({
                source: '<?php echo $caminhoAbsoluto; ?>/processar-busca-atiradores.php'
            });
        });
    </script>
  -->

  <!--
<script type="text/javascript">
    $(function () {
        $("#atirador").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: '<?php echo $caminhoAbsoluto; ?>/processar-busca-atiradores.php',
                    data: "{ 'prefix': '" + request.term + "'}",
                    dataType: "json",
                    type: "POST",
                    contentType: "application/json; charset=utf-8",
                    success: function (data) {
                        response($.map(data.d, function (item) {
                            return {
                                label: item.split('-')[0],
                                val: item.split('-')[1]
                            }
                        }))
                    }
                });
            },
            search: function (e, u) {
                $(this).addClass('loader');
            },
            response: function (e, u) {
                $(this).removeClass('loader');
            }
        });
    });
</script>
-->
<?php
if($_SESSION["ErroAtleta"] < time())
{
  unset($_SESSION["ErroAtleta"]);
}
 ?>
  </body>
</html>
