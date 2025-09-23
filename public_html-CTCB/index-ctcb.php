<?php
error_reporting(0);

// Sessão com segurança extra
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
session_start();

require_once('classes/metodosClass.php');
$metodos = new metodosClass();
$caminhoAbsoluto = $metodos->caminhoAbsoluto();

if (!isset($_SESSION["TipoAcesso"])) {
  echo "<script>window.location.href='" . $caminhoAbsoluto . "/'</script>";
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
  <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
</head>
<body>
  <div id="fb-root"></div>
  <script>
    (function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      // Corrigido para usar HTTPS explícito
      js.src = "https://connect.facebook.net/pt_BR/sdk.js#xfbml=1&version=v2.3";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
  </script>

  <div class="col-md-12 fundo-container">
    <div class="container">
      <div class="menu">
        <div class="row">
          <div class="col-md-4">
            <figure>
              <a href="<?php echo $caminhoAbsoluto; ?>/">
                <img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" alt="Logo CTCB" class="logo" title="Voltar para a página inicial">
              </a>
            </figure>
          </div>
          <div class="col-md-8">
            <div class="row offset-md-8">
              <div class="menu-superior">
                <a href="<?php echo $caminhoAbsoluto; ?>/">Principal</a> &nbsp;
                <a href="<?php echo $caminhoAbsoluto; ?>/fale-conosco/">Contato</a> &nbsp;
                <a href="<?php echo $caminhoAbsoluto; ?>/localizacao/">Localização</a>
              </div>
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
                      <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/importacao/">Importação</a>
                    </li>
                    <span class="linha-vertical"></span>
                    <li class="nav-item">
                      <a class="nav-link" href="<?php echo $caminhoAbsoluto; ?>/cacadas/">Caçadas</a>
                    </li>
                    <!-- Continue padronizando os demais links e menus com $caminhoAbsoluto -->
                  </ul>
                </div>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts locais padronizados -->
  <script src="<?php echo $caminhoAbsoluto; ?>/js/bootstrap.min.js"></script>
  <script src="<?php echo $caminhoAbsoluto; ?>/js/main.js"></script>
</body>
</html>