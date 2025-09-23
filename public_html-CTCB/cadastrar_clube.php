<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
$dados = false;
if($_POST["Submit"] == "Salvar"){
  $dados = array_filter($_POST);
  echo $metodos->cadastrarClubes($dados);
} else{
  $_SESSION["SucessoCadastro"] = true;
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
  <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.min.js"></script>
  <script type="text/javascript" >

   $(document).ready(function() {

     function limpa_formulário_cep() {
               // Limpa valores do formulário de cep.

      $("#rua").val("");
      $("#bairro").val("");
      $("#cidade").val("");
      $("#uf").val("");
    }

           //Quando o campo cep perde o foco.
    $("#cep").blur(function() {

               //Nova variável "cep" somente com dígitos.
     var cep = $(this).val().replace(/\D/g, '');

               //Verifica se campo cep possui valor informado.
     if (cep != "") {

                   //Expressão regular para validar o CEP.
       var validacep = /^[0-9]{8}$/;

                   //Valida o formato do CEP.
       if(validacep.test(cep)) {

                       //ALTEREI Preenche os campos com "..." enquanto consulta webservice.
        $("#rua").val("...");
        $("#bairro").val("...");
        $("#cidade").val("...");
        $("#uf").val("...");

                       //Consulta o webservice viacep.com.br/
        $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

         if (!("erro" in dados)) {
                               //ALTEREI Atualiza os campos com os valores da consulta.
          $("#rua").val(dados.logradouro);
          $("#bairro").val(dados.bairro);
          $("#cidade").val(dados.localidade);                          				  
          $("#uf").val(dados.uf);
                           } //end if.
                           else {
                               //CEP pesquisado não foi encontrado.
                             limpa_formulário_cep();
                             alert("CEP não encontrado.");
                           }
                         });
                   } //end if.
                   else {
                       //cep é inválido.
                     limpa_formulário_cep();
                     alert("Formato de CEP inválido.");
                   }
               } //end if.
               else {
                   //cep sem valor, limpa formulário.
                 limpa_formulário_cep();
               }
             });
  });
   $(document).ready(function() {

     function limpa_formulário_cep() {
               // Limpa valores do formulário de cep.
       $("#ruaresponsavel").val("...");
       $("#bairroresponsavel").val("...");
       $("#cidaderesponsavel").val("...");
       $("#ufresponsavel").val("...");
     }

           //Quando o campo cep perde o foco.
     $("#cepresponsavel").blur(function() {

               //Nova variável "cep" somente com dígitos.
       var cep = $(this).val().replace(/\D/g, '');

               //Verifica se campo cep possui valor informado.
       if (cep != "") {

                   //Expressão regular para validar o CEP.
         var validacep = /^[0-9]{8}$/;

                   //Valida o formato do CEP.
         if(validacep.test(cep)) {

                       //Preenche os campos com "..." enquanto consulta webservice.
           $("#ruaresponsavel").val("...");
           $("#bairroresponsavel").val("...");
           $("#cidaderesponsavel").val("...");
           $("#ufresponsavel").val("...");

                       //Consulta o webservice viacep.com.br/
           $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

             if (!("erro" in dados)) {
                               //Atualiza os campos com os valores da consulta.
               $("#ruaresponsavel").val(dados.logradouro);
               $("#bairroresponsavel").val(dados.bairro);
               $("#cidaderesponsavel").val(dados.localidade);
               $("#ufresponsavel").val(dados.uf);
                           } //end if.
                           else {
                               //CEP pesquisado não foi encontrado.
                             limpa_formulário_cep();
                             alert("CEP não encontrado.");
                           }
                         });
                   } //end if.
                   else {
                       //cep é inválido.
                     limpa_formulário_cep();
                     alert("Formato de CEP inválido.");
                   }
               } //end if.
               else {
                   //cep sem valor, limpa formulário.
                 limpa_formulário_cep();
               }
             });
   });
 </script>
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
    <?php require_once("header.php"); ?>
  </div>
  <div class="container" style="margin-top: -10px;">
    <div style="height: 200px; background-color: #F8F8F8; background-image: linear-gradient(#F8F8F8, #FFF);">
      <div class="row" style="margin-top: 10px; padding: 10px">
        <div class="col-md-8 col-xs-12">
          <h3 style="color: #3e4095; font-weight: bold">CADASTRO DE CLUBE</h3>
          <br><br>
     <!--   <div class="text-center">
        <h3>EM MANUTENÇÃO</h3>
      </div> -->

      


      <div class="container" style="margin-top: 10px">
       <div class="row" style="margin-top: 10px">
         <div class="col-md-12">
           <div class="tituloCaixa">
             <i class="fas fa-exclamation-triangle"></i> Preencha o cadastro de forma correta, respeitando os campos obrigatórios. Todos os dados enviados serão confirmados para liberaçao de acesso ao sistema.
           </div>
           <div style="margin-top:10px">

            <div class="row">
             <div class="col-md-12">
              <hr>
              <i class="far fa-address-card fa-lg"></i> <strong>DADOS DO RESPONSÁVEL</strong>
              <hr>
               <form method="post" action="#!">

                <div class="form-group">
                  <label for="presidente">* Nome do Responsável:</label>
                  <input id="presidente" name="Presidente" type="text" class="form-control" required>
                </div>

                <div class="form-group">
                  <label for="cpf_resp">* CPF do Responsável:</label>
                  <input id="cpf_resp" name="Cpf_resp" type="text" class="form-control" required >
                </div>
 
                 <div class="form-group">
                  <label for="celular_resp">* Celular do Responsável:</label>
                  <input id="celular_resp" name="Celular_resp" type="text" class="form-control" required >
                </div>


                <div class="form-group">
                  <label for="email_resp">* Email do Responsável:</label>
                  <input id="email_resp" name="Email_resp" type="email" class="form-control" required>
                </div>
                <hr>
 
              <i class="far fa-address-card fa-lg"></i> <strong>DADOS DO CLUBE</strong>
              <hr>

                 <div class="form-group">
                   <label for="nome">* Nome do Clube:</label>
                   <input type="text" name="Nome" class="form-control" id="nome" value="" required>
                 </div>
                <div class="form-group">
                  <label for="cnpj_clube">* CNPJ do Clube:</label>
                  <input id="cnpj_clube" name="Cnpj_clube" type="text" class="form-control" required >
                </div>                 
                <div class="form-group">
                  <label for="cr_clube">* CR do Clube:</label>
                  <input id="cr_clube" name="Cr_clube" type="text" class="form-control" required >
                </div>

                <div class="form-group">
                  <label for="cr_validade">* Validade do CR do Clube:</label>
                  <input id="cr_validade" name="Cr_validade" type="text" class="form-control">
                </div>

                <div class="form-group">
                  <label for="rm_clube">* Região Militar do Clube:</label>
                  <input id="rm_clube" name="Rm_clube" type="text" class="form-control">
                </div>

                 <div class="form-group">
                   <label for="sigla">* Sigla do Clube:</label>
                   <input type="text" name="Sigla" class="form-control" id="sigla" value="" required>
                 </div>
                 <div class="form-group">
                   <label for="senha">* Senha de Acesso: <small>(Entre 6 e 12 caracteres)</small></label>
                   <input type="password" name="Senha" class="form-control senha-segura" id="senha" minlength="6" maxlength="12" required value="<?php echo $metodos->generatePassword(); ?>">
                 </div>
                 <div class="form-group">
                  <label for="status" hidden>Status:</label>
                  <select name="Status" class="form-control" id="status" hidden>
                    <option value="I">Inativo</option>
                    <option value="A">Ativo</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="telefoneEmpresa">Telefone Fixo:</label>
                  <input id="telefoneEmpresa" name="TelefoneComercial" type="text" class="form-control">
                </div>
                <div class="form-group">
                  <label for="celularEmpresa">* Celular/Whatsapp do Clube:</label>
                  <input id="celular" name="TelefoneCelular" type="text" class="form-control" required>
                </div>
                <div class="form-group">
                  <label for="email">* Email do Clube:</label>
                  <input id="email" name="Email" type="email" class="form-control" required>
                </div>

              <!-- <div class="form-group">
                  <label for="data">Fim do mandato:</label>
                  <input id="data" name="FimMandato" type="text" class="form-control">
                </div>-->


                <div class="form-group">
                  <label for="site">Site do Clube:</label>
                  <input id="site" name="Site" type="text" class="form-control" placeholder="Ex.: https://www.site.com.br">
                </div>
                <div class="form-group">
                 <label for="cep" class="control-label">* CEP:</label>
                 <input type="text" name="CEP" class="form-control" id="cep" required>
               </div>
               <div class="form-group">
                 <label for="rua" class="control-label">Endereço (Rua, Av, Travessa...):</label>
                 <input id="rua" name="Logradouro" type="text" class="form-control">
               </div>
               <div class="form-group">
                 <label for="rua" class="control-label">* Número:</label>
                 <input id="numero" name="Numero" type="text" class="form-control" required>
               </div>
               <div class="form-group">
                 <label for="rua" class="control-label">Complemento:</label>
                 <input id="complemento" name="Complemento" type="text" class="form-control">
               </div>                         
               <div class="form-group">
                 <label for="bairro" class="control-label">Bairro:</label>
                 <input id="bairro" name="Bairro" type="text" class="form-control">
               </div>
               <div class="form-group">
                 <label for="cidade">Cidade:</label>
                 <input id="cidade" name="Cidade" type="text" class="form-control">
               </div>
               <div class="form-group">
                 <label for="estado">Estado:</label>
                 <input id="uf" name="Estado" type="text" class="form-control">
               </div>

               <div class="form-group">
                <label for=""><i class="fas fa-caret-right"></i> CR do Clube: (Capa e anexos) .pdf</label>
                 <input type="file" class="form-control btn btn-light" name="Arquivos[]" value="" multiple placeholder="Só serão aceitos arquivos em PDF">
               </div>

               <div class="form-group">
                <div align="center">
                  <button type="submit" name="Submit" value="Salvar" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
              </div>
            </form>

          </div>


        </div>
      </div>
    </div>
  </div>
</div>
</div>
<div class="col-md-4 col-xs-12">
 <?php if($_SESSION["Logado"] == false){
   include("menu-nao-logado.php");
 }else{
  include("menu-logado.php");
}?>

</div>
<footer>
  <?php include("footer.php") ?>
</footer>
</div>
<script src="<?php echo $caminhoAbsoluto; ?>/js/bootstrap.min.js"></script>
<script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.maskedinput-master/dist/jquery.maskedinput.js" type="text/javascript"></script>
<script type="text/javascript">
 $(function() {
   $.mask.definitions['~'] = "[+-]";
   $("#cpf_resp").mask("999.999.999-99");
   $("#cep").mask("99999-999");
   $("#telefone").mask("(99)9999-9999");
   $("#celular_resp").mask("(99)99999-9999");
   $("#celular").mask("(99)99999-9999");
   $("#telefoneEmpresa").mask("(99)9999-9999");
   $("#cr_validade").mask("99/99/9999");
   $("#cnpj_clube").mask("99.999.999/9999-99");

 });
</script>
<script>
 $(document).ready(function(){
  $("div.alert").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
});
</script>
</body>
</html>
<?php
$_SESSION["SucessoCadastro"] = true;
?>