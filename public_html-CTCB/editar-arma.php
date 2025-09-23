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
if($_POST["Submit"] == "Salvar"){
  $dados = array_filter($_POST);
  echo $metodos->editarArmasAtirador($dados);
}
$tabela = "atirador_arma";
$idTabela = "id_cod_arma";
$idBusca = $_SERVER["QUERY_STRING"];
$visualizar = $metodos->visualizar($tabela, $idTabela, $idBusca);
if($visualizar[0] == 0){
  echo "<script>window.location.href='".$caminhoAbsoluto."/armas/'</script>";
  exit();
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
        <h3 style="color: #3e4095; font-weight: bold"><i class="fas fa-crosshairs"></i> EDITAR ARMA</h3><br>
        <?php if($_SESSION["Sucesso"]){ ?>
        <div class="alert alert-success">
           <i class="fas fa-check"></i> Arma alterada com sucesso
        </div>
        <?php } ?>
        <div style="margin-top: 10px">
          <form method="post" action="#!">
            <input type="hidden" name="Key" value="<?php echo $_SERVER["QUERY_STRING"]; ?>">
             <div class="form-group">
               <label for="descricao">Descrição: <small>(pistola, revólver, espingarda, etc.)</small></label>
               <input type="text" name="Descricao" class="form-control" id="descricao"  value="<?php echo $visualizar[1]->descricao; ?>">
             </div>
             <div class="form-group">
               <label for="serie">Nº de série:</label>
               <input type="text" name="Serie" class="form-control" id="serie" value="<?php echo $visualizar[1]->numero_serie; ?>">
             </div>
             <div class="form-group">
               <label for="fabricante">Fabricante:</label>
               <input type="text" name="Fabricante" class="form-control" id="fabricante" value="<?php echo $visualizar[1]->fabricante; ?>">
             </div>
             <div class="form-group">
                           <div class="form-row mb-4">
                              <div class="col">
                                <label for="anoFabricacao">Ano de Fabricação:</label>
                                    <input type="text" name="AnoFabricacao" class="form-control" id="anoFabricacao" value="<?php echo $visualizar[1]->ano_fabricacao; ?>">
                              </div>
                              <div class="col">
                                <label for="nacionalidade">País de Fabricação:</label>
                                     <?php echo $metodos->listarNacionalidade($buscar = $visualizar[1]->nacionalidade); ?>
                              </div>
                           </div>
                </div>
                <div class="form-group">
                  <label for="modelo">Modelo:</label>
                  <input type="text" name="Modelo" class="form-control" id="modelo" value="<?php echo $visualizar[1]->modelo; ?>">
                </div>
                <div class="form-group">
                  <label for="calibre">Calibre: <small>(22, 38, etc.)</small></label>
                  <input type="text" name="Calibre" class="form-control" id="calibre" value="<?php echo $visualizar[1]->calibre; ?>">
                </div>
                <div class="form-group">
                  <label for="cor">Cor: <small>(preta, cinza, prata, etc.)</small></label>
                  <input type="text" name="Cor" class="form-control" id="cor" value="<?php echo $visualizar[1]->cor; ?>">
                </div>
                <div class="form-group">
                  <label for="cabo">Cabo: <small>(niquelado, madeira, etc.)</small></label>
                  <input type="text" name="Cabo" class="form-control" id="cabo" value="<?php echo $visualizar[1]->cabo; ?>">
                </div>
                <div class="form-group">
                  <label for="dimensao">Dimensão: <small>(cano longo, cano duplo, etc.)</small></label>
                  <input type="text" name="Dimensao" class="form-control" id="dimensao" value="<?php echo $visualizar[1]->dimensao; ?>">
                </div>
                <div class="form-group">
                  <label for="carregamento">Carregamento: <small>(capacidade de carregamento)</small></label>
                  <input type="text" name="Carregamento" class="form-control" id="carregamento" value="<?php echo $visualizar[1]->capacidade_carregamento; ?>">
                </div>
              <div class="form-group">
                <label for="numeroCanos">Número de canos:</label>
                  <input name="NumeroCanos" type="number" class="form-control" id="numCanos" maxlength="2" value="<?php echo $visualizar[1]->numero_canos; ?>">
              </div>
              <div class="form-group">
                            <div class="form-row mb-4">
                               <div class="col">
                                 <label for="comprimentoCano">Comprimento do cano:</label>
                                     <input type="text" name="ComprimentoCano" class="form-control" id="comprimentoCano" value="<?php echo $visualizar[1]->comprimento_cano; ?>">
                               </div>
                               <div class="col">
                                 <label for="unidade">Unidade:</label>
                                      <select class="form-control" name="Unidade" id="unidade">
                                        <option value="">Selecione</option>
                                        <option value="M" <?php if($visualizar[1]->comprimento_cano_unidade == 'M') echo "selected"; ?>>Milímetro</option>
                                        <option value="P" <?php if($visualizar[1]->comprimento_cano_unidade == 'P') echo "selected"; ?>>Polegada</option>
                                      </select>
                               </div>
                            </div>
                 </div>
                 <div class="form-group">
                               <div class="form-row mb-4">
                                  <div class="col">
                                    <label for="almaCano">Alma do cano:</label>
                                    <select class="form-control" name="AlmaCano">
                                      <option value="">Selecione</option>
                                      <option value="L" <?php if($visualizar[1]->alma_cano == 'L') echo "selected"; ?>>Lisa</option>
                                      <option value="R" <?php if($visualizar[1]->alma_cano == 'R') echo "selected"; ?>>Raiada</option>
                                    </select>
                                  </div>
                                  <div class="col">
                                    <label for="numeroRaias">Número de raias:</label>
                                      <input name="NumeroRaias" type="number" class="form-control" id="numCanos" maxlength="2" value="<?php echo $visualizar[1]->numero_canos; ?>">
                                  </div>
                                  <div class="col">
                                    <label for="sentidoRaia">Sentido raia:</label>
                                         <select class="form-control" name="SentidoRaia" id="sentidoRaia">
                                           <option value="">Selecione</option>
                                           <option value="D" <?php if($visualizar[1]->sentido_raia == 'D') echo "selected"; ?>>Direita</option>
                                           <option value="E" <?php if($visualizar[1]->sentido_raia == 'E') echo "selected"; ?>>Esquerda</option>
                                         </select>
                                  </div>
                               </div>
                    </div>
              <div class="form-group">
                  <label for="funcionamento">Funcionamento: <span style="color: red">*</span></label>
                  <select class="form-control" name="Funcionamento" id="funcionamento">
                    <option value="">Selecione</option>
                    <option value="A" <?php if($visualizar[1]->funcionamento == 'A') echo "selected"; ?>>Automática</option>
                    <option value="S" <?php if($visualizar[1]->funcionamento == 'S') echo "selected"; ?>>Semi-automática</option>
                    <option value="R" <?php if($visualizar[1]->funcionamento == 'R') echo "selected"; ?>>Repetição</option>
                  </select>
              </div>
              <div class="form-group">
                <label for="acabamento">Acabamento:</label>
                <select class="form-control" name="Acabamento" id="acabamento">
                  <option value="">Selecione</option>
                  <option value="O" <?php if($visualizar[1]->acabamento == 'O') echo "selected"; ?>>Oxidada</option>
                  <option value="I" <?php if($visualizar[1]->acabamento == 'I') echo "selected"; ?>>Inox</option>
                  <option value="N" <?php if($visualizar[1]->acabamento == 'N') echo "selected"; ?>>Niquelado</option>
                </select>
              </div>
              <div class="form-group">
                <label for="numSigma">Nº de sigma:</label>
                  <input name="NumeroSigma" type="text" class="form-control" id="numSigma" value="<?php echo $visualizar[1]->sigma; ?>">
              </div>
              <div class="form-group">
                <label for="vencimentoCRAF">Vencimento CRAF:</label>
                <?php list($anoCRAF,$mesCRAF,$diaCRAF) = explode("-",$visualizar[1]->vencimento_craf); ?>
                            <div class="form-row mb-4">
                               <div class="col">
                                     <select class="form-control" name="DiaCRAF">
                                       <option value="">Dia</option>
                                      <?php for($dia = 1; $dia <= 31; $dia++){ $dia = ($dia < 10)?'0'.$dia:$dia; ?>
                                               <option value="<?php echo $dia; ?>" <?php if($dia == $diaCRAF) echo "selected"; ?>><?php echo $dia; ?></option>
                                      <?php } ?>
                                     </select>
                               </div>
                               <div class="col">
                                 <select class="form-control" name="MesCRAF">
                                      <option value="">Mês</option>
                                      <option value="1" <?php if($mesCRAF == '1') echo "selected"; ?>>Janeiro</option>
                                      <option value="2" <?php if($mesCRAF == '2') echo "selected"; ?>>Fevereiro</option>
                                      <option value="3" <?php if($mesCRAF == '3') echo "selected"; ?>>Março</option>
                                      <option value="4" <?php if($mesCRAF == '4') echo "selected"; ?>>Abril</option>
                                      <option value="5" <?php if($mesCRAF == '5') echo "selected"; ?>>Maio</option>
                                      <option value="6" <?php if($mesCRAF == '6') echo "selected"; ?>>Junho</option>
                                      <option value="7" <?php if($mesCRAF == '7') echo "selected"; ?>>Julho</option>
                                      <option value="8" <?php if($mesCRAF == '8') echo "selected"; ?>>Agosto</option>
                                      <option value="9" <?php if($mesCRAF == '9') echo "selected"; ?>>Setembro</option>
                                      <option value="10" <?php if($mesCRAF == '10') echo "selected"; ?>>Outubro</option>
                                      <option value="11" <?php if($mesCRAF == '11') echo "selected"; ?>>Novembro</option>
                                      <option value="12" <?php if($mesCRAF == '12') echo "selected"; ?>>Dezembro</option>
                                 </select>
                               </div>
                               <div class="col">
                                 <select class="form-control" name="AnoCRAF">
                                  <option value="">Ano</option>
                                  <?php for($ano = 2000; $ano <= date("Y"); $ano++){ ?>
                                    <option value="<?php echo $ano; ?>" <?php if($ano == $anoCRAF) echo "selected"; ?>><?php echo $ano; ?></option>
                                  <?php } ?>
                                 </select>
                               </div>
                            </div>
                 </div>
                 <div class="form-group">
                   <label for="sobressalentes">Sobressalentes:</label>
                     <input name="Sobressalentes" type="text" class="form-control" id="sobressalentes" value="<?php echo $visualizar[1]->sobressalentes; ?>">
                 </div>
            <div class="form-group">
             <div align="center">
             <button type="submit" name="Submit" value="Salvar" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
           </div>
         </div>
        </form>

      </div>
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
   <script>
   $(document).ready(function(){
          $("div.alert").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
         });
   </script>
  </body>
</html>
