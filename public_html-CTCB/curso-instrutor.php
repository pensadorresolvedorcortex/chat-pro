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
  <?php include("header.php") ?>
</div>

<!-- ATÉ AQUI -->





  <div class="container" style="margin-top: -10px;">
  <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
    <div class="row" style="margin-top: 10px; padding: 10px">
      <div class="col-md-8 col-xs-12">
        <h3 style="color: #3e4095; font-weight: bold">CURSO DE HABILITAÇÃO PARA INSTRUTOR DE TIRO</h3>
           <div style="margin-top: 20px">
            <p style="text-align: justify">
               Os cursos para Instrutores de Tiro, tem como base o Art.100 da Portaria 51 COLOG de 08/09/2015, que define que os instrutores devem ser credenciados pela Confederação e se registrarem no SFPC.
               <div class="text-center">
                 <img src="<?php echo $caminhoAbsoluto; ?>/images/curso_instrutor1.jpg" class="img-responsive" title="Foto de uma loja de armas mostrando o balção de rifles">
               </div>
               <p></p>
Este curso é exclusivo para Instrutores de Tiro já formados, com experiência que deverá ser comprovada através de certificados ou declaração do clube onde ministra as aulas.
<p></p>
<b>Conteúdo Programático</b>
<p></p>
<div align="center">
<table width="95%" border="0" cellpadding="5" cellspacing="0" class="table table-bordered">
  <tr>
    <td width="10%" style="text-align:center;background-color:#fafafa">08h00</td>
    <td width="90%">Coffee Break</td>
  </tr>
  
  <tr>
    <td width="10%" style="text-align:center;background-color:#fafafa">09h00</td>
    <td>Apresentação do Curso</td>
  </tr>
  
  <tr>
    <td width="10%" style="text-align:center;background-color:#fafafa">10h00</td>
    <td>Apresentação do TCC (Trabalho do Curso) - Onde cada um terá 10 minutos para apresentar o seu trabalho, no molde de uma aula teórica, com os temas que serão enviados por e-mail, quando da sua inscrição</td>
  </tr>
  
  <tr>
    <td width="10%" style="text-align:center;background-color:#fafafa">11h30</td>
    <td>Prova escrita de 100 questões com base no Decreto 3665/2000 (R105), Portaria 51 COLOG 08/09/2015, Lei 10826/2003 e Decreto 5123/2004</td>
  </tr>
  
  <tr>
    <td width="10%" style="text-align:center;background-color:#fafafa">12h30</td>
    <td>Intervalo para almoço. Será fornecida alimentação no local</td>
  </tr>
  
  <tr>
    <td width="10%" style="text-align:center;background-color:#fafafa">13h00</td>
    <td>Montagem e desmontagem de 1° escalão de revólveres, pistolas e espingardas. O aluno apresentará aula prática de, no máximo, 10 minutos com uma arma. Poderá utilizar sua própria arma desde que possua Guia de Tráfego (GT) da mesma ou o Porte Funcional</td>
  </tr>
  
  <tr>
    <td width="10%" style="text-align:center;background-color:#fafafa">14h30</td>
    <td>Aula prática de tiro no estande, com todos os tipos de armas usadas na Capacitação Técnica de Tiro. Simulando avaliações práticas</td>
  </tr>
  
  <tr>
    <td width="10%" style="text-align:center;background-color:#fafafa">18h00</td>
    <td>Encerramento do curso com a entrega da declaração de conclusão do Curso de Habilitação de Instrutor de Tiro</td>
  </tr>
</table>
</div>
<p></p>
A Confederação enviará, pelo correio, com firma reconhecida, a carteira de Instrutor da Confederação. Será enviado, também, a declaração de filiação na Confederação de Tiro e Caça do Brasil, válida por um ano.
<p></p>
O Aluno-Instrutor, deverá requerer, junto ao SFPC o apostilamento do seu certificado com  Instrutor de tiro, vinculado à Confederação. Os que não tiverem CR, deverão pedir o CR e já juntar o Certificado pedindo a atividade de Instrutor.
<ul>
<li>Deverão apresentar o atestado de Aptidão Psicológica para participar do curso;</li>
<li>O Aluno-Instrutor poderá trazer as suas armas, coldres, óculos e abafador para utilizar no curso;</li>
<li>O Aluno-Instrutor deverá comparecer com vestimenta apropriada para instrução de tiro.</li>
</ul>
<p></p>
               <div class="text-center" style="margin-top: 10px">
                 <img src="<?php echo $caminhoAbsoluto; ?>/images/curso_instrutor2.jpg" class="img-responsive" title="Foto de um avião parado no solo em um porto de navios e no fundo uma cidade">
               </div><br>
                <b>Valor do Curso</b><br />
R$ 1800,00 em 6x nos cartões de crédito<br />
R$ 250,00 para reserva da vaga
<p></p>
<b>Dados para depósito</b><br />
Confederação de Tiro e Caça do Brasil<br />
Banco Bradesco - 237<br />
Agência 0469-3<br />
Conta 139459-2<br />
CNPJ 14.973.348/0001-70<br /><br>

               <br><br>Entre em contato pelos nossos telefones ou <a href="<?php echo $caminhoAbsoluto; ?>/fale-conosco/" class="link" alt="Formulário de contato" title="Formulário de contato">Formulário de Contato</a> aqui do site.

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
