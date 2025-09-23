<?php
/* 
  Para debug, descomente estas linhas:
  ini_set('display_errors',1);
  ini_set('display_startup_errors',1);
  error_reporting(E_ALL);
*/
error_reporting(0);

class conectaClass
{
    /**
     * Configurações de conexão
     */
    private $servidor = 'ctcb_confedera.mysql.dbaas.com.br';
    private $usuario  = 'ctcb_confedera';
    private $senha    = 'b@nc0Confeder@';
    private $banco    = 'ctcb_confedera';

    /**
     * Conexão ativa
     */
    protected $conecta;

    /**
     * Faz a conexão ao banco de dados
     * @access public
     * @return mysqli conexão
     */
    public function conectar()
    {
        $this->conecta = mysqli_connect($this->servidor, $this->usuario, $this->senha, $this->banco);

        if (!$this->conecta) {
            // Grava o erro no log do PHP
            error_log("Erro na conexão com o banco: " . mysqli_connect_error());
            // Exibe mensagem genérica para o usuário
            die("Erro ao conectar ao banco de dados.");
        }

        mysqli_set_charset($this->conecta, "utf8");

        return $this->conecta;
    }

    /**
     * Retorna a conexão ativa
     * @access public
     */
    public function getConexao()
    {
        return $this->conecta;
    }
}
?>
