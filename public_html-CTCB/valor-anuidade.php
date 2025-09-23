<?php
error_reporting(0);
session_start();
if($_SESSION["LogadoCTCB"] == false){
  echo "<script>window.location.href='../index.php';</script>";
  exit();
}
require_once("classes/metodosClass.php");
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>CTCB RJ | Sistema de Gestão</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $caminhoAbsoluto; ?>/css/style.css">
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="<?php echo $caminhoAbsoluto; ?>/js/mascaras.js"></script>
  </head>
  <body>
   <div class="container">
     <div class="row header">
          <div class="col-md-6">
            <a href="<?php echo $caminhoAbsoluto; ?>/"><img src="<?php echo $caminhoAbsoluto; ?>/imagens/logo.png" alt="Logo da CTCB" class="logo"></a>
         </div>
         <div class="col-md-6 text-right">
           <h3>SISTEMA DE GESTÃO | CTCBRJ</h3>
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
                 <a class="dropdown-item active" href="<?php echo $caminhoAbsoluto; ?>/atiradores/"><i class="fas fa-caret-right"></i> Sócio</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/categorias/"><i class="fas fa-caret-right"></i> Categoria</a>
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/despachantes/"><i class="fas fa-caret-right"></i> Despachante</a>
                 <!--
                 <a class="dropdown-item" href="<?php echo $caminhoAbsoluto; ?>/estados/"><i class="fas fa-caret-right"></i> Estado</a>
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
         <i class="fas fa-list fa-lg"></i> Valor da Anuidade
       </div>
       <?php if($_SESSION["Sucesso"]){ ?>
       <div class="alert alert-success">Anuidade cadastrada com sucesso</div>
       <?php } ?>
       <?php if($_SESSION["Erro"]){ ?>
       <div class="alert alert-danger">Essa anuidade já consta cadastrada</div>
       <?php } ?>
       <?php if($_SESSION["SucessoExcluir"]){ ?>
       <div class="alert alert-danger">Anuidade excluída com sucesso</div>
       <?php } ?>
       <?php if($_SESSION["ErroExcluir"]){ ?>
       <div class="alert alert-danger">Ops... tivemos algum problema do lado de cá. Se o erro persistir, entre em contato com suporte@pertuttigestao.com.br</div>
       <?php } ?>
       <div style="margin-top:10px">
          <div class="row">
            <div class="col-md-7">
                <form method="post" action="#!">
                  <h5 style="font-weight: bold">ANUIDADE</h5>
                  <div class="form-group">
                    <label for="email">Nome:</label>
                    <input type="text" name="Nome" class="form-control" id="nome" value="">
                  </div>
                  <div class="form-group">
                    <label for="email">Sigla:</label>
                    <input type="text" name="ValorAnuidade" class="form-control col-md-4" id="valorAnuidade" value="">
                  </div>           
              <div class="form-group">
                <div align="center">
                  <?php if($_SESSION["Cadastrar"] == '1'){ ?>
                      <button type="button" name="Submit" value="Cadastrar" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                <?php } ?>
              </div>
            </div>
          </form>
          </div>
            <div class="col-md-5">
              <div class="card">
                <div class="card-header">
                  <i class="fas fa-check-double"></i> Anuidade(s) cadastrada(s)
                </div>
                <div class="card-body">
                  <table class="table table-striped">
                    <tr>
                      <td scope="col" style="font-size: 13px; text-align: center; font-weight: bold; background-color:#003366; color: #FFF">Nome</td>
                      <td scope="col" style="font-size: 13px; text-align: center; font-weight: bold; background-color:#003366; color: #FFF">Valor</td>
                      <td scope="col" style="font-size: 13px; text-align: center; font-weight: bold; background-color:#003366; color: #FFF">Excluir</td>
                    </tr>
                      <?php echo $metodos->visualizarAnuidade(); ?>
                  </table>
                
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
   <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.maskMoney.js" type="text/javascript"></script>
    <script type="text/javascript">
       $("#valorAnuidade").maskMoney({symbol:'R$ ', thousands:'.', decimal:',', symbolStay: true});
    </script>
    <script>
    $(document).ready(function(){
           $("div.alert").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
          });
    </script>

  </body>
</html>
