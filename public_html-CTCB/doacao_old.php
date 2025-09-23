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
         <li class="nav-item active">
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
           <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/representantes/">Representantes</a>
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
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">DOE PARA A CTCB</h3>
           <div style="margin-top: 20px">
            <p style="text-align: justify">A CTCB precisa da sua ajuda para continuar lutando pelos direitos dos CAC´s. Você pode doar escolhendo uma das opções abaixo, através do sistema PagSeguro.
            <p style="text-align: justify">
            
 Valor de R$530,00<br>
<!-- INICIO DO BOTAO PAGSEGURO --><a href="https://pag.ae/7We-yQqG4/button" target="_blank" title="Pagar com PagSeguro"><img src="https://stc.pagseguro.uol.com.br/public/img/botoes/doacoes/209x48-doar-assina.gif" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></a><!-- FIM DO BOTAO PAGSEGURO -->
 <hr>
Valor de R$1.055,00<br>
<!-- INICIO DO BOTAO PAGSEGURO --><a href="https://pag.ae/7We-zTaCQ/button" target="_blank" title="Pagar com PagSeguro"><img src="https://stc.pagseguro.uol.com.br/public/img/botoes/doacoes/209x48-doar-assina.gif" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></a><!-- FIM DO BOTAO PAGSEGURO -->
<hr>
Valor de R$1.585,00<br>
<!-- INICIO DO BOTAO PAGSEGURO --><a href="https://pag.ae/7We-BmJM8/button" target="_blank" title="Pagar com PagSeguro"><img src="https://stc.pagseguro.uol.com.br/public/img/botoes/doacoes/209x48-doar-assina.gif" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></a><!-- FIM DO BOTAO PAGSEGURO --> 
<hr>
Valor de R$2.110,00<br>
<!-- INICIO DO BOTAO PAGSEGURO --><a href="https://pag.ae/7We-C6C14/button" target="_blank" title="Pagar com PagSeguro"><img src="https://stc.pagseguro.uol.com.br/public/img/botoes/doacoes/209x48-doar-assina.gif" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></a><!-- FIM DO BOTAO PAGSEGURO --> 
<hr>
 Valor de R$2.635,00<br>
<!-- INICIO DO BOTAO PAGSEGURO --><a href="https://pag.ae/7We-CCgcK/button" target="_blank" title="Pagar com PagSeguro"><img src="https://stc.pagseguro.uol.com.br/public/img/botoes/doacoes/209x48-doar-assina.gif" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></a><!-- FIM DO BOTAO PAGSEGURO -->
<hr>
 Valor de R$3.160,00<br>
<!-- INICIO DO BOTAO PAGSEGURO --><a href="https://pag.ae/7WmrCEGB7/button" target="_blank" title="Pagar com PagSeguro"><img src="//assets.pagseguro.com.br/ps-integration-assets/botoes/pagamentos/205x30-pagar.gif" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></a><!-- FIM DO BOTAO PAGSEGURO -->      
            
             <div class="text-center"></div>
               <br>
             </p>
        </div>
      </div>
      <div class="col-md-4 col-xs-12">
      <div align="center"><a href="https://ctcb.org.br/cadastro-associado/"><img src="https://ctcb.org.br/images/quero_me_associar.png" width="323" height="105"></a></div>
        <div align="center"><a href="https://ctcb.org.br/area-associados/"><img src="https://ctcb.org.br/images/a_associado.png" width="323" height="112"></a></div>

        <div class="row" style="padding: 10px">
          <div style="width: 100%; padding:10px; background-color: #3E4095; color: #FFF; text-align: center; font-weight: bold">CONTA-CORRENTE</div>
          <p style="font-size: 14px; text-align: center; width: 100%"><br>
            Banco Bradesco - Ag 0469 - C/C 136861-3<br>
            CNPJ 12.499.864/0001-89
          </p>
        </div>
        <div class="row" style="padding: 10px">
          <div style="width: 100%; padding:10px; background-color: #3E4095; color: #FFF; text-align: center; font-weight: bold">MÉTODOS DE PAGAMENTO</div>
          <p style="font-size: 14px; text-align: center; width: 100%"><br>
            Nova matrícula ou renovação (R$ 242,08)
          </p>
          <form action="https://pagseguro.uol.com.br/checkout/v2/payment.html" method="post" onsubmit="PagSeguroLightbox(this); return false;">
          <input type="hidden" name="code" value="9CF83B61070729A44486CFA1EB7CA62B" />
          <input type="hidden" name="iot" value="button" />
          <div style="margin-left: 25%"><input type="image" src="https://stc.pagseguro.uol.com.br/public/img/botoes/pagamentos/209x48-pagar-assina.gif" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></div>
          </form>
          <br>
          <!--INICIO DA FAIXA COMPRAMOS SUA ARMA-->
          
         <div style="width: 100%; padding:10px; background-color: #3E4095; color: #FFF; text-align: center; font-weight: bold">COMPRA DA SUA ARMA</div>
         <div align="center"><a href="https://ctcb.org.br/detalhes-noticias/?127"><img src="https://ctcb.org.br/images/saiba_mais.png" width="311" height="125"></a></div>
         <!--FIM DA FAIXA COMPRAMOS SUA ARMA-->
          
          
        <!--INICIO DA RETIRADA PAGSEGURO  <p style="font-size: 14px; text-align: center; width: 100%">
            Emissão de CR ou renovação (R$ 1.265,00)
          </p>
          <form action="https://pagseguro.uol.com.br/checkout/v2/payment.html" method="post" onsubmit="PagSeguroLightbox(this); return false;">
            <!-- NÃO EDITE OS COMANDOS DAS LINHAS ABAIXO
            <input type="hidden" name="code" value="39372BD701010E33341E4F9622882ADF" />
            <input type="hidden" name="iot" value="button" />
            <div style="margin-left: 25%"><input type="image" src="https://stc.pagseguro.uol.com.br/public/img/botoes/pagamentos/209x48-pagar-assina.gif" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></div>
            </form>
            <br>
            <p style="font-size: 14px; text-align: center; width: 100%">
              Emissão de CR ou renovação (R$ 631,50)<br>
               promotores, juízes e policiais
            </p>
            <form action="https://pagseguro.uol.com.br/checkout/v2/payment.html" method="post" onsubmit="PagSeguroLightbox(this); return false;">
              <!-- NÃO EDITE OS COMANDOS DAS LINHAS ABAIXO 
              <input type="hidden" name="code" value="63855BFC1515D95CC4791F950DA4AAEB" />
              <input type="hidden" name="iot" value="button" />
              <div style="margin-left: 25%"><input type="image" src="https://stc.pagseguro.uol.com.br/public/img/botoes/pagamentos/209x48-pagar-assina.gif" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!" /></div>
              </form>FIM DA RETIRADA DO PAGSEGURO-->
        </div>
        <hr>
     <!--   <div class="row" style="padding: 10px">
  <button class="btn btn-primary" style="width: 100%; font-weight: bold" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/cadastro-associado/'"><i class="far fa-address-card fa-lg"></i> Quero me associar</button>
</div>
<div class="row" style="padding: 10px">
  <button class="btn btn-success" style="width: 100%; font-weight: bold" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/area-associados/'"><i class="fas fa-users fa-lg"></i> Área dos Associados</button>
</div>
<div class="row" style="padding: 10px;">
   <p>
  <h5 style="width: 100%; text-align: center;"><a href="#!" data-toggle="modal" data-target="#myModal" style="color:#218838"><i class="fas fa-question-circle"></i> Esqueci  a senha</a></h5>
  </p>
</div>-->
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
        <a href="<?php echo $caminhoAbsoluto; ?>/representantes/" style="color: #FFF">Representantes</a>  |  
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
        <i class="fas fa-map-marker-alt"></i> Av. Beira Mar 200 sala 504 - Centro - Rio de Janeiro - RJ - 20021.060
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
