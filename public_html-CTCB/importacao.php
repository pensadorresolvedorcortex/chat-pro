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

<body>






  <!-- COPIAR DAQUI -->

<div class="col-md-12 fundo-container">
  <?php include 'header.php' ?>
</div>

<!-- ATÉ AQUI -->
 

<div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">IMPORTAÇÃO</h3>
        <div style="margin-top: 20px">
          <p style="text-align: justify">A CTCB faz <strong>GRATUITAMENTE</strong> o seu CII (Certificado Internacional de Importação) para os afiliados em dia com a Confederação de Tiro e Caça do Brasil. 
            Entre em contato conosco através do atendimento@ctcb.org.br ou pelo telefone (21) 2292-0888 para atualizar seu cadastro e/ou renovar sua afiliação. É muito importante validar seus dados em nosso cadastro para participar dos benefícios de ser Confederado.            
            <p style="text-align: justify">A CTCB da uma completa assessoria para você importar as suas armas, munições e acessórios.
              <p style="text-align: justify">A Confederação de Tiro e Caça do Brasil - CTCB, que apoia e coordena todo ano a importação de centenas de armas, agora fechou o convênio para você CAC comprar em local seguro nos Estados Unidos da América (EUA). A Confederação garantirá, totalmente, sua importação desde o início.
               <div class="text-center">
                 <img src="<?php echo $caminhoAbsoluto; ?>/images/importacao1.jpg" class="img-responsive" title="Foto de uma loja de armas mostrando o balção de rifles">
               </div>
               <br>Aqueles que quiserem comprar munições, estojos e até pontas, apesar do peso, também poderão fazê-lo. Este ano ajudamos e apoiamos a importação de muitos estojos e munições. Os preços praticados pela indústria nacional, chega a custar, em vários produtos, até 10x mais que o mercado internacional.
               <br><br>Estamos tentando organizar para que você possa importar as coisas pequenas, baratas ou não, que você desejar. Muitas empresas nos EUA não querem perder tempo enviando um produto somente e compras com cartão de crédito do Brasil cheira a fraude.
               <br><br>Vamos muito além de organizar provas de tiro. Cuidamos da importação do que você desejar, estamos fazendo convênios que ajudam e facilitam a vida do CAC, mas, infelizmente, nada é rápido.
               <div class="text-center" style="margin-top: 10px">
                 <img src="<?php echo $caminhoAbsoluto; ?>/images/importacao2.jpg" class="img-responsive" title="Foto de um avião parado no solo em um porto de navios e no fundo uma cidade">
               </div>
               <br>Vamos devagar porque temos pressa de chegar! Divulguem ao máximo esta oportunidade.
               <br><br><a href="<?php echo $caminhoAbsoluto; ?>/diversos/modelo_cii.doc" class="link" alt="Download do Modelo de Certificado Internacional de Importação (CII)" title="Download do Modelo de Certificado Internacional de Importação (CII)">Clique aqui</a> para fazer o download do Modelo de Certificado Internacional de Importação (CII).
               <br><br>Entre em contato pelos nossos telefones ou <a href="<?php echo $caminhoAbsoluto; ?>/fale-conosco/" class="link" alt="Formulário de contato" title="Formulário de contato">Formulário de Contato</a> aqui do site.

             </p>
           </div>
         </div>
         
         <div class="col-md-4 col-xs-12">
          <!--COLOCAR O PHP AQUI  -->


          <?php if($_SESSION["Logado"] == false){
           include("menu-nao-logado.php");
         }else{
          include("menu-logado.php");
        }?>

        <!--ATÉ AQUI-->

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
