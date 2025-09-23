<?php

error_reporting(0);

session_start();

require_once('classes/metodosClass.php');

$metodos = new metodosClass();

$caminhoAbsoluto = $metodos->caminhoAbsoluto();

?>

<!DOCTYPE html>

<html lang="pt-br" dir="ltr">

<head>

  <meta charset="utf-8">

  <title>Confederação de Tiro e Caça do Brasil | CTCB</title>

  <meta name="description" content="Entidade que regula o Tiro e a caça em todo o território nacional">

  <meta name="keywords" content="tiro, esportivo, alvo, municao, calibre, revolver, esporte, precisao, mira, instrutor, atleta, competicao, medalha, fuzil, campeonato, oficial, federacao, brasil, distancia, bala, prato, fossa, double, skeet, deitado, carabina, prático, caça, javali, lune">

  <meta name="robots" content="index,follow">

  <meta name="viewport" content="width=device-width, initial-scale=1.0" />



  <!-- Desenvolvido por: -->

  <meta name="author" content="JG Soluções em Tecnologia | suporte.ctcb@gmail.com" />

  <!-- Contato: solucoes@pertuttigestao.com.br -->



  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">

  <link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/sticky-footer/">

  <link href="css/bootstrap.css" rel="stylesheet">

  <link href="css/style.css" rel="stylesheet">

  <!--    <script src="//code.jivosite.com/widget/YXdLsl00Ip" async></script>-->

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

          <a href="https://central.ctcb.org.br/"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" alt="" class="logo img-fluid"></a>

        </figure>

      </div>

      <div class="col-md-8">

        <div class="row offset-md-8">

          <div class="menu-superior"><a href="https://central.ctcb.org.br/" alt="Home">Principal</a> &nbsp; <a href="https://central.ctcb.org.br/atendimento" alt="Atendimento">Atendimento</a> &nbsp; <a href="https://central.ctcb.org.br/localizacao" alt="Localização">Localização</a></div>

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

                  <a class="nav-link" href="https://central.ctcb.org.br/importacao">Importação</a>

                </li>

                <span class="linha-vertical"></span>

                <li class="nav-item">

                  <a class="nav-link" href="https://central.ctcb.org.br/importacao">Caçadas</a>

                </li>

                <span class="linha-vertical"></span>

                <li class="nav-item">

                  <a class="nav-link" href="https://central.ctcb.org.br/curso-instrutor">Curso de Instrutor</a>

                </li>

                <span class="linha-vertical"></span>

                <li class="nav-item">

                  <a class="nav-link" href="https://central.ctcb.org.br/assessoria-juridica">Assessoria Jurídica</a>

                </li>

                <span class="linha-vertical"></span>

                <li class="nav-item">

                  <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/revista/" target="_blank">Revista</a>

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

                <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/atletas/">Atletas</a>

              </li>

              <span class="linha-vertical"></span>

              <li class="nav-item">

                <a class="nav-link" href="https://central.ctcb.org.br/representantes">Representantes</a>

              </li>

              <span class="linha-vertical"></span>



              <li class="nav-item">

                <a class="nav-link" href="https://central.ctcb.org.br/clubes">Clubes</a>

              </li>

              <span class="linha-vertical"></span>

              <li class="nav-item">

                <a class="nav-link" href="https://central.ctcb.org.br/regulamento">Regulamento</a>

              </li>

              <span class="linha-vertical"></span>

              <li class="nav-item">

                <a class="nav-link" href="https://central.ctcb.org.br/calendario">Calendário</a>

              </li>

              <span class="linha-vertical"></span>

              <li class="nav-item">

                <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/resultados/">Resultados</a>

              </li>

              <span class="linha-vertical"></span>

              <li class="nav-item">

                <a class="nav-link" href="https://central.ctcb.org.br/instrutores">Instrutores</a>

              </li>

              <span class="linha-vertical"></span>

              <li class="nav-item">

                <a class="nav-link" href="https://central.ctcb.org.br/noticias">Notícias</a>

              </li>

              <span class="linha-vertical"></span>

              <li class="nav-item">

                <a class="nav-link" href="https://central.ctcb.org.br/fotos">Fotos</a>

              </li>

              <span class="linha-vertical"></span>

              <li class="nav-item">

                <a class="nav-link" href="https://central.ctcb.org.br/videos">Vídeos</a>

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


      <div class="col-md-8 col-xs-12">

 


       <hr>
    <!--   <div class="container" style="display: inline-block;"> 
         <a href="https://ctcbrj.org.br/"><img src="../images/banners/logo-ctcbrj.png" alt="Logo CTCBRJ" width="350px;" align="center";></a>
        <a href="https://federacaodetiroecaca.org.br/"><img src="../images/banners/logo-federacao.png" alt="Logo Federação de Tiro e Caça" width="350px;" align="center";></a>

       </div>-->

<table style="border-collapse: collapse; width: 100%; height: 18px;" border="0">
<tbody>
<tr style="height: 18px;">
<td style="width: 100%; height: 18px; text-align: center;"><a title="Promo&ccedil;&atilde;o CTCB - Combo 3 em 1" href="https://ctcb.org.br/cadastro-associado/"><img src="https://federacaodetiroecaca.org.br/images/banners/promo-federacao.png" alt="" width="700" height="175" /></a></td>
</tr>
</tbody>
</table>
<table style="border-collapse: collapse; width: 100%; height: 18px;" border="0">
<tbody>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px; text-align: center;"><a title="Clube de Tiro e Caça do Brasil" href="https://ctcbrj.org.br/" target="_blank"><img src="https://federacaodetiroecaca.org.br/images/banners/logo-ctcbrj.png" alt="" width="345" height="133" /></a></td>
<td style="width: 50%; height: 18px; text-align: center;"><a title="Federação de Tiro e Caça" href="https://federacaodetiroecaca.org.br/" target="_blank"><img src="https://ctcb.org.br/images/banners/logo-federacao.png" alt="" width="345" height="133" /></a></td>
</tr>
</tbody>
</table>
   
       <h3 style="color: #3e4095; font-weight: bold">Notícias</h3>

       <div class="row">

        <?php echo $metodos->noticiasPaginaInicial(); ?>

      </div>

      <hr>

      <h3 style="color: #3e4095; font-weight: bold">Galeria de Fotos</h3>

      <div class="row">

        <div class="col-md-12">

          <span style="color: #59A22D; font-weight: bold">Prova no Clube Garça-SP</span><br>

          <small>08/07/2018</small><br>

        </div>

      </div>

      <div class="row">

        <div class="col-md-3"><img src="<?php echo $caminhoAbsoluto; ?>/fotos_old/foto4.jpg" class="img-fluid" style="border: 2px solid #000"></div>

        <div class="col-md-3"><img src="<?php echo $caminhoAbsoluto; ?>/fotos_old/foto1.jpg" class="img-fluid" style="border: 2px solid #000">&nbsp;</div>

        <div class="col-md-3"><img src="<?php echo $caminhoAbsoluto; ?>/fotos_old/foto2.jpg" class="img-fluid" style="border: 2px solid #000">&nbsp;</div>

        <div class="col-md-3"><img src="<?php echo $caminhoAbsoluto; ?>/fotos_old/foto3.jpg" class="img-fluid" style="border: 2px solid #000">&nbsp;</div>

        <div class="col-md-12" align="center"><button class="btn btn-success" onclick="window.location.href='https://central.ctcb.org.br/fotos'"><i class="fas fa-camera"></i> Ver todas as fotos</button></div>

      </div>

      <hr>

      <h3 style="color: #3e4095; font-weight: bold">Vídeos</h3>

      <div class="row">

        <div class="col-md-5"><iframe width="300" height="175" src="https://www.youtube.com/embed/3Sq4pHbydW0?rel=0" frameborder="0" allowfullscreen style="border: 2px #068a50 solid;"></iframe></div>

        <div class="col-md-5"><iframe width="300" height="175" src="https://www.youtube.com/embed/CJ8gQskMRDk" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>



        <div class="col-md-2"></div>



        <!--<div class="col-md-5"><iframe width="300" height="175" src="https://www.youtube.com/embed/3Sq4pHbydW0?rel=0" frameborder="0" allowfullscreen style="border: 2px #068a50 solid;"></iframe></div>-->

        <div class="col-md-12" align="center"><button class="btn btn-success" onclick="window.location.href='https://central.ctcb.org.br/videos'"><i class="fab fa-youtube"></i> Ver todos os vídeos</button></div>

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





<div id="notificacao" class="modal fade">

  <div class="modal-dialog">

    <!-- Modal content-->

    <div class="modal-content">

      <div class="modal-header" style="background-color: #B22222;">

        <h4 class="modal-title" style="color: #FFF"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> AVISO</h4>

        <button type="button" class="close" data-dismiss="modal">&times;</button>

      </div>

      <div class="modal-body" style="font-size: 16px">

        O site da CTCB entrará em manutenção no dia <strong>11/05/2019 (sábado)</strong> para aplicar melhorias e voltará a normalidade no dia <strong>13/05/2019 (segunda-feira)</strong>.<br><br>Agradecemos a compreensão de todos!

      </div>

      <div class="modal-footer" style="background-color: #f1f1f1">

        <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>

      </div>

    </div>

  </div>

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

<script src="js/jquery.min.js"></script>

<script src="js/bootstrap.min.js"></script>

<script>

  $(document).ready(function() {

	  // $('#notificacao').modal('show');

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

