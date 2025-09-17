<?php
declare(strict_types=1);

/**
 * Classe responsável por estabelecer a conexão com o banco de dados.
 *
 * Os dados de conexão podem ser definidos por variáveis de ambiente:
 * INTRANET_DB_HOST, INTRANET_DB_USER, INTRANET_DB_PASSWORD, INTRANET_DB_NAME,
 * INTRANET_DB_PORT e INTRANET_DB_CHARSET. Caso não sejam informados, serão
 * utilizados os valores padrão.
 */
class conectaClass
{
    private const DEFAULT_PORTA = 3306;
    private const CHARSET_PADRAO = 'utf8mb4';

    private string $servidor;
    private string $usuario;
    private string $senha;
    private string $banco;
    private int $porta;
    private string $charset;

    public function __construct(
        ?string $servidor = null,
        ?string $usuario = null,
        ?string $senha = null,
        ?string $banco = null,
        ?int $porta = null,
        ?string $charset = null
    ) {
        $this->servidor = $servidor ?? (getenv('INTRANET_DB_HOST') ?: 'localhost');
        $this->usuario = $usuario ?? (getenv('INTRANET_DB_USER') ?: '');
        $this->senha = $senha ?? (getenv('INTRANET_DB_PASSWORD') ?: '');
        $this->banco = $banco ?? (getenv('INTRANET_DB_NAME') ?: '');

        $portaInformada = $porta ?? $this->valorInteiroDeAmbiente('INTRANET_DB_PORT');
        $this->porta = $portaInformada > 0 ? $portaInformada : self::DEFAULT_PORTA;

        $charsetInformado = $charset ?? (getenv('INTRANET_DB_CHARSET') ?: null);
        $this->charset = $charsetInformado !== null && $charsetInformado !== ''
            ? $charsetInformado
            : self::CHARSET_PADRAO;
    }

    /**
     * Cria uma conexão com o banco de dados MySQL.
     *
     * @throws RuntimeException Quando não é possível estabelecer a conexão.
     */
    public function conectar(): mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conexao = mysqli_init();

            if ($conexao === false) {
                throw new RuntimeException('Não foi possível inicializar o cliente MySQL.');
            }

            if (defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
                $conexao->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
            }

            $conexao->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            $conexao->real_connect(
                $this->servidor,
                $this->usuario,
                $this->senha,
                $this->banco,
                $this->porta
            );
            $conexao->set_charset($this->charset);

            return $conexao;
        } catch (mysqli_sql_exception | RuntimeException $exception) {
            $this->registrarErro($exception);

            throw new RuntimeException(
                'Não foi possível conectar ao banco de dados no momento.',
                0,
                $exception
            );
        }
    }

    private function registrarErro(Throwable $exception): void
    {
        error_log(sprintf(
            '[%s] Falha ao conectar ao banco de dados: %s',
            date('Y-m-d H:i:s'),
            $exception->getMessage()
        ));
    }

    private function valorInteiroDeAmbiente(string $variavel): int
    {
        $valor = getenv($variavel);

        if ($valor === false) {
            return 0;
        }

        return (int) filter_var($valor, FILTER_SANITIZE_NUMBER_INT);
    }
}

