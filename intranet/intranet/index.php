<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/classes/metodosClass.php';

$erroInicializacao = '';

try {
    $metodos = new metodosClass();
} catch (RuntimeException $exception) {
    $erroInicializacao = 'Não foi possível conectar ao serviço de autenticação. Tente novamente em instantes.';
    error_log(sprintf('[%s] Falha ao iniciar a intranet: %s', date('Y-m-d H:i:s'), $exception->getMessage()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($metodos)) {
    $login = filter_input(INPUT_POST, 'Login', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $senha = filter_input(INPUT_POST, 'Senha', FILTER_UNSAFE_RAW) ?? '';

    try {
        $resultadoLogin = $metodos->validarUsuarios($login, $senha);

        if (is_string($resultadoLogin) && $resultadoLogin !== '') {
            echo $resultadoLogin;
        }
    } catch (Throwable $exception) {
        error_log(sprintf('[%s] Erro ao validar usuário: %s', date('Y-m-d H:i:s'), $exception->getMessage()));
        $_SESSION['ErroLogin'] = time() + 5;
    }
}

if (isset($_SESSION['ErroLogin']) && $_SESSION['ErroLogin'] < time()) {
    unset($_SESSION['ErroLogin']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
	<head>
		<meta charset="utf-8">
		<title>CTCB | Sistema de Gestão</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="icon" type="image/x-icon" href="/imagens/favicon.png">
		<link href="css/style.css" rel="stylesheet">
		<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
		<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
	</head>
        <body class="gradient-background">
			<br><br><br><br><br><div class="container">
			<div class="col-md-10 col-md-offset-1 main" >
			<div class="col-md-6 left-side" style="margin-top: 120px" align="center">
			<img src="imagens/logo.png" alt="" class="img-responsive">
			</div><!--col-sm-6-->
			<div class="col-md-6 right-side">
			<h3 style="font-weight: bold"><i class="fas fa-lock"></i> ACESSO RESTRITO</h3>
                        <div class="form">
                                <?php if ($erroInicializacao !== ''): ?>
                                <div class="alert alert-warning" style="font-weight: bold"><i class="fas fa-exclamation-triangle fa-lg"></i> <?php echo htmlspecialchars($erroInicializacao, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($_SESSION['ErroLogin'])): ?>
                                <div class="alert alert-danger" style="font-weight: bold"><i class="fas fa-exclamation-triangle fa-lg"></i> Login ou senha inválidos</div>
                        <?php endif; ?>
                                <form class="" action="#" method="post">
                        <div class="form-group">
                        <label for="login">Login:</label>
                        <input type="text" name="Login" id="login" class="form-control input-lg" autocomplete="username">
                        </div>
                        <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" name="Senha" id="senha" class="form-control input-lg" autocomplete="current-password">
			</div>
			<div class="text-xs-center">
			<a href="lembrar-senha/" style="color:#FFF" title="Esqueceu a senha?">Esqueceu a senha?</a>
			<!--<button class="btn btn-deep-purple" title="Acessar o sistema" disabled>EM MANUTENÇÃO</button>--> 
			<button class="btn btn-deep-purple" title="Acessar o sistema">ACESSAR</button>
			</div>
		</form>
	    <div style="padding: 50px"></div>
			</div>
			</div><!--col-sm-6-->
			</div><!--col-sm-8-->
			</div><!--container-->
			<script>
	    $(document).ready(function(){
	           $("div.alert").fadeIn( 300 ).delay( 3000 ).fadeOut( 400 );
	          });
	    </script>
	</body>
</html>
