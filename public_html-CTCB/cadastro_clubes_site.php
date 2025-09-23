<?php
error_reporting(0);
session_start();

require_once("../intranet/sistema-ctcb/classes/metodosClass.php");
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();

if($_POST["Submit"] == "Salvar")
{
  $dados = array_filter($_POST);
  echo $metodos->cadastrarClubes($dados);
}
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>CTCB | Controle de Gestão</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $caminhoAbsoluto; ?>/css/style.css">
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="<?php echo $caminhoAbsoluto; ?>/js/validar-cep.js"></script>
  </head>
  <body>
   <div class="container">
     <div class="row header">
          <div class="col-md-6">
            <a href="<?php echo $caminhoAbsoluto; ?>/"><img src="<?php echo $caminhoAbsoluto; ?>/imagens/logo.png" alt="Logo da CTCB" class="logo"></a>
         </div>
         <div class="col-md-6 text-right">
           <h3>SISTEMA DE GESTÃO | CTCB</h3>
           <a href="<?php echo $caminhoAbsoluto; ?>/sair/" style="color: #000" alt="Sair do sistema" title="Sair do sistema"><i class="fas fa-power-off"></i> Sair do sistema</a>
         </div>
     </div>
     <div class="row conteudo">
       <nav class="col-md-12 navbar navbar-expand-lg navbar-light bg-light">
         <div class="collapse navbar-collapse navbar-right" id="navbarSupportedContent">
           <ul class="navbar-nav mr-auto">
             <li class="nav-item">
               <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/">Principal <span class="sr-only">(current)</span></a>
             </li>
             <li class="nav-item dropdown active">
               <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                 Cadastro
               </a>
               <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/atiradores/"><i class="fas fa-caret-right"></i> Atirador</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/categorias/"><i class="fas fa-caret-right"></i> Categoria</a>
                 <a class="dropdown-item active" href="<?php echo $caminhoAbsoluto; ?>/clubes/"><i class="fas fa-caret-right"></i> Clube</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/despachantes/"><i class="fas fa-caret-right"></i> Despachante</a>
                <!-- <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/estados/"><i class="fas fa-caret-right"></i> Estado</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/nacionalidade/"><i class="fas fa-caret-right"></i> Nacionalidade</a>
               -->
               <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/modalidades/"><i class="fas fa-caret-right"></i> Modalidade</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/provas/"><i class="fas fa-caret-right"></i> Prova</a>
               </div>
             </li>
             <li class="nav-item dropdown">
               <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                 Diários
               </a>
               <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/noticias/"><i class="fas fa-caret-right"></i> Notícias</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/enviar-emails/"><i class="fas fa-caret-right"></i> Envia E-mail</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/galeria-fotos/"><i class="fas fa-caret-right"></i> Galeria de Fotos</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/videos/"><i class="fas fa-caret-right"></i> Vídeo</a>
               </div>
             </li>
             <li class="nav-item dropdown">
               <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                 Campeonatos
               </a>
               <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/eventos/"><i class="fas fa-caret-right"></i> Evento</a>
               </div>
             </li>
             <li class="nav-item dropdown">
               <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                 Financeiro
               </a>
               <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/financeiro-pagos/"><i class="fas fa-caret-right"></i> Pagos</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/financeiro-pendentes/"><i class="fas fa-caret-right"></i> Pendentes</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/financeiro-pagos/"><i class="fas fa-caret-right"></i> Valor Mensalidade</a>
               </div>
             </li>
             <li class="nav-item dropdown">
               <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                 Utilitários
               </a>
               <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/cadastrar-usuarios/"><i class="fas fa-caret-right"></i> Usuários</a>
               </div>
             </li>
           </ul>
         </div>
       </nav>
      <div class="container" style="margin-top: 10px">
   <div class="row" style="margin-top: 10px">
     <div class="col-md-12">
       <div class="tituloCaixa">
         <i class="far fa-plus-square fa-lg"></i> Cadastrar Clube
       </div>
       <div style="margin-top:10px">
         <?php if($_SESSION["Sucesso"]){ ?>
            <div class="alert alert-success"><i class="fas fa-check"></i> Clube cadastrado com sucesso!</div>
         <?php } ?>
         <?php if($_SESSION["Erro"]){ ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Ops.. Tivemos um problema do lado de cá! Caso o erro persista, envie um e-mail para suporte.ctcb@gmail.com.</div>
         <?php } ?>
  <div class="row">
     <div class="col-md-8">
         <form method="post" action="#!">
           <div class="form-group">
             <label for="nome">Nome:</label>
             <input type="text" name="Nome" class="form-control" id="nome" value="">
           </div>
           <div class="form-group">
             <label for="sigla">Sigla:</label>
             <input type="text" name="Sigla" class="form-control" id="sigla" value="">
           </div>
           <div class="form-group">
             <label for="senha">Senha: <small>(Entre 6 e 12 caracteres)</small></label>
                   <input type="text" name="Senha" class="form-control senha-segura" id="senha" minlength="6" maxlength="12" required value="<?php echo $metodos->generatePassword(); ?>">
           </div>
              <div class="form-group">
                <label for="status">Status:</label>
                <select name="Status" class="form-control" id="status">
                  <option value="A">Ativo</option>
                  <option value="I">Inativo</option>
                </select>
              </div>
              <div class="form-group">
                <label for="telefoneEmpresa">Telefone:</label>
                  <input id="telefoneEmpresa" name="TelefoneComercial" type="text" class="form-control">
              </div>
              <div class="form-group">
                <label for="celularEmpresa">Celular:</label>
                  <input id="celular" name="TelefoneCelular" type="text" class="form-control">
              </div>
              <div class="form-group">
                <label for="email">Email:</label>
                  <input id="email" name="Email" type="email" class="form-control">
              </div>
              <div class="form-group">
                <label for="presidente">Presidente:</label>
                  <input id="presidente" name="Presidente" type="text" class="form-control">
              </div>
              <div class="form-group">
                <label for="data">Fim do mandato:</label>
                  <input id="data" name="FimMandato" type="text" class="form-control">
              </div>
              <div class="form-group">
                <label for="site">site:</label>
                  <input id="site" name="Site" type="text" class="form-control" placeholder="Ex.: https://www.site.com.br">
              </div>
             <div class="form-group">
                   <label for="cep" class="control-label">CEP:</label>
                   <input type="text" name="CEP" class="form-control" id="cep">
             </div>
             <div class="form-group">
                 <label for="rua" class="control-label">Endereço Completo:</label>
                 <input id="rua" name="Logradouro" type="text" class="form-control">
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
        <div align="center">
        <button type="submit" name="Submit" value="Salvar" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
      </div>
    </div>
   </form>
   </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-header">
          <i class="fas fa-users fa-lg"></i> Últimos atiradores cadastrados
        </div>
        <div class="card-body">
         <?php echo $metodos->listarClubesLimite($limite = 10); ?>
           <div class="text-center">
              <a href="<?php echo $caminhoAbsoluto; ?>/clubes/" class="btn btn-primary btn-sm">Ver todos</a>
           </div>
        </div>
      </div>
    </div>
  </div>
        </div>
     </div>
   </div>
 </div>
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
           $("#data").mask("99/99/9999");
           $("#crValidade").mask("99/99/9999");
       });
   </script>
   <script>
   $(document).ready(function(){
          $("div.alert").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
         });
   </script>
  </body>
</html>
