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
<!--TOPO DO SITE-->
<div class="col-md-12 fundo-container">
  <?php include("header.php") ?>
</div>
<!--TOPO SITE-->
<div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">CAÇADAS</h3>
        <div style="margin-top: 20px">
         <div class="text-center">
           <h5 style="color: #59A22D; font-weight: bold">Estamos precisando de fazendas para as caçadas!</h5>
           <audio controls autoplay>
             <source src="<?php echo $caminhoAbsoluto; ?>/diversos/tema_ultimo_moicanos.ogg" type="audio/ogg">
               <source src="<?php echo $caminhoAbsoluto; ?>/diversos/tema_ultimo_moicanos.mp3" type="audio/mp3">
               </audio>
             </div>

             <p style="text-align: justify; margin-top: 10px">
               Vamos fazer safaris com direito a acampamento completo, mas antes, precisamos da aprovação do dono da fazenda, endereço correto com coordenadas da fazenda, bem como as condições para os caçadores. Você conhece alguma fazenda assim? Com animais que possam ser abatidos por estarem incomodando e dando prejuízo para o dono da fazenda?
               <div class="text-center">
                 <img src="<?php echo $caminhoAbsoluto; ?>/images/cacada1.jpg" class="img-responsive" title="Um javali correndo">
               </div>
               <br>Os javalis, por exemplo, atacam as lavouras por todo o Brasil e por isso mesmo, tiveram a caça liberada com o objetivo de controle de pragas. Os animais chegam a dar grandes prejuizos aos agricultores e por isso, nossa atividade, além de nos entreter, presta uma grande ajuda a sociedade.
               <br><br>Vamos unir uma grande tribo de caçadores, em vários estados, que desejem praticar o esporte da caça.
               <div class="text-center" style="margin-top: 10px">
                 <img src="<?php echo $caminhoAbsoluto; ?>/images/cacada2.jpg" class="img-responsive" title="O semblante de dois caçadores com rifles">
               </div>
               <br>Entre em contato pelos nossos telefones ou <a href="<?php echo $caminhoAbsoluto; ?>/fale-conosco/" title="Página de contato" class="link" alt="Direciona para a página de contato">Formulário de Contato</a> aqui do site.
               <br><br>Para saber mais sobre os procedimentos do IBAMA, que regulamenta a atividade no Brasil, clique nos links abaixo:
               <br><br><a href="https://www.ibama.gov.br/areas-tematicas-fauna-silvestre/procedimentos-para-manejo-do-javali-em-territorio-nacional" class="link" target="_blank" title="Procedimentos para o manejo do javali no território nacional" alt="Direciona para o Procedimentos para o manejo do javali no território nacional. Página externa.">Procedimentos para manejo do javali no território nacional.
                 <br><br><a href="https://www.ibama.gov.br/phocadownload/fauna_silvestre_2/temas_manejo_javali/form1_declaracao_manejo_javali_usos%20armas.pdf" class="link" target="_blank" title="Procedimentos para a Declaração de manejo de espécies exóticas invasoras" alt="Direciona para o Procedimentos para a Declaração de manejo de espécies exóticas invasoras. Página externa.">Declaração de manejo de espécies exóticas invasoras.</a>
                 <div class="text-center" style="margin-top: 10px">
                   <img src="<?php echo $caminhoAbsoluto; ?>/images/cacada3.jpg" class="img-responsive" title="Imagem de um búfalo" alt="Imagem de um búfalo">
                 </div>
                 <br>
                 Seguem abaixo alguns documentos sobre o manejo das espécies de búfalos no Brasil, onde fica definida, pelos órgãos reguladores a permissão para caça de controle destes animais.
                 <br><br><a href="<?php echo $caminhoAbsoluto; ?>/diversos/bufalos_parecer_tecnico_governador_reserva_biologica_guapore.pdf" target="_blank" class="link" title="Plano de Manejo de Espécies Invasoras - Vale do Guaporé - RO." alt="Plano de Manejo de Espécies Invasoras - Vale do Guaporé - RO.">Plano de Manejo de Espécies Invasoras - Vale do Guaporé - RO</a>
                 <br><br><a href="<?php echo $caminhoAbsoluto; ?>/diversos/bufalos_parecer_tecnico_recurso_reserva_biologica_guapore.pdf" target="_blank" class="link" title="Parecer Técnico ICMBio MMA - Vale do Guaporé - RO." alt="Parecer Técnico ICMBio MMA - Vale do Guaporé - RO.">Parecer Técnico ICMBio MMA - Vale do Guaporé - RO</a>
                 <br><br><a href="<?php echo $caminhoAbsoluto; ?>/diversos/bufalos_consulta_ibama.jpg"  target="_blank" class="link" title="Resposta do IBAMA sobre consulta da Confederação - Pedido de Informação." alt="Resposta do IBAMA sobre consulta da Confederação - Pedido de Informação.">Resposta do IBAMA sobre consulta da Confederação - Pedido de Informação</a>
                 <br><br><a href="<?php echo $caminhoAbsoluto; ?>/diversos/bufalos_consulta_ibama2.jpg" target="_blank" class="link" target="_blank" title="Resposta do IBAMA sobre consulta da Confederação - Recurso 1ª Instância" alt="Resposta do IBAMA sobre consulta da Confederação - Recurso 1ª Instância.">Resposta do IBAMA sobre consulta da Confederação - Recurso 1ª Instância</a>
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
        <?php include("footer.php");?>
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
