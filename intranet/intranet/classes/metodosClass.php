<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conectaClass.php';

class metodosClass
{
    private const URL_PADRAO = 'https://intranet.ctcb.org.br';
    private const SITE_KEY_PADRAO = '6Lc6ynwUAAAAAHLWy-hSJel8KT6FQXaG_nS6Aex4';
    private const SECRET_KEY_PADRAO = '6Lc6ynwUAAAAAA7t43SzBYHzFjwmbYXu4qIfaIPX';

    private mysqli $conexao;

    public function __construct()
    {
        try {
            $conectar = new conectaClass();
            $this->conexao = $conectar->conectar();
        } catch (RuntimeException $exception) {
            $this->registrarExcecao($exception);

            throw $exception;
        }
    }

    public function logs(string $ip): void
    {
        $stmt = null;

        try {
            $stmt = $this->conexao->prepare('INSERT INTO acessos VALUES (NULL, ?, NOW())');
            $stmt->bind_param('s', $ip);
            $stmt->execute();
        } catch (mysqli_sql_exception $exception) {
            $this->registrarExcecao($exception);
        } finally {
            $this->fecharStatement($stmt);
        }
    }

    public function palavraMinuscula(string $palavra): string
    {
        return mb_convert_case($palavra, MB_CASE_TITLE, 'UTF-8');
    }

    public function caminhoAbsoluto(): string
    {
        $url = getenv('INTRANET_BASE_URL');

        if ($url === false || trim($url) === '') {
            return self::URL_PADRAO;
        }

        return rtrim($url, '/');
    }

    public function siteKey(): string
    {
        $siteKey = getenv('INTRANET_RECAPTCHA_SITE_KEY');

        return $siteKey === false || trim($siteKey) === ''
            ? self::SITE_KEY_PADRAO
            : trim($siteKey);
    }

    public function secretKey(): string
    {
        $secretKey = getenv('INTRANET_RECAPTCHA_SECRET_KEY');

        return $secretKey === false || trim($secretKey) === ''
            ? self::SECRET_KEY_PADRAO
            : trim($secretKey);
    }

    public function validarUsuarios(string $login, string $senha): ?string
    {
        $login = trim($login);
        $senha = trim($senha);

        if ($login === '' || $senha === '') {
            return $this->falharLogin();
        }

        $senhaCodificada = $this->codificar($senha);

        try {
            if ($this->acessoTemporario($login, $senhaCodificada)) {
                return $this->redirecionar($this->caminhoAbsoluto() . '/sistema-ctcb/atiradores/');
            }

            if ($this->loginAdministrativo($login, $senhaCodificada)) {
                return $this->redirecionar($this->caminhoAbsoluto() . '/sistema-ctcb/');
            }

            if ($this->loginClube($login, $senhaCodificada)) {
                return $this->redirecionar($this->caminhoAbsoluto() . '/sistema/');
            }
        } catch (mysqli_sql_exception $exception) {
            $this->registrarExcecao($exception);
        }

        return $this->falharLogin();
    }

    public function mesExtenso(string $mes): string
    {
        return match ($mes) {
            '01' => 'janeiro',
            '02' => 'fevereiro',
            '03' => 'março',
            '04' => 'abril',
            '05' => 'maio',
            '06' => 'junho',
            '07' => 'julho',
            '08' => 'agosto',
            '09' => 'setembro',
            '10' => 'outubro',
            '11' => 'novembro',
            '12' => 'dezembro',
            default => '',
        };
    }

    public function visualizar(string $tabela, string $idTabela, string $idBusca): array
    {
        if (!$this->identificadorValido($tabela) || !$this->identificadorValido($idTabela)) {
            throw new InvalidArgumentException('Identificador inválido informado.');
        }

        $sql = sprintf('SELECT * FROM %s WHERE %s = ?', $tabela, $idTabela);

        $stmt = null;

        try {
            $stmt = $this->conexao->prepare($sql);
            $stmt->bind_param('s', $idBusca);
            $stmt->execute();

            if (method_exists($stmt, 'get_result')) {
                $resultado = $stmt->get_result();

                if ($resultado instanceof mysqli_result) {
                    $objeto = $resultado->fetch_object() ?: null;

                    return [$resultado->num_rows, $objeto];
                }
            }

            $stmt->store_result();
            $totalLinhas = $stmt->num_rows;

            if ($totalLinhas === 0) {
                return [0, null];
            }

            $meta = $stmt->result_metadata();

            if ($meta === false) {
                return [$totalLinhas, null];
            }

            $dados = [];
            $bind = [];

            while ($campo = $meta->fetch_field()) {
                $bind[$campo->name] = null;
                $dados[] = &$bind[$campo->name];
            }

            call_user_func_array([$stmt, 'bind_result'], $dados);

            if ($stmt->fetch()) {
                return [$totalLinhas, (object) $bind];
            }

            return [$totalLinhas, null];
        } catch (mysqli_sql_exception $exception) {
            $this->registrarExcecao($exception);

            return [0, null];
        } finally {
            if (isset($meta) && $meta instanceof mysqli_result) {
                $meta->free();
            }

            $this->fecharStatement($stmt);
        }
    }

    public function codificar(string $key): string
    {
        $salt = '$' . md5(strrev($key)) . '%';
        $codifica = crypt($key, $salt);

        return hash('sha512', $codifica);
    }

    public function sairSistema(): ?string
    {
        $_SESSION['Logado'] = false;
        unset($_SESSION['Logado'], $_SESSION['IdUsuario']);

        return $this->redirecionar($this->caminhoAbsoluto() . '/');
    }

    private function acessoTemporario(string $login, string $senhaCodificada): bool
    {
        if ($login !== 'Evandro.CTCB') {
            return false;
        }

        if ($senhaCodificada !== $this->codificar('Acesso@Temp')) {
            return false;
        }

        $_SESSION['LogadoCTCB'] = true;
        $_SESSION['Usuario'] = 'Evandro';

        return true;
    }

    private function loginAdministrativo(string $login, string $senhaCodificada): bool
    {
        $loginsPermitidos = ['ctcb', 'Admin@Master', 'Provas'];

        if (!in_array($login, $loginsPermitidos, true)) {
            return false;
        }

        $stmt = null;

        try {
            $stmt = $this->conexao->prepare(
                'SELECT IdAdmin, NomeAdmin, DataAcesso, HoraAcesso, Cadastrar, Editar, Excluir '
                . 'FROM acesso_admin WHERE LoginAdmin = ? AND SenhaAdmin = ? LIMIT 1'
            );
            $stmt->bind_param('ss', $login, $senhaCodificada);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                return false;
            }

            $stmt->bind_result($idAdmin, $nomeAdmin, $dataAcesso, $horaAcesso, $cadastrar, $editar, $excluir);
            $stmt->fetch();

            $_SESSION['DataAcessoCTCB'] = $dataAcesso;
            $_SESSION['HoraAcessoCTCB'] = $horaAcesso;
            $_SESSION['Cadastrar'] = $cadastrar;
            $_SESSION['Editar'] = $editar;
            $_SESSION['Excluir'] = $excluir;
            $_SESSION['LogadoCTCB'] = true;
            $_SESSION['CTCB'] = $nomeAdmin;
            $_SESSION['IdAdmin'] = $idAdmin;

            $this->atualizarDadosAcessoAdmin((int) $idAdmin);

            return true;
        } catch (mysqli_sql_exception $exception) {
            $this->registrarExcecao($exception);

            return false;
        } finally {
            $this->fecharStatement($stmt);
        }
    }

    private function loginClube(string $login, string $senhaCodificada): bool
    {
        $stmt = null;

        try {
            $stmt = $this->conexao->prepare(
                'SELECT clube FROM clube WHERE sigla = ? AND senha = ? LIMIT 1'
            );
            $stmt->bind_param('ss', $login, $senhaCodificada);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                return false;
            }

            $stmt->bind_result($idClube);
            $stmt->fetch();

            $_SESSION['Logado'] = true;
            $_SESSION['IdClube'] = $idClube;

            return true;
        } catch (mysqli_sql_exception $exception) {
            $this->registrarExcecao($exception);

            return false;
        } finally {
            $this->fecharStatement($stmt);
        }
    }

    private function atualizarDadosAcessoAdmin(int $idAdmin): void
    {
        $stmt = null;

        try {
            $stmt = $this->conexao->prepare(
                'UPDATE acesso_admin SET DataAcesso = CURDATE(), HoraAcesso = CURTIME() WHERE IdAdmin = ?'
            );
            $stmt->bind_param('i', $idAdmin);
            $stmt->execute();
        } catch (mysqli_sql_exception $exception) {
            $this->registrarExcecao($exception);
        } finally {
            $this->fecharStatement($stmt);
        }
    }

    private function falharLogin(): ?string
    {
        $_SESSION['ErroLogin'] = time() + 5;

        return $this->redirecionar($this->caminhoAbsoluto() . '/');
    }

    private function redirecionar(string $url): ?string
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }

        return "<script>window.location.href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'</script>";
    }

    private function identificadorValido(string $identificador): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $identificador);
    }

    private function registrarExcecao(Throwable $exception): void
    {
        error_log(sprintf(
            '[%s] Erro na intranet: %s',
            date('Y-m-d H:i:s'),
            $exception->getMessage()
        ));
    }

    private function fecharStatement(?mysqli_stmt $stmt): void
    {
        if ($stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
    }
}

