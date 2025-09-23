<?php
error_reporting(0);
session_start();
require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();
if($_POST["Submit"] == "Alterar"){
  $novaSenha = $_POST["NovaSenha"];
  echo $metodos->alterarSenhaAtirador($novaSenha);
}
if($_SERVER["QUERY_STRING"]){
  list($c,$n) = explode("num=",$_SERVER["QUERY_STRING"]);
  $num = $n;
  list($codigo,$usuario) = explode("_",$c);
  $tabelaC = "declaracao";
  $idTabelaC = "autenticacao";
  $idBuscaC = $codigo;
  $visualizarC = $metodos->visualizar($tabelaC,$idTabelaC,$idBuscaC);
  $tabelaU = "atirador";
  $idTabelaU = "id_cod_atirador";
  $idBuscaU = $usuario;
  $visualizarU = $metodos->visualizar($tabelaU,$idTabelaU,$idBuscaU);
  $declaracao = $num;
  switch($declaracao)
  {
    case '1' : $tipo = 'Filiação de Entidade'; break;
    case '2' : $tipo = 'Habitualidade'; break;
    case '3' : $tipo = 'Ranking'; break;
    case '4' : $tipo = 'Modalidades'; break;
    case '5' : $tipo = 'Guia de Tráfego'; break;
    case '6' : $tipo = 'Atividades no Clube'; break;
  }
  if($visualizarC[0] == 0 || $visualizarU[0] == 0){
    echo "<script>window.location.href='".$caminhoAbsoluto."/filiacao-entidade/'</script>";
    exit();
  }
}
if($_POST["CodigoAutenticacao"]){
  //list($codigo,$usuario) = explode("_",$_POST["CodigoAutenticacao"]);
  $tabelaC = "declaracao";
  $idTabelaC = "autenticacao";
  $idBuscaC = $_POST["CodigoAutenticacao"];
  $visualizarC = $metodos->visualizar($tabelaC,$idTabelaC,$idBuscaC);
  $tabelaU = "atirador";
  $idTabelaU = "atirador";
  $idBuscaU = $visualizarC[1]->atirador;
  $visualizarU = $metodos->visualizar($tabelaU,$idTabelaU,$idBuscaU);
  $declaracao = $visualizarC[1]->tipo;
  switch($declaracao)
  {
    case 'F' : $tipo = 'Filiação de Entidade'; break;
    case 'H' : $tipo = 'Habitualidade'; break;
    case 'R' : $tipo = 'Ranking'; break;
    case 'M' : $tipo = 'Modalidades'; break;
    case 'G' : $tipo = 'Guia de Tráfego'; break;
    case 'A' : $tipo = 'Atividades no Clube'; break;
  }
  if($visualizarC[0] == 0 || $visualizarU[0] == 0){
    echo "<script>alert('Nenhuma registro encontrado!');</script>";
    echo "<script>window.location.href='".$caminhoAbsoluto."/validar-documentos/'</script>";
    exit();
  }
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
        <h3 style="color: #3e4095; font-weight: bold">VALIDAR DECLARAÇÕES</h3>
        <div class="alert alert-primary">
          <div class="row">
            <div class="col-md-1"><i class="far fa-lightbulb fa-3x"></i></div>
            <div class="col-md-11 tex-left">Aqui você poderá digitar os códigos de validação impressos em nossos documentos oficiais e verificar sua autenticidade.</div>
          </div>
        </div>
         <br>
    <form class="" action="#!" method="post">
       <div class="form-group">
         <label for="">Declaração:</label>
         <input type="text" name="CodigoAutenticacao" class="form-control" value="" placeholder="Código de Autenticação">
       </div>
       <div class="form-group" align="center">
         <button type="submit" name="Buscar" value="Codigo" class="btn btn-primary">Verificar</button>
       </div>
    </form>
          <?php
             if($_SERVER["QUERY_STRING"]){
                list($c,$n) = explode("num=",$_SERVER["QUERY_STRING"]);
                $num = $n;
                list($codigo,$usuario) = explode("_",$c);
                $tabela = "atirador";
                $idTabela = "id_cod_atirador";
                $idBusca = $usuario;
                $visualizar = $metodos->visualizar($tabela,$idTabela,$idBusca);
                $tabelaC = "declaracao";
                $idTabelaC = "autenticacao";
                $idBuscaC = $codigo;
                $visualizarC = $metodos->visualizar($tabelaC,$idTabelaC,$idBuscaC);
            ?>
            <div class="alert alert-success">
              <div class="alert alert-success" style="border: 1px dashed; text-align: center;">
                <h4 style="font-weight: bold">
                   <?php echo $visualizarC[1]->autenticacao; ?>
                 </h4>
              </div>
              <p><h5><strong>Declaração de <?php echo $tipo; ?> de Tiro Desportivo</strong></h5></p>
              <p><?php echo $visualizar[1]->nome; ?></p>
              <p><strong>CPF:</strong> <?php echo $visualizar[1]->cpf; ?>  - <strong>Código:</strong> <?php echo $visualizar[1]->codigo; ?></p>
              <?php
              list($data,$hora) = explode(" ",$visualizarC[1]->data);
              $data = $data;
              list($ano,$mes,$dia) = explode("-",$data);
              $data = $dia."/".$mes."/".$ano;
              ?>
              <p><strong>Emissão:</strong> <?php echo $data; ?></p>
            </div>
          <?php } ?>
<?php
             if($_POST["CodigoAutenticacao"]){
                  $tabelaC = "declaracao";
                  $idTabelaC = "autenticacao";
                  $idBuscaC = $_POST["CodigoAutenticacao"];
                  $visualizarC = $metodos->visualizar($tabelaC,$idTabelaC,$idBuscaC);
                  $tabelaU = "atirador";
                  $idTabelaU = "atirador";
                  $idBuscaU = $visualizarC[1]->atirador;
            ?>
            <div class="alert alert-success">
              <div class="alert alert-success" style="border: 1px dashed; text-align: center;">
                <h4 style="font-weight: bold">
                   <?php echo $visualizarC[1]->autenticacao; ?>
                 </h4>
              </div>
              <p><h5><strong>Declaração de <?php echo $tipo; ?> de Tiro Desportivo</strong></h5></p>
              <p><?php echo $visualizarU[1]->nome; ?></p>
              <p><strong>CPF:</strong> <?php echo $visualizarU[1]->cpf; ?>  - <strong>Código:</strong> <?php echo $visualizarU[1]->codigo; ?></p>
              <?php
              list($data,$hora) = explode(" ",$visualizarC[1]->data);
              $data = $data;
              list($ano,$mes,$dia) = explode("-",$data);
              $data = $dia."/".$mes."/".$ano;
              ?>
              <p><strong>Emissão:</strong> <?php echo $data; ?></p>
            </div>
          <?php } ?>
          <?php if($_POST["Buscar"] == "Codigo"){
                  $tabela = "atirador";
                  $idTabela = "atirador";
                  $idBusca = $_SESSION["IdUsuario"];
                  $visualizar = $metodos->visualizar($tabela,$idTabela,$idBusca);
                  $tabelaC = "declaracao";
                  $idTabelaC = "autenticacao";
                  $idBuscaC = $_POST["CodigoAutenticacao"];
                  $visualizarC = $metodos->visualizar($tabelaC,$idTabelaC,$idBuscaC);
                  if($visualizarC[0] == 0){
            ?>
               <div class="alert alert-danger">
                 <i class="fas fa-exclamation-triangle"></i> Código de declaração inválido!
               </div>
          <?php }else{ ?>
            <div class="alert alert-success">
              <div class="alert alert-success" style="border: 1px dashed; text-align: center;">
                <h4 style="font-weight: bold">
                   <?php echo $visualizarC[1]->autenticacao; ?>
                 </h4>
              </div>
              <p><h5><strong>Declaração de <?php echo $tipo; ?> de Tiro Desportivo</strong></h5></p>
              <p><?php echo $visualizar[1]->nome; ?></p>
              <p><strong>CPF:</strong> <?php echo $visualizar[1]->cpf; ?>  - <strong>Código:</strong> <?php echo $visualizar[1]->codigo; ?></p>
              <p><strong>Emissão:</strong> 05/12/2018</p>
            </div>
        <?php }} ?>


      </div>
      <div class="col-md-4 col-xs-12">

        <!-- Menu Lateral -->
       <?php if($_SESSION["Logado"] == false){ include("menu-nao-logado.php"); }else{ include("menu-logado.php"); } ?>
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
  <script src="<?php echo $caminhoAbsoluto; ?>/js/jquery.min.js"></script>
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
  </body>
</html>
