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
     <div class="container">
     <div class="menu">
      <div class="row">
        <div class="col-md-4">
         <figure>
          <img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" alt="" class="logo">
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
  <div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">Notícias</h3>
        <div class="row">
            <ul style="font-size:13px">
              <li><a href="#" style="color: green">INVESTIMENTO - CONTRATO MODELO DE IMPORTAÇÃO POR LOTE DE ARMAS EXCLUSIVAMENTE PARA VENDA A CACS.</a></li>
              <hr>
              <li><a href="#" style="color: green">PORTARIA DO MPDF- INQUÉRITO CONTRA TAURUS.</a></li>
              <hr>
              <li><a href="#" style="color: green">DECLARAÇÃO DA CONFEDERAÇÃO REFERENTE AO CERTIFICADO DE INSTRUTOR DE TIRO</a></li>
              <hr>
              <li><a href="#" style="color: green">COMUNICADO CBC/TAURUS AO MERCADO - OPINIÃO DA CTCB</a></li>
              <hr>
              <li><a href="#" style="color: green">PARECER DO MPT EM GO QUE MANDOU RECOLHER AS TAURUS</a></li>
              <hr>
              <li><a href="#" style="color: green">VITÓRIA DO CAC, FILIADO A CONFEDERAÇÃO, NA JUSTIÇA CONTRA A TAURUS</a></li>
          </ul>
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
            <div class="col-md-3"><img src="<?php echo $caminhoAbsoluto; ?>/fotos/foto4.jpg" class="img-fluid" style="border: 2px solid #000"></div>
            <div class="col-md-3"><img src="<?php echo $caminhoAbsoluto; ?>/fotos/foto1.jpg" class="img-fluid" style="border: 2px solid #000">&nbsp;</div>
            <div class="col-md-3"><img src="<?php echo $caminhoAbsoluto; ?>/fotos/foto2.jpg" class="img-fluid" style="border: 2px solid #000">&nbsp;</div>
            <div class="col-md-3"><img src="<?php echo $caminhoAbsoluto; ?>/fotos/foto3.jpg" class="img-fluid" style="border: 2px solid #000">&nbsp;</div>
            <div class="col-md-12" align="center"><button class="btn btn-success">Ver todas as fotos</button></div>
        </div>
        <hr>
        <h3 style="color: #3e4095; font-weight: bold">Vídeos</h3>
        <div class="row">
            <div class="col-md-5"><iframe width="300" height="175" src="https://www.youtube.com/embed/3Sq4pHbydW0?rel=0" frameborder="0" allowfullscreen style="border: 2px #068a50 solid;"></iframe></div>
            <div class="col-md-2"></div>
            <div class="col-md-5"><iframe width="300" height="175" src="https://www.youtube.com/embed/3Sq4pHbydW0?rel=0" frameborder="0" allowfullscreen style="border: 2px #068a50 solid;"></iframe></div>
            <div class="col-md-12" align="center"><button class="btn btn-success">Ver todos os vídeos</button></div>
        </div>
      </div>

      <div class="col-md-4 col-xs-12">
        <!-- Menu Lateral -->
       <div class="bg-warning text-center" style="padding: 10px">
            <span style="font-family: Arial">Valor em aberto: R$ 230,00</span>
            <br>
            <button class="btn btn-success btn-sm" style="font-weight: bold">Pagar</button>
       </div>
       <div class="bg-success">
          <div class="container text-white">
            <div class="text-right">
            Seja bem-vindo de volta,<br>
            <strong>José Marcos</strong>
          </div>
            <br>
            <div class="row">
              <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/alterar-cadastro/'" style="cursor: pointer" title="Altere seu cadastro"><i class="fas fa-user fa-lg"></i><p style="font-size: 12px">Cadastro</p></div>
              <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/alterar-senha/'" style="cursor: pointer" title="Altere sua senha"><i class="fas fa-key fa-lg"></i><p style="font-size: 12px">Senha</p></div>
              <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/inscricao/'" style="cursor: pointer" title="Faça novas inscrições"><i class="fas fa-pen-nib fa-lg"></i><p style="font-size: 12px">Inscrição</p></div>
              <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/carteira/'" style="cursor: pointer" title="Imprima sua carteira"><i class="fas fa-address-card fa-lg"></i><p style="font-size: 12px">Carteira</p></div>
          </div>
          <div class="row" style="margin-top: 10px">
            <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/valores-pagos/'" style="cursor: pointer" title="Veja os valores pagos"><i class="fas fa-hand-holding-usd fa-2x"></i><p style="font-size: 12px">Valor pago</p></div>
            <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/valores-abertos/'" style="cursor: pointer" title="Veja os valores em aberto"><i class="fas fa-dollar-sign fa-2x"></i><p style="font-size: 12px">Pagamento em aberto</p></div>
            <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/armas/'" style="cursor: pointer"  title="Cadastre sua arma"><i class="fas fa-crosshairs fa-2x"></i><p style="font-size: 12px">Armas</p></div>
            <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/anuncios/'" style="cursor: pointer"><i class="fas fa-file-invoice-dollar fa-2x"></i><p style="font-size: 12px">Anúncio</p></div>
        </div>
        <div class="text-center" style="margin-top: 10px">
        <small>Em 12 meses - 01 evento</small>
        <hr>
        <h5>Declarações e Impressos</h5>
        <div class="row" style="margin-top: 20px;">
          <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/entidade/'" style="cursor: pointer" title="Imprima sua declaração a Entidade"><i class="fas fa-stamp fa-lg"></i><p style="font-size: 12px">Filiação Entidade</p></div>
          <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/habitualidade/'" style="cursor: pointer" title="Imprima sua declaração de habitualidade"><i class="fas fa-user-clock fa-lg"></i><p style="font-size: 12px">Habitua-<br>lidade</p></div>
          <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/hanking/'" style="cursor: pointer" title="Imprima seu hanking"><i class="fas fa-medal fa-lg"></i><small>Ranking</small></div>
          <div class="col-md-3 text-center"onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/modalidades-provas/'" style="cursor: pointer" title="Imprima sua declaração de modalidades e provas"><i class="fas fa-clock fa-lg"></i></i><p style="font-size: 12px">Modalidade e prova</p></div>
      </div>
      <div class="row" style="margin-top: 10px">
        <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/guia-trafego/'" style="cursor: pointer" title="Imprima sua declaração para solicitar sua guia de tráfego"><i class="fas fa-walking fa-2x"></i><p style="font-size: 12px">Guia de Tráfego</p></div>
        <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/atividades/'" style="cursor: pointer" title="Imprima sua declaração de atividades"><i class="fas fa-chart-line fa-2x"></i><p style="font-size: 12px">Atividade</p></div>
        <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/certificados/'" style="cursor: pointer" title="Imprima seus certificados"><i class="fas fa-award fa-2x"></i><p style="font-size: 12px">Certificado</p></div>
        <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/curriculo-esportivo/'" style="cursor: pointer" title="Imprima seu currículo esportivo"><i class="fas fa-file-signature fa-2x"></i><p style="font-size: 12px">Currículo esportivo</p></div>
    </div>
      </div>
          </div>
        </div>
        <!-- Fim do menu lateral -->
        <div class="row" style="padding: 10px">
          <button class="btn btn-danger" style="width: 100%; font-weight: bold" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/validar-documentos/'"><i class="fas fa-check"></i> Validar Documentos</button>
        </div>
        <div class="row" style="padding: 10px">
          <h4 style="width: 100%; text-align: center" class="textoFace">FACEBOOK</h4>
          <div style="width: 100%; text-align: center">
          <div class="fb-like" data-href="https://www.facebook.com/Confederação-de-Tiro-e-Caça-do-Brasil-1407741389444948" data-send="true" data-layout="button_count" data-width="160" data-show-faces="true"></div><br>
          <a href="https://www.facebook.com/Confederação-de-Tiro-e-Caça-do-Brasil-1407741389444948" target="_blank">acessar a fanpage</a>
        </div>
        </div>
        <div class="row" style="padding: 10px;">
          <div style="width: 100%; text-align: center">
          <a href="#" target="_blank"><img src="https://www.ctcb.org.br/images/banners/compre_sem_intermediario.jpg" width="290" alt="PM Cofres" style="border:1px solid #666;" /></a>
        </div>
        </div>
        <div class="row" style="padding: 10px;">
          <div style="width: 100%; text-align: center">
            <button type="button" class="btn btn-primary btn-lg">SEU ANÚNCIO AQUI</button>
        </div>
        </div>
        <div class="row" style="padding: 10px;">
          <div style="width: 100%; text-align: center">
            <div class="card" style="width: 100%; background-color: #59A22D;">
              <h5 style="color: #FFF; font-weight: bold; text-shadow: 2px -2px #000; margin-top: 10px"><i class="fas fa-newspaper"></i> NEWSLETTER</h5>
              <div class="card-body">
                <div class="form-group">
                  <label for="email">Coloque seu email abaixo:</label>
                  <input type="email" class="form-control" id="email" aria-describedby="email" placeholder="Coloque seu email">
                </div>
                <button type="submit" class="btn btn-success">Cadastrar</button>
              </div>
            </div>
          </div>
        </div>
        <div class="row" style="padding: 10px;">
          <div style="width: 100%; text-align: center">
            <h5 style="font-weight: bold">APOIO A ESPORTE</h5>
            <div class="row">
            <div class="col-md-6">
              <a href="https://www.mildot.com.br" target="_blank"><img src="https://www.ctcb.org.br/images/banners/banner_mildot.jpg" width="138" alt="Mildot Comércio de Material de Segurança" style="border:1px solid #666;" /></a>
            </div>
            <div class="col-md-6">
              <a href="https://www.militaria.com.br" target="_blank"><img src="https://www.ctcb.org.br/images/banners/banner_militaria.jpg" width="138" alt="Militaria - Fabrica de Armas e Munições" style="border:1px solid #666;" /></a>
            </div>
          </div>
          </div>
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
          <button type="button" class="btn btn-light" data-dismiss="modal">Lembrei</button>
        </div>
      </div>
    </div>
  </div>
   <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
   <script src="<?php echo $caminhoAbsoluto; ?>/js/bootstrap.min.js"></script>
  </body>
</html>
