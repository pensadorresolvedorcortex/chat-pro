<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
$dados = false;
if($_POST["Submit"] == "Cadastrar"){
  $dados = array_filter($_POST);
  echo $metodos->cadastrarDadosAtirador($dados);
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
     <div class="container">
     <div class="menu">
      <div class="row">
        <div class="col-md-4">
         <figure>
           <a href="<?php echo $caminhoAbsoluto; ?>/" title="Voltar para a página inicial">
          <img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" alt="" class="logo">
          </a>
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
        <h3 style="color: #3e4095; font-weight: bold">CADASTRO DE ASSOCIADO</h3>
        <br><br>
     <!--   <div class="text-center">
        <h3>EM MANUTENÇÃO</h3>
        </div> -->

      

        
    
       <?php if(!isset($_SESSION["CPF"])){ ?>
        <div class="alert alert-info">
          <i class="fas fa-exclamation-triangle"></i> <strong>ATENÇÃO:</strong><br><br>
          Você optou por prosseguir sem o CPF. Esse tipo de cadastro só é permitido para menores de 18 anos não possuidores de documento.
        </div>
        <h5 style="font-weight: bold; color: #B22222">Acesso sem CPF</h5>
     <?php }else{ ?>
        <h5 style="font-weight: bold; color: #000; margin-top: 20px">CPF: <?php echo $_SESSION["CPF"]; ?></h5>
      <?php } ?>
           <div style="margin-top: 20px">
             <?php if($erro == true){ ?>
             <div class="alert alert-danger" id="erro"><i class="fas fa-exclamation-triangle"></i> CPF inválido! Favor digitar seu CPF corretamente!</div>
           <?php } ?>
            <form method="post" action="#!">
              <?php if($_SESSION["CPF"]){ ?>
                <input type="hidden" name="CPF" value="<?php echo $_SESSION["CPF"]; ?>">
              <?php } ?>
               <div class="form-group">
                 <label for="nome">Tipo de anuidade:</label>
                 <select name="TipoAnuidade" class="form-control" id="tipoAnuidade">
                   <option value="Confederado">Confederado - R$ 230,00 - Anual</option>
                 </select>
               </div>
               <h5 style="font-weight: bold"><i class="fas fa-user-tie"></i> DESPACHANTE</h5>
               <div class="form-group">
               <select name="Despachante" id="Despachante" class="form-control">
                  <option value="">Selecione o despachante</option>
                  <?php echo $metodos->selectDespachantes(); ?>
               </select>
               </div>
               <h5 style="font-weight: bold"><i class="fas fa-key"></i> DADOS DE ACESSO</h5>
               <div class="form-group">
                 <label for="email">E-mail:<span style="color: red">*</span></label>
                 <input type="email" name="Email" class="form-control" id="Email" required> 
   
               </div>
               <div>
                  <!--VALIDAÇÃO EMAIL-->
                <?php if($_SESSION["SucessoCadastro"]==false) { ?>
                  <span class="alert alert-danger">Desculpe, esse e-mail já está sendo usado por outro usuário<span>
                <?php } ?>  
                  <!--FIM VALIDAÇÃO EMAIL-->                          
               </div>
               <br>
               <div class="form-group">
                 <label for="email">Senha:<span style="color: red">*</span> <small>(Entre 6 e 12 caracteres)</small></label>
                 <div class="input-group">
                       <input type="password" name="Senha" class="form-control senha-segura" id="Senha" minlength="6" maxlength="12" required>
                       <span class="input-group-btn" id="btn-addon2">
                               <button type="button" class="btn btn-info addon-btn waves-effect waves-light" id="botaoSenha" title="Mostrar senha" onclick="mostrarSenha()">
                                 <i id="ver" class="far fa-eye fa-lg"></i>
                               </button>
                           </span>
                   </div>
               </div>
               <h5 style="font-weight: bold"><i class="fas fa-user-edit"></i> DADOS PESSOAIS</h5>
               <div class="form-group">
                 <label for="nome">Nome:<span style="color: red">*</span></label>
                 <input type="text" name="Nome" class="form-control" id="Nome" required>
               </div>
               <div class="form-group">
                 <label for="nome">Gênero:</label>
               <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="Genero" value="Masculino" id="inlineCheckbox1" checked>
                <label class="form-check-label" for="inlineCheckbox1">Masculino</label>
                </div>
                <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="Genero" value="Feminino" id="inlineCheckbox2">
                <label class="form-check-label" for="inlineCheckbox2">Feminino</label>
                </div>
              </div>
             <div class="form-group">
               <label for="nome">Data de Nascimento:</label>
                           <div class="form-row mb-4">
                              <div class="col">
                                    <select class="form-control" name="DiaNascimento" id="DiaNascimento">
                                      <option value="">Dia</option>
                                     <?php for($dia = 1; $dia <= 31; $dia++){ $dia = ($dia < 10)?'0'.$dia:$dia; ?>
                                              <option value="<?php echo $dia; ?>"><?php echo $dia; ?></option>
                                     <?php } ?>
                                    </select>
                              </div>
                              <div class="col">
                                <select class="form-control" name="MesNascimento" id="MesNascimento">
                                     <option value="">Mês</option>
                                     <option value="1">Janeiro</option>
                                     <option value="2">Fevereiro</option>
                                     <option value="3">Março</option>
                                     <option value="4">Abril</option>
                                     <option value="5">Maio</option>
                                     <option value="6">Junho</option>
                                     <option value="7">Julho</option>
                                     <option value="8">Agosto</option>
                                     <option value="9">Setembro</option>
                                     <option value="10">Outubro</option>
                                     <option value="11">Novembro</option>
                                     <option value="12">Dezembro</option>
                                </select>
                              </div>
                              <div class="col">
                                <?php if(!isset($_SESSION["CPF"])){ ?>
                                 <select class="form-control" name="AnoNascimento" id="AnoNascimento">
                                  <option value="">Ano</option>
                                  <?php for($ano = date("Y") - 18; $ano <= date("Y"); $ano++){ ?>
                                    <option value="<?php echo $ano; ?>"><?php echo $ano; ?></option>
                                  <?php } ?>
                                </select>
                              <?php }else{ ?>
                                <select class="form-control" name="AnoNascimento" id="AnoNascimento">
                                 <option value="">Ano</option>
                                 <?php for($ano = 1900; $ano <= date("Y"); $ano++){ ?>
                                   <option value="<?php echo $ano; ?>"><?php echo $ano; ?></option>
                                 <?php } ?>
                                </select>
                              <?php } ?>
                              </div>
                           </div>
                </div>
                <div class="form-group">
                  <label for="telefone">Telefone:</label>
                    <input name="Telefone" type="text" class="form-control" id="Telefone">
                </div>
                <div class="form-group">
                  <label for="celular">Celular:<span style="color: red">*</span></label>
                    <input name="Celular" type="text" class="form-control" id="Celular" required>
                </div>
                <div class="form-group">
                  <label for="nomeMae">Nome da mãe:</label>
                    <input name="NomeMae" type="text" class="form-control" id="NomeMae">
                </div>
                <div class="form-group">
                  <label for="nomePai">Nome do pai:</label>
                    <input name="NomePai" type="text" class="form-control" id="NomePai">
                </div>
                <div class="form-group">
                  <label for="telefone">Estado Civil:</label>
                  <select name="EstadoCivil" class="form-control">
                    <option value="">Selecione uma opção</option>
                    <option value="Solteiro">Solteiro</option>
                    <option value="Casado">Casado</option>
                    <option value="Viúvo">Viúvo</option>
                    <option value="União Estável">União Estável</option>
                  </select>
                </div>
                <div class="form-group">
                      <label for="cep" class="control-label">CEP:<span style="color: red">*</span></label>
                      <input type="text" name="CEP" class="form-control" id="CEP" data-inputmask="'alias': '99999-999'" required>
                </div>
               

                               <div class="form-group">
                    <label for="rua" class="control-label">Endereço Completo:<span style="color: red">*</span></label>
                    <input id="rua" name="Logradouro" type="text" class="form-control" required>
                </div>
                <div class="form-group">
                  <label for="bairro" class="control-label">Bairro: <span style="color: red">*</span></label>
                    <input id="bairro" name="Bairro" type="text" class="form-control" required>
                </div>
                <div class="form-group">
                  <label for="cidade">Cidade:</label>
                    <input id="cidade" name="Cidade" type="text" class="form-control">
                </div>
                <div class="form-group">
                  <label for="estado">Estado:</label>
                    <input id="uf" name="Estado" type="text" class="form-control">
                </div>


<!--
  
                <div class="form-group">
                    <label for="rua" class="control-label">Endereço Completo:<span style="color: red">*</span></label>
                    <input id="Logradouro" name="Logradouro" type="text" class="form-control" required>
                </div>
                <div class="form-group">
                  <label for="bairro" class="control-label">Bairro: <span style="color: red">*</span></label>
                    <input id="Bairro" name="Bairro" type="text" class="form-control" required>
                </div>
                <div class="form-group">
                  <label for="cidade">Cidade:</label>
                    <input id="Cidade" name="Cidade" type="text" class="form-control" readonly>
                </div>
                <div class="form-group">
                  <label for="estado">Estado:</label>
                    <input id="Estado" name="Estado" type="text" class="form-control" readonly>
                </div>
 
 -->
 
                <div class="form-group">
                  <label for="estado">Nacionalidade:<span style="color: red">*</span></label>
                    <?php echo $metodos->listarNacionalidade($buscar = null); ?>
                </div>
                 <div class="form-group">
                  <label for="estado">Naturalidade:<span style="color: red">*</span></label>
                    <?php echo $metodos->listarNaturalidade($buscar = null); ?>
                </div>
               <h5 style="font-weight: bold"><i class="fas fa-building"></i> DADOS COMERCIAIS</h5>
               <div class="form-group">
                   <label for="profissao">Profissão:</label>
                   <input id="Profissao" name="Profissao" type="text" class="form-control">
               </div>
               <div class="form-group">
                 <label for="telefoneEmpresa">Telefone:</label>
                   <input id="TelefoneComercial" name="TelefoneComercial" type="text" class="form-control">
               </div>
               <h5 style="font-weight: bold"><i class="fas fa-list-ol"></i> ATIVIDADES</h5>
               <div class="form-group">
                 <?php
                 if(!isset($_SESSION["CPF"])){
                   $disabled = "disabled";
                   $selectedSim = "selected";
                 }else{
                   $disabled = "";
                   $selectedNao = "selected";
                 }
                 ?>

                <label for="atleta">Atleta:</label>
                 <select name="Atleta" class="form-control" <?php echo $disabled; ?> id="atleta">
                   <option value="Sim" <?php echo $selectedSim; ?>>Sim</option>
                   <option value="Não" <?php echo $selectedNao; ?>>Não</option>
                 </select>
               </div>
               <div class="form-group">
                 <label for="instrutor">Instrutor:</label>
                 <select name="Instrutor" class="form-control" <?php echo $disabled; ?> id="instrutor">
                   <option value="Sim">Sim</option>
                   <option value="Não" selected>Não</option>
                 </select>
               </div>
               <div class="form-group">
                 <label for="arbitro">Árbitro:</label>
                 <select name="Arbitro" class="form-control" <?php echo $disabled; ?> id="arbitro">
                   <option value="Sim">Sim</option>
                   <option value="Não" selected>Não</option>
                 </select>
               </div>
               <div class="form-group">
                 <label for="colecionador">Colecionador:</label>
                 <select name="Colecionador" class="form-control" <?php echo $disabled; ?> id="colecionador">
                   <option value="Sim">Sim</option>
                   <option value="Não" selected>Não</option>
                 </select>
               </div>
               <div class="form-group">
                 <label for="cacador">Caçador:</label>
                 <select name="Caçador" class="form-control" <?php echo $disabled; ?> id="cacador">
                   <option value="Sim">Sim</option>
                   <option value="Não" selected>Não</option>
                 </select>
               </div>
               <div class="form-group">
                 <div class="form-row mb-4">
                    <div class="col">
                 <label for="cacador">Recarga:</label>
                 <select name="Caçador" class="form-control" <?php echo $disabled; ?> id="cacador">
                   <option value="Sim">Sim</option>
                   <option value="Não" selected>Não</option>
                 </select>
                </div>
                <div class="col">
                   <label for="dies">Dies:</label>
                  <input type="text" name="Dies" class="form-control" value="" <?php echo $disabled; ?> id="dies">
                </div>
               </div>
             </div>
               <h5 style="font-weight: bold"><i class="fas fa-file-alt"></i> DOCUMENTOS</h5>
               <div class="form-group">
                 <div class="form-row mb-4">
                  <div class="col">
                     <label for="identidade">Identidade:</label>
                     <input id="identidade" name="Identidade" type="text" class="form-control">
                  </div>
                  <div class="col">
                    <label for="orgaoEmissor">Órgão Emissor:</label>
                    <input id="orgaoEmissor" name="OrgaoEmissor" type="text" class="form-control">
                  </div>
                  <div class="col">
                    <label for="dataEmissao">Data de Emissão:</label>
                    <input id="dataEmissao" name="DataEmissao" type="text" class="form-control">
                  </div>
               </div>
             </div>
             <div class="form-group">
               <div class="form-row mb-4">
                <div class="col">
                   <label for="cr">CR:</label>
                   <input id="cr" name="CR" type="text" class="form-control">
                </div>
                <div class="col">
                  <label for="crValidade">Validade:</label>
                  <input id="crValidade" name="CRValidade" type="text" class="form-control">
                </div>
             </div>
           </div>
              <div class="form-group">
               <div align="center">
               <button type="submit" name="Submit" value="Cadastrar" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
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
          <?php require_once("footer.php"); ?>
    </footer>
  </div>
  </div>
   <script src="<?php echo $caminhoAbsoluto; ?>/js/bootstrap.min.js"></script>
   <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.maskedinput-master/dist/jquery.maskedinput.js" type="text/javascript"></script>
   <script type="text/javascript">
       $(function() {
           $.mask.definitions['~'] = "[+-]";
           $("#cpf").mask("999.999.999-99");
           $("#cep").mask("99999-999");
           $("#telefone").mask("(99)9999-9999");
           $("#celular").mask("(99)99999-9999");
           $("#telefoneEmpresa").mask("(99)9999-9999");
           $("#dataEmissao").mask("99/99/9999");
           $("#crValidade").mask("99/99/9999");
       });
   </script>
   <script>
   $(document).ready(function(){
          $("#erro").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
    });
   </script>
   <script>
   function mostrarSenha(){
   	var botao = document.getElementById("senha");
   	if(botao.type == "password"){
   		botao.type = "text";
       document.getElementById("ver").className="far fa-eye-slash fa-lg";
       document.getElementById("botaoSenha").title="Esconder senha";
   	}else{
   		botao.type = "password";
       document.getElementById("ver").className="far fa-eye fa-lg";
       document.getElementById("botaoSenha").title="Mostrar senha";
   	}
   }
   </script>
   <!-- Popular dados -->
   <script>
   $(document).ready(function(){
   <?php
     if( $dados ) {
        foreach ($dados as $key => $value) {
          echo "$('#{$key}').val('{$value}');";
        }
     }
   ?>
    });
   </script>
   <!-- fim popular -->
   </body>
</html>
<?php
  $_SESSION["SucessoCadastro"] = true;
?>