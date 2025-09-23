<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if($_SESSION["Logado"] == false){
  echo "<script>window.location.href='".$caminhoAbsoluto."/';</script>";
  exit();
}
if($_SESSION["Sucesso"] < time()){
  unset($_SESSION["Sucesso"]);
}
if($_SESSION["Erro"] < time()){
  unset($_SESSION["Erro"]);
}
$tabela = "atirador";
$idTabela = "atirador";
$idBusca = $_SESSION["IdUsuario"];
$visualizar = $metodos->visualizar($tabela,$idTabela,$idBusca);
if($_POST["Submit"] == "Alterar"){
  $dados = array_filter($_POST);
  echo $metodos->alterarDadosAtirador($dados);
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
    <style media="screen">
      .form-control{
        background-color: #FAFFBD;
      }
    </style>
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

                       //Preenche os campos com "..." enquanto consulta webservice.
                       $("#rua").val("...");
                       $("#bairro").val("...");
                       $("#cidade").val("...");
                       $("#uf").val("...");

                       //Consulta o webservice viacep.com.br/
                       $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

                           if (!("erro" in dados)) {
                               //Atualiza os campos com os valores da consulta.
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
          <a href="<?php echo $caminhoAbsoluto; ?>/"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" alt="" class="logo img-fluid"></a>
        </figure>
      </div>
      <div class="col-md-8">
        <div class="row offset-md-8">
          <div class="menu-superior"><a href="#">Principal</a> &nbsp; <a href="#">Contato</a> &nbsp; <a href="#">Localização</a></div>
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
              <a class="nav-link" href="#">Importação</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Caçadas</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Curso de Instrutor</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Assessoria Jurídica</a>
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
              <a class="nav-link" href="#">Atletas</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Clubes</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Regularmento</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Calendário</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Resultados</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Instrutores</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Notícias</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Fotos</a>
            </li>
            <span class="linha-vertical"></span>
            <li class="nav-item">
              <a class="nav-link" href="#">Vídeos</a>
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
        <h3 style="color: #3e4095; font-weight: bold"><i class="fas fa-user"></i> ALTERAR CADASTRO</h3>
        <?php
list($anoCadastro,$mesCadastro,$diaCadastro) = explode("-",$visualizar[1]->data_cadastro);
        $dataCadastro = $diaCadastro."/".$mesCadastro."/".$anoCadastro;
        list($anoNascimento,$mesNascimento,$diaNascimento) = explode("-",$visualizar[1]->data_nascimento);
        $dataNascimento = $diaNascimento."/".$mesNascimento."/".$anoNascimento;
        $codigo = $visualizar[1]->codigo;
        $nome = $new_string = mb_convert_case($visualizar[1]->nome, MB_CASE_TITLE, 'UTF-8');
        $cpf = substr($visualizar[1]->cpf,0,3).".".substr($visualizar[1]->cpf,3,3).".".substr($visualizar[1]->cpf,6,3)."-".substr($visualizar[1]->cpf,9,2);
        $rg = $visualizar[1]->identidade;
        $rgOrgao = $visualizar[1]->identidade_orgao;
        list($anoRG,$mesRG,$diaRG) = explode("-",$visualizar[1]->identidade_emissao);
        $dataEmissao = $diaRG."/".$mesRG."/".$anoRG;
        $cr = $visualizar[1]->cr;
        list($anoCR,$mesCR,$diaCR) = explode("-",$visualizar[1]->cr_validade);
        $dataCR = $diaCR."/".$mesCR."/".$anoCR;
        ?>
        <div class="alert alert-info">
          <strong>Data de cadastro:</strong> <?php echo $dataCadastro; ?><br>
          <strong>Código:</strong> <?php echo $codigo; ?><br>
          <strong>Nome:</strong> <?php echo $nome; ?><br>
          <strong>CPF:</strong> <?php echo $cpf; ?><br>
          <strong>RG:</strong> <?php echo $rg; ?> - <strong>Órgão Emissor:</strong> <?php echo $rgOrgao; ?> - <strong>Data de Emissão:</strong> <?php echo $dataEmissao; ?><br>
          <strong>CR:</strong> <?php echo $cr; ?> - <strong>Validade:</strong> <?php echo $dataCR; ?><br>
          <strong>Anuidade: </strong> Confederado - R$ 230,00 - Anual
        </div>
           <?php if($erro == true){ ?>
             <div class="alert alert-danger alerta"><i class="fas fa-exclamation-triangle"></i> CPF inválido! Favor digitar seu CPF corretamente!</div>
           <?php } ?>
           <?php if($_SESSION["Sucesso"]){ ?>
               <div class="alert alert-success alerta"><i class="fas fa-check"></i> Alteração efetuada com sucesso!</div>
           <?php } ?>
           <?php if($_SESSION["Erro"]){ ?>
               <div class="alert alert-danger alerta"><i class="fas fa-exclamation-triangle"></i> Nenhum dado foi alterado.</div>
           <?php } ?>
        <form method="post" action="#!">
           <h5 style="font-weight: bold"><i class="fas fa-key"></i> DADOS DE ACESSO</h5>
           <div class="form-group">
             <label for="email">E-mail:</label>
             <input type="text" name="Email" class="form-control" id="email" value="<?php echo $visualizar[1]->email; ?>">   
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
             <label for="email">Senha: <small>(Entre 6 e 12 caracteres)</small></label>
             <div class="input-group">
                   <input type="password" name="Senha" class="form-control senha-segura" id="senha" minlength="6" maxlength="12">
                   <span class="input-group-btn" id="btn-addon2">
                           <button type="button" class="btn btn-info addon-btn waves-effect waves-light" id="botaoSenha" title="Mostrar senha" onclick="mostrarSenha()">
                             <i id="ver" class="far fa-eye fa-lg"></i>
                           </button>
                       </span>
               </div>
           </div>
           <h5 style="font-weight: bold; margin-top: 15px"><i class="fas fa-user-edit"></i> DADOS PESSOAIS</h5>
           <div class="form-group">
             <label for="nome">Gênero:</label>
           <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="Genero" value="Masculino" id="inlineCheckbox1" <?php if($visualizar[1]->sexo == 'M'){ echo "checked"; } ?> >
            <label class="form-check-label" for="inlineCheckbox1">Masculino</label>
            </div>
            <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="Genero" value="Feminino" id="inlineCheckbox2" <?php if($visualizar[1]->sexo == 'F'){ echo "checked"; } ?>>
            <label class="form-check-label" for="inlineCheckbox2">Feminino</label>
            </div>
          </div>
         <div class="form-group">
           <?php list($anoNascimento,$mesNascimento,$diaNascimento) = explode("-",$visualizar[1]->data_nascimento); ?>
           <label for="nome">Data de Nascimento:</label>
                       <div class="form-row mb-4">
                          <div class="col">
                                <select class="form-control" name="DiaNascimento">
                                  <option value="">Dia</option>
                                 <?php
                                     for($dia = 1; $dia <= 31; $dia++){ $dia = ($dia < 10)?'0'.$dia:$dia; ?>
                                          <option value="<?php echo $dia; ?>" <?php if($diaNascimento == $dia) echo "selected"; ?>><?php echo $dia; ?></option>
                                 <?php } ?>
                                </select>
                          </div>
                          <div class="col">
                            <select class="form-control" name="MesNascimento">
                                 <option value="">Mês</option>
                                 <option value="01" <?php if($mesNascimento == '01') echo "selected"; ?>>Janeiro</option>
                                 <option value="02" <?php if($mesNascimento == '02') echo "selected"; ?>>Fevereiro</option>
                                 <option value="03" <?php if($mesNascimento == '03') echo "selected"; ?>>Março</option>
                                 <option value="04" <?php if($mesNascimento == '04') echo "selected"; ?>>Abril</option>
                                 <option value="05" <?php if($mesNascimento == '05') echo "selected"; ?>>Maio</option>
                                 <option value="06" <?php if($mesNascimento == '06') echo "selected"; ?>>Junho</option>
                                 <option value="07" <?php if($mesNascimento == '07') echo "selected"; ?>>Julho</option>
                                 <option value="08" <?php if($mesNascimento == '08') echo "selected"; ?>>Agosto</option>
                                 <option value="09" <?php if($mesNascimento == '09') echo "selected"; ?>>Setembro</option>
                                 <option value="10" <?php if($mesNascimento == '10') echo "selected"; ?>>Outubro</option>
                                 <option value="11" <?php if($mesNascimento == '11') echo "selected"; ?>>Novembro</option>
                                 <option value="12" <?php if($mesNascimento == '12') echo "selected"; ?>>Dezembro</option>
                            </select>
                          </div>
                          <div class="col">
                            <?php if($visualizar[1]->cpf == ""){ ?>
                             <select class="form-control" name="AnoNascimento">
                              <option value="">Ano</option>
                              <?php for($ano = date("Y") - 18; $ano <= date("Y"); $ano++){ ?>
                                <option value="<?php echo $ano; ?>" <?php if($anoNascimento == $ano) echo "selected"; ?>><?php echo $ano; ?></option>
                              <?php } ?>
                            </select>
                          <?php }else{ ?>
                            <select class="form-control" name="AnoNascimento">
                             <option value="">Ano</option>
                             <?php for($ano = 1900; $ano <= date("Y"); $ano++){ ?>
                               <option value="<?php echo $ano; ?>" <?php if($anoNascimento == $ano) echo "selected"; ?>><?php echo $ano; ?></option>
                             <?php } ?>
                            </select>
                          <?php } ?>
                          </div>
                       </div>
            </div>
            <div class="form-group">
              <label for="telefone">Telefone:</label>
                <input name="Telefone" type="text" class="form-control" id="telefone" value="<?php echo $visualizar[1]->telefone_residencia; ?>">
            </div>
            <div class="form-group">
              <label for="celular">Celular:</label>
                <input name="Celular" type="text" class="form-control" id="celular" value="<?php echo $visualizar[1]->celular; ?>">
            </div>
            <div class="form-group">
              <label for="nomeMae">Nome da mãe:</label>
                <input name="NomeMae" type="text" class="form-control" id="nomeMae"  value="<?php echo $visualizar[1]->nome_mae; ?>">
            </div>
            <div class="form-group">
              <label for="nomePai">Nome do pai:</label>
                <input name="NomePai" type="text" class="form-control" id="nomePai" value="<?php echo $visualizar[1]->nome_pai; ?>">
            </div>
            <div class="form-group">
              <label for="telefone">Estado Civil:</label>
              <select name="EstadoCivil" class="form-control">
                <option value="">Selecione uma opção</option>
                <option value="Solteiro(a)" <?php if($visualizar[1]->estado_civil == "Solteiro(a)") echo "selected"; ?>>Solteiro</option>
                <option value="Casado(a)" <?php if($visualizar[1]->estado_civil == "Casado(a)") echo "selected"; ?>>Casado(a)</option>
                <option value="Viúvo(a)" <?php if($visualizar[1]->estado_civil == "Viúvo(a)") echo "selected"; ?>>Viúvo(a)</option>
                <option value="Divorciado(a)" <?php if($visualizar[1]->estado_civil == "Divorciado(a)") echo "selected"; ?>>Divorciado(a)</option>
                <option value="União Estável" <?php if($visualizar[1]->estado_civil == "União Estável") echo "selected"; ?>>União Estável</option>
              </select>
            </div>
            <div class="form-group">
                  <label for="cep" class="control-label">CEP: <span style="color: red">*</span></label>
                  <input type="text" name="CEP" class="form-control" id="cep" data-inputmask="'alias': '99999-999'" required="required" value="<?php echo $visualizar[1]->cep; ?>">
            </div>
            <div class="form-group">
                <label for="rua" class="control-label">Endereço completo: <span style="color: red">*</span></label>
                <input id="rua" name="Logradouro" type="text" class="form-control" required="required" value="<?php echo $visualizar[1]->endereco; ?>">
            </div>
            <div class="form-group">
              <label for="bairro" class="control-label">Bairro: <span style="color: red">*</span></label>
                <input id="bairro" name="Bairro" type="text" class="form-control" required="required" value="<?php echo $visualizar[1]->bairro; ?>">
            </div>
            <div class="form-group">
              <label for="cidade">Cidade:</label>
                <input id="cidade" name="Cidade" type="text" class="form-control" readonly value="<?php echo $visualizar[1]->cidade; ?>">
            </div>
            <?php
            $tabelaEstado = 'estado';
            $idTabelaEstado = 'estado';
            $idBuscaEstado = $visualizar[1]->estado;
            $visualizarEstado = $metodos->visualizar($tabelaEstado,$idTabelaEstado,$idBuscaEstado);
            ?>
            <div class="form-group">
              <label for="estado">Estado:</label>
                <input id="uf" name="Estado" type="text" class="form-control" readonly value="<?php echo $visualizarEstado[1]->sigla; ?>">
            </div>
            <div class="form-group">
              <label for="estado">Nacionalidade:</label>
                <?php echo $metodos->listarNacionalidade($buscar = $visualizar[1]->nacionalidade); ?>
            </div>
            <div class="form-group">
              <label for="estado">Naturalidade:</label>
                <?php echo $metodos->listarNaturalidade($buscar = $visualizar[1]->naturalidade); ?>
            </div>

           <h5 style="font-weight: bold"><i class="fas fa-building"></i> DADOS COMERCIAIS</h5>
           <div class="form-group">
               <label for="profissao">Profissão:</label>
               Pro<?php echo $visualizar[1]->profissao; ?>
               <input id="profissao" name="Profissao" type="text" class="form-control" value="<?php echo $visualizar[1]->profissao; ?>">
           </div>
           <div class="form-group">
             <label for="telefoneEmpresa">Telefone:</label>
               <input id="telefoneEmpresa" name="TelefoneComercial" type="text" class="form-control" value="<?php echo $visualizar[1]->telefone_comercial; ?>">
           </div>
           <h5 style="font-weight: bold"><i class="fas fa-list-ol"></i> ATIVIDADES</h5>
           <div class="form-group">
             <?php
             if($visualizar[1]->cpf == ""){
               $disabled = "disabled";
             }else{
               $disabled = "";
             }
             ?>
             <label for="atleta">Atleta:</label>
             <select name="Atleta" class="form-control" <?php echo $disabled; ?> id="atleta">
               <option value="Sim" <?php if($visualizar[1]->atleta == 'S') echo "selected"; ?>>Sim</option>
               <option value="Não" <?php if($visualizar[1]->atleta == 'N') echo "selected"; ?>>Não</option>
             </select>
           </div>
           <div class="form-group">
             <label for="arbitro">Árbitro:</label>
             <select name="Arbitro" class="form-control" <?php echo $disabled; ?> id="arbitro">
               <option value="Sim" <?php if($visualizar[1]->arbitro == 'S') echo "selected"; ?>>Sim</option>
               <option value="Não" <?php if($visualizar[1]->arbitro == 'N') echo "selected"; ?>>Não</option>
             </select>
           </div>
           <div class="form-group">
             <label for="colecionador">Colecionador:</label>
             <select name="Colecionador" class="form-control" <?php echo $disabled; ?> id="colecionador">
               <option value="Sim" <?php if($visualizar[1]->colecionador == 'S') echo "selected"; ?>>Sim</option>
               <option value="Não" <?php if($visualizar[1]->colecionador == 'N') echo "selected"; ?>>Não</option>
             </select>
           </div>
           <div class="form-group">
             <label for="cacador">Caçador:</label>
             <select name="Caçador" class="form-control" <?php echo $disabled; ?> id="cacador">
               <option value="Sim" <?php if($visualizar[1]->cacador == 'S') echo "selected"; ?>>Sim</option>
               <option value="Não" <?php if($visualizar[1]->cacador == 'N') echo "selected"; ?>>Não</option>
             </select>
           </div>
           <div class="form-group">
             <div class="form-row mb-4">
                <div class="col">
             <label for="cacador">Recarga:</label>
             <select name="Caçador" class="form-control" <?php echo $disabled; ?> id="cacador">
               <option value="Sim" <?php if($visualizar[1]->recarga == 'S') echo "selected"; ?>>Sim</option>
               <option value="Não" <?php if($visualizar[1]->recarga == 'S') echo "selected"; ?>>Não</option>
             </select>
            </div>
            <div class="col">
               <label for="dies">Dies:</label>
              <input type="text" name="Dies" class="form-control" value="<?php echo $visualizar[1]->dies; ?>" <?php echo $disabled; ?> id="dies">
            </div>
           </div>
         </div>
       <!--
           <h5 style="font-weight: bold"><i class="fas fa-file-alt"></i> DOCUMENTOS</h5>
           <div class="form-group">
             <div class="form-row mb-4">
              <div class="col">
                 <label for="identidade">Identidade:</label>
                 <input id="identidade" name="Identidade" type="text" class="form-control" value="<?php echo $visualizar[1]->identidade; ?>">
              </div>
              <div class="col">
                <label for="orgaoEmissor">Órgão Emissor:</label>
                <input id="orgaoEmissor" name="OrgaoEmissor" type="text" class="form-control" value="<?php echo $visualizar[1]->identidade_orgao; ?>">
              </div>
              <div class="col">
                <?php list($anoEmissaoRG,$mesEmissaoRG,$diaEmissaoRG) = explode("-",$visualizar[1]->identidade_emissao); ?>
                <label for="dataEmissao">Data de Emissão:</label>
                <input id="dataEmissao" name="DataEmissao" type="text" class="form-control" value="<?php echo $diaEmissaoRG.$mesEmissaoRG.$anoEmissaoRG; ?>">
              </div>
           </div>
         </div>
       -->
         <div class="form-group">
           <div class="form-row mb-4">
            <div class="col">
               <label for="cr">CR:</label>
               <input id="cr" name="CR" type="text" class="form-control" value="<?php echo $visualizar[1]->cr; ?>">
            </div>
            <div class="col">
              <?php list($anoValidadeCR,$mesValidadeCR,$diaValidadeCR) = explode("-",$visualizar[1]->cr_validade); ?>
              <label for="crValidade">Validade:</label>
              <input id="crValidade" name="CRValidade" type="text" class="form-control" value="<?php echo $diaValidadeCR.$mesValidadeCR.$anoValidadeCR; ?>">
            </div>
         </div>
       </div>    
          <div class="form-group">
           <div align="center">
           <button type="submit" name="Submit" value="Alterar" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
         </div>
       </div>
      </form>
      </div>
      <div class="col-md-4 col-xs-12">

        <!-- Menu Lateral -->
         <?php include("menu-logado.php"); ?>
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
         Principal  |  Atletas  |  Clubes  |  Instrutores  |  Regulamento  |  Calendário  |  Notícias  |  Fotos  |  Vídeos  |  Importação  |  Contato  |  Localização<br><br>
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
   <script src="<?php echo $caminhoAbsoluto; ?>/js/bootstrap.min.js"></script>
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
          $("div.alerta").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
         });
   </script>
  </body>
</html>
<?php
  $_SESSION["SucessoCadastro"] = true;
?>