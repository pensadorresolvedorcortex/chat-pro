<?php

/*

// Habilitar as linhas abaixo em caso de erro....

ini_set('display_errors',1);

ini_set('display_startup_erros',1);

error_reporting(E_ALL);

 */

/**

 * NOTE: Dispensamos o uso do try e catch

 */

// Habilitar essa linha

error_reporting(0);

session_start();

require_once "conectaClass.php";

class metodosClass {

	public $conexao;

/**

 * Classe construtor

 */

	public function __construct() {

		$conectar = new conectaClass();

		$this->conexao = $conectar->conectar();

		return $this->conexao;

	}

/**

 * Método armazena os logs de acesso ao site

 * @access public

 * @param string $ip

 */

	public function logs($ip) {

		mysqli_query($this->conexao, "INSERT INTO acessos VALUES(null,'" . $ip . "',NOW());");

	}

	/**

	 * Método converte o nome com a primeira letra em caixa alta

	 * @access public

	 * @param string $palavra

	 * @return string $nomeUsuario

	 */

	public function palavraMinuscula($palavra) {

		$nomeUsuario = mb_convert_case($palavra, MB_CASE_TITLE, 'UTF-8');

		return $nomeUsuario;

	}

	/**

	 * Método cria o caminho absoluto dos links. Encontra-se em todas as páginas

	 * @access public

	 * @param null

	 * @return string $caminhoAbsoluto

	 */

        public function caminhoAbsoluto($httpsIndicator = null)
        {
                $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
                $isHttps = false;

                if ($httpsIndicator !== null) {
                        $httpsValue = strtolower((string) $httpsIndicator);
                        $isHttps = !empty($httpsValue) && $httpsValue !== 'off';
                } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                        $isHttps = true;
                } elseif (isset($_SERVER['REQUEST_SCHEME'])) {
                        $isHttps = strtolower($_SERVER['REQUEST_SCHEME']) === 'https';
                }

                $scheme = $isHttps ? 'https' : 'http';

                if ($host === '') {
                        return $scheme . '://localhost';
                }

                return $scheme . '://' . $host;
        }

	/**

	 * Método cria a chave lado cliente (Google recaptcha). Veja o método recaptcha($key)

	 * Encontra-se nas páginas area-associados.php, cadastrar-associados.php

	 * @access public

	 * @param null

	 * @return string $siteKey

	 * @ignore

	 */

	public function siteKey() {

		$siteKey = '6Lc6ynwUAAAAAHLWy-hSJel8KT6FQXaG_nS6Aex4';

		return $siteKey;

	}

	/**

	 * Método cria a chave lado servidor (Google recaptcha). Veja o método recaptcha($key)

	 * Encontra-se nas páginas area-associados.php, cadastrar-associados.php

	 * @access public

	 * @param null

	 * @return string $secretKey

	 * @ignore

	 */

	public function secretKey() {

		$secretKey = '6Lc6ynwUAAAAAA7t43SzBYHzFjwmbYXu4qIfaIPX';

		return $secretKey;

	}

	/**

	 * Método validar o acesso aos usuários

	 * Encontra-se em todas as páginas

	 * @access public

	 * @param string $login, $senha

	 * @return true

	 */

	public function validarUsuarios($tipoAcesso, $login, $senha) {

		// Acesso Atirador

		if ($tipoAcesso == 'A') {

			$login = mysqli_real_escape_string($this->conexao, $login);

			$senha = $this->codificar(mysqli_real_escape_string($this->conexao, $senha));

			$sql = mysqli_query($this->conexao, "SELECT * FROM atirador WHERE email = '" . $login . "' AND senha = '" . $senha . "';");

			$ctcb = mysqli_fetch_object($sql);

			if (mysqli_num_rows($sql) > 0) {

				$_SESSION["Logado"] = true;

				$_SESSION["TipoAcesso"] = "Atirador";

				$_SESSION["IdUsuario"] = $ctcb->atirador;

				return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/'</script>";

				exit();

			} else {

				$_SESSION["ErroLogin"] = time() + 5;

			}

		}

		// Acesso Despachante e CTCB

		if ($tipoAcesso == 'D') {

			$login = mysqli_real_escape_string($this->conexao, $login);

			$senha = $this->codificar(mysqli_real_escape_string($this->conexao, $senha));

			$sqlD = mysqli_query($this->conexao, "SELECT * FROM despachante WHERE login = '" . $login . "' AND senha = '" . $senha . "';");

			$ctcbD = mysqli_fetch_object($sqlD);

			if (mysqli_num_rows($sqlD) > 0) {

				$_SESSION["Logado"] = true;

				$_SESSION["IdDespachante"] = $ctcbD->despachante;

				if ($ctcbD->login == 'ctcb') {

					$_SESSION["TipoAcesso"] = "CTCB";

					return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/index-ctcb/'</script>";

				} else {

					$_SESSION["TipoAcesso"] = "Despachante";

					return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/index-despachantes/'</script>";

				}

				exit();

			} else {

				$_SESSION["ErroLogin"] = time() + 5;

			}

		}

	}

	/**

	 * Método valida o cadastro (CPF)

	 * Encontra-se na página cadastro-associado.php

	 * @access public

	 * @param array $cpf

	 * @return true

	 */

	public function validarCadastro($cpf) {

		$cpf = $this->limpaCPF_CNPJ($cpf);

		$sqlVerificar = mysqli_query($this->conexao, "SELECT * FROM atirador WHERE cpf = '" . mysqli_real_escape_string($this->conexao, $cpf) . "';");

		if (mysqli_num_rows($sqlVerificar) > 0) {

			$_SESSION["CPFCadastrado"] = time() + 5;

		} else {

			$_SESSION["CPF"] = $cpf;

			return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/cadastrar-associado/';</script>";

		}

	}

	/**

	 * Método visualiza o pagamento dos atiradores

	 * Encontra-se na página cadastro-associado.php

	 * @access public

	 * @param int $idAtiradores

	 * @return true

	 */

	public function visualizarPagamentosAtiradores($idAtiradores) {

		$sqlAtiradorPagto = mysqli_query($this->conexao, "SELECT * FROM atirador_pagamento WHERE atirador = '" . $idAtiradores . "' ORDER BY atirador_pagamento DESC LIMIT 1;");

		$visualizar = '';

		while ($ctcb = mysqli_fetch_object($sqlAtiradorPagto)) {

			if ($ctcb->valor_pago == '0.00') {

				list($anoA, $mesA, $diaA) = explode('-', $ctcb->data_vencimento);

				$visualizar .= '<tr>

                            <th>' . date("Y") . '</th>

                            <td>' . $diaA . '/' . $mesA . '/' . $anoA . '</td>

                            <td>R$ 230,00</td>

                            <td>

                              <form action="https://pagseguro.uol.com.br/checkout/v2/payment.html" method="post" onsubmit="PagSeguroLightbox(this); return false;">

                              <input type="hidden" name="code" value="9CF83B61070729A44486CFA1EB7CA62B" />

                              <button type="submit" class="btn btn-success btn-sm" style="font-weight: bold" onclick="window.location.href=\'https://www.ctcb.org.br/efetuar-pagamento/\'">Pagar</button>

                              </form>

                            </td>

                         </tr>';

			} else {

				list($anoP, $mesP, $diaP) = explode('-', $ctcb->data_pagamento);

				$visualizar .= '<tr>

                        <!--<th>' . ($ctcb->anuidade + 1) . '</th> -->

                          <th>' . date("Y") . '</th>

                        <td>' . $diaP . '/' . $mesP . '/' . ($anoP + 1) . '</td>

                        <td>R$ 230,00</td>

                        <td><form action="https://pagseguro.uol.com.br/checkout/v2/payment.html" method="post" onsubmit="PagSeguroLightbox(this); return false;">

                        <input type="hidden" name="code" value="9CF83B61070729A44486CFA1EB7CA62B" />

                        <button type="submit" class="btn btn-warning btn-sm" style="font-weight: bold" onclick="window.location.href=\'https://www.ctcb.org.br/efetuar-pagamento/\'">Próxima fatura</button>

                        </form></td>

                      </tr>';

				$visualizar .= '<tr>

                        <th>' . $ctcb->anuidade . '</th>

                        <td>' . $diaP . '/' . $mesP . '/' . $anoP . '</td>

                        <td>R$ ' . number_format($ctcb->valor_pago, 2, ",", ".") . '</td>

                        <td><button class="btn btn-success btn-sm">Pago</button></td>

                      </tr>';

			}

		}

		return $visualizar;

	}

	/**

	 * Método lista em uma combox a Nacionalidade

	 * Encontra-se na página cadastrar-associado.php

	 * @access public

	 * @param null

	 * @return $visualizar

	 */

	public function listarNacionalidade($buscar) {

		$visualizar = "<select name='Nacionalidade' class='form-control required'>";

		$visualizar .= "<option>Selecione uma opção</option>";

		$sqlNacionalidade = mysqli_query($this->conexao, "SELECT * FROM nacionalidade;");

		while ($listar = mysqli_fetch_object($sqlNacionalidade)) {

			if ($buscar != null) {

				if ($listar->nacionalidade == $buscar) {

					$selected = 'selected';

				} else {

					$selected = "";

				}

			}

			$visualizar .= "<option value='" . $listar->nome . "' " . $selected . ">" . $listar->nome . "</option>";

		}

		$visualizar .= "</select>";

		return $visualizar;

	}

	/**

	 * Método lista em uma combox na Naturalidade

	 * Encontra-se na página cadastrar-associado.php

	 * @access public

	 * @param null

	 * @return $visualizar

	 */

	public function listarNaturalidade($buscar) {

		$visualizar = "<select name='Naturalidade' class='form-control' required>";

		$visualizar .= "<option>Selecione uma opção</option>";

		$sqlNaturalidade = mysqli_query($this->conexao, "SELECT * FROM estado;");

		while ($listar = mysqli_fetch_object($sqlNaturalidade)) {

			if ($buscar != null) {

				if ($listar->estado == $buscar) {

					$selected = 'selected';

				} else {

					$selected = "";

				}

			}

			$visualizar .= "<option value='" . $listar->estado . "' " . $selected . ">" . $listar->nome . "</option>";

		}

		$visualizar .= "</select>";

		return $visualizar;

	}

	/**

	 * Método retira os caracteres do CPF e CNPJ

	 * Encontra-se na página cadastro-associado.php

	 * @access public

	 * @param string $valor

	 * @return true

	 */

	public function limpaCPF_CNPJ($valor) {

		$valor = trim($valor);

		$valor = str_replace(".", "", $valor);

		$valor = str_replace(",", "", $valor);

		$valor = str_replace("-", "", $valor);

		$valor = str_replace("/", "", $valor);

		return $valor;

	}

	/**

	 * Método mostra o recaptcha (Google)

	 * Encontra-se na página area-associados.php

	 * @access public

	 * @param string $key

	 * @return string $retorno

	 */

	public function recaptcha($key) {

		$resposta = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $this->secretKey() . "&response={$key}");

		$retorno = json_decode($resposta);

		return $retorno;

	}

	/**

	 * Método lista os eventos

	 * Encontra-se na página inscricao.php

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	public function listarEventos() {

		$dataInicio = mktime(23, 59, 59, date('m'), date('d') - date('j'), date('Y'));

		$data = date("Y-m-d", $dataInicio);

		$sqlEventos = mysqli_query($this->conexao, "SELECT evento,idcodevento,nome,data_inicio, DATE_FORMAT(data_inicio,'%d/%m') AS DataInicio, DATE_FORMAT(data_termino,'%d/%m/%Y') AS DataTermino FROM evento");

		$visualizar = "";

		while ($listar = mysqli_fetch_object($sqlEventos)) {

			if ($listar->data_inicio > $data) {

				$visualizar .= "<i class=\"fas fa-caret-right\"></i><a href='" . $this->caminhoAbsoluto() . "/inscrever/?" . $listar->idcodevento . "' style='color: #59A22D'> " . $listar->DataInicio . " a " . $listar->DataTermino . " - " . $listar->nome . "</a><br>";

			}

		}

		return $visualizar;

	}

	/**

	 * Método lista as armas do atirador

	 * Encontra-se na armas.php

	 * @access public

	 * @param int $idUsuario

	 * @return string $visualizar

	 */

	public function listarArmas($idAtirador) {

		$sql = mysqli_query($this->conexao, "SELECT * FROM atirador_arma WHERE atirador = '" . $_SESSION["IdUsuario"] . "' ORDER BY atirador_arma DESC;");

		if (mysqli_num_rows($sql) == 0) {

			$visualizar = "<div class=\"alert alert-info\"><i class=\"fas fa-exclamation-triangle\"></i> Ainda não existem armas cadastradas!</div>";

		} else {

			$visualizar = "<table class=\"table table-striped table-bordered\">

                       <thead>

                         <tr>

                           <th scope=\"col\" style=\"background-color: #4682B4; text-align: center; color: #FFF\">Descrição</th>

                           <th scope=\"col\" style=\"background-color: #4682B4; text-align: center; color: #FFF\">Nº Série</th>

                           <th scope=\"col\" style=\"background-color: #4682B4; text-align: center; color: #FFF\">Fabricante</th>

                           <th scope=\"col\" style=\"background-color: #4682B4; text-align: center; color: #FFF\">Ação</th>

                         </tr>

                       </thead>

                       <tbody>";

			while ($listar = mysqli_fetch_object($sql)) {

				$visualizar .= "<tr>";

				$visualizar .= "<td><i class=\"fas fa-caret-right\"></i> " . $listar->descricao . "</td>";

				$visualizar .= "<td>" . $listar->numero_serie . "</td>";

				$visualizar .= "<td>" . $listar->fabricante . "</td>";

				$visualizar .= "<td class='text-center'><a href='" . $this->caminhoAbsoluto() . "/editar-arma/?" . $listar->id_cod_arma . "' style='color: #000' title='Editar a arma número de série " . $listar->numero_serie . "'><i class=\"far fa-edit\"></i></a> <a href='#!' id=\"btnVisualizarCasas\" data-id='" . $listar->atirador_arma . "' data-toggle=\"modal-3\" title=\"Excluir a arma número série " . $visualizar->numero_serie . "\" style='color: #000'><i class=\"far fa-trash-alt\"></i></a></td>";

				$visualizar .= "</tr>";

			}

			$visualizar .= "</tbody" >

			$visualizar .= "</table>";

			$visualizar .= " <div class=\"modal fade\" id=\"casasRegionais\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"\" aria-hidden=\"true\">

                       <div class=\"modal-dialog\">

                           <div>

                               <div id=\"tela\">

                               </div>

                           </div>

                       </div>

                   </div>";

			$visualizar .= "<script>

                      $(\"table\").on('click',\"#btnVisualizarCasas\", function(){

                          var posts = $(this).attr('data-id');

                          $.post('" . $this->caminhoAbsoluto() . "/excluir-arma/', {key: posts}, function(retorno){

                           // console.log(retorno);

                                 $(\"#casasRegionais\").modal({ backdrop: 'static' });

                                 $(\"#tela\").html(retorno);

                          });

                      });

                      </script>";

		}

		return $visualizar;

	}

	/**

	 * Método para verificar o pagamento do usuário. Encontra-se na página menu-logado.php

	 * @access public

	 * @param int $idUsuario

	 * @return string $visualizar

	 */

	public function verificarPagamento($idUsuario) {

		$idAtirador = mysqli_real_escape_string($this->conexao, $idUsuario);

		//

		//echo "SELECT *, DATE_SUB(data_vencimento, INTERVAL 12 MONTH) AS DataVencimento FROM atirador_pagamento WHERE atirador = '".$idAtirador."' AND anuidade = '".date("Y")."' OR anuidade = '".(date("Y")-1)."';";

		//$sqlPagamentos = mysqli_query($this->conexao,"SELECT * FROM atirador_pagamento WHERE atirador = '".$idAtirador."' ORDER BY valor_pago DESC LIMIT 1;");

		// $sqlPagamentos = mysqli_query($this->conexao,"SELECT *, DATE_SUB(data_vencimento, INTERVAL 12 MONTH) AS DataVencimento FROM atirador_pagamento WHERE atirador = '".$idAtirador."' ORDER BY valor_pago DESC LIMIT 1;");

		/*    $sqlPagamentos = mysqli_query($this->conexao,"SELECT *, DATE_SUB(data_vencimento, INTERVAL 12 MONTH) AS DataVencimento FROM atirador_pagamento WHERE atirador = '".$idAtirador."' AND (anuidade = ".date("Y")." OR anuidade = ".(date("Y")-1).") ORDER BY data_vencimento DESC;");

 */

		$sqlPagamentos = mysqli_query($this->conexao, "SELECT *, DATE_ADD(data_vencimento, INTERVAL 12 MONTH) AS DataVencimento FROM atirador_pagamento WHERE atirador = '" . $idAtirador . "' ORDER BY data_vencimento DESC;");

		$ctcb = mysqli_fetch_object($sqlPagamentos);

		//if(mysqli_num_rows($sqlPagamentos) == 0)

		if ($ctcb->DataVencimento < date("Y-m-d")) {

			$v = "0";

			$_SESSION["EfetuarPagamento"] = true;

			$valor = "R$ 230,00";

			$visualizar = "<form action=\"https://pagseguro.uol.com.br/checkout/v2/payment.html\" method=\"post\" onsubmit=\"PagSeguroLightbox(this); return false;\">

                        <input type=\"hidden\" name=\"code\" value=\"9CF83B61070729A44486CFA1EB7CA62B\" />";

			$visualizar .= "<div class=\"bg-warning text-center\" style=\"padding: 10px\">

                          <span style=\"font-family: Arial\">Valor em aberto: " . $valor . "</span>

                          <br>

                          <button class=\"btn btn-success btn-sm\" style=\"font-weight: bold\" onclick=\"window.location.href='" . $this->caminhoAbsoluto() . "/efetuar-pagamento/'\">Pagar</button>

                     </div>";

			$visualizar .= "</form>";

		} else {

			$v = "1";

			$visualizar = "<div class=\"bg-success text-center\" style=\"padding: 10px\">

                          <span style=\"font-family: Arial; color: #FFF; font-weight: bold\">Seu pagamento está em dia! <i class=\"fas fa-check\"></i></span>

                      </div>";

		}

		return array(mysqli_num_rows($sqlPagamentos), $visualizar, $v);

	}

	/**

	 * Método contabiliza a quantidade de eventos que um atirador participa

	 * @access public

	 * @param int $idUsuario

	 * @return string $visualizar

	 */

	public function contarEventosAtirador($idUsuario) {

		$idAtirador = mysqli_real_escape_string($this->conexao, $idUsuario);

		$sqlEventos = mysqli_query($this->conexao, "SELECT * FROM evento_atirador WHERE atirador = '" . $idAtirador . "';");

		$contar = mysqli_num_rows($sqlEventos);

		if ($contar == 0) {

			$visualizar = "nenhum evento!";

		} else if ($contar == 1) {

			$visualizar = "1 evento";

		} else {

			$visualizar = $contar . " eventos";

		}

		return array($contar, $visualizar);

	}

	/**

	 * Método cadastra os dados do atirador

	 * Encontra-se na cadastrar-atirador.php

	 * @access public

	 * @param array $dados

	 * @return true

	 */

	public function alterarDadosAtirador(array $dados) {

		if ($dados["Senha"] != "") {

			$email = mysqli_real_escape_string($this->conexao, $dados["Email"]);

			/// verifica se existe alguem com o mesmo e-mail

			if ($this->emailExiste($email, $_SESSION["IdUsuario"])) {
				/// Se o email existir tratar aqui

				$_SESSION["SucessoCadastro"] = false;

				$_SESSION["Erro"] = time() + 5;

				return false;

			}

			/// fim verifica

			$senha = mysqli_real_escape_string($this->conexao, $dados["Senha"]);

			$senha = $this->codificar($senha);

			$nome = mysqli_real_escape_string($this->conexao, $dados["Nome"]);

			$genero = mysqli_real_escape_string($this->conexao, $dados["Genero"]);

			$diaNascimento = mysqli_real_escape_string($this->conexao, $dados["DiaNascimento"]);

			$mesNascimento = mysqli_real_escape_string($this->conexao, $dados["MesNascimento"]);

			$anoNascimento = mysqli_real_escape_string($this->conexao, $dados["AnoNascimento"]);

			$dataNascimento = $anoNascimento . "-" . $mesNascimento . "-" . $diaNascimento;

			$telefone = mysqli_real_escape_string($this->conexao, $dados["Telefone"]);

			$celular = mysqli_real_escape_string($this->conexao, $dados["Celular"]);

			$nomeMae = mysqli_real_escape_string($this->conexao, $dados["NomeMae"]);

			$nomePai = mysqli_real_escape_string($this->conexao, $dados["NomePai"]);

			$estadoCivil = mysqli_real_escape_string($this->conexao, $dados["EstadoCivil"]);

			$cep = mysqli_real_escape_string($this->conexao, $dados["CEP"]);

			$logradouro = mysqli_real_escape_string($this->conexao, $dados["Logradouro"]);

			$bairro = mysqli_real_escape_string($this->conexao, $dados["Bairro"]);

			$cidade = mysqli_real_escape_string($this->conexao, $dados["Cidade"]);

			$estado = mysqli_real_escape_string($this->conexao, $dados["Estado"]);

			$sqlEstado = mysqli_query($this->conexao, "SELECT * FROM estado WHERE sigla = '" . $estado . "';");

			$ctcbEstado = mysqli_fetch_object($sqlEstado);

			$estado = $ctcbEstado->estado;

			$nacionalidade = mysqli_real_escape_string($this->conexao, $dados["Nacionalidade"]);

			$naturalidade = mysqli_real_escape_string($this->conexao, $dados["Naturalidade"]);

			$profissao = mysqli_real_escape_string($this->conexao, $dados["Profissao"]);

			$telefoneComercial = mysqli_real_escape_string($this->conexao, $dados["TelefoneComercial"]);

			$atleta = mysqli_real_escape_string($this->conexao, $dados["Atleta"]);

			$arbitro = mysqli_real_escape_string($this->conexao, $dados["Arbitro"]);

			$colecionador = mysqli_real_escape_string($this->conexao, $dados["Colecionador"]);

			$cacador = mysqli_real_escape_string($this->conexao, $dados["Cacador"]);

			$recarga = mysqli_real_escape_string($this->conexao, $dados["Recarga"]);

			$dies = mysqli_real_escape_string($this->conexao, $dados["Dies"]);

			$rg = mysqli_real_escape_string($this->conexao, $dados["Identidade"]);

			$orgaoEmissor = mysqli_real_escape_string($this->conexao, $dados["OrgaoEmissor"]);

			list($diaEmissao, $mesEmissao, $anoEmissao) = explode("/", mysqli_real_escape_string($this->conexao, $dados["DataEmissao"]));

			$dataEmissao = $anoEmissao . "-" . $mesEmissao . "-" . $diaEmissao;

			$cr = mysqli_real_escape_string($this->conexao, $dados["CR"]);

			list($diaCR, $mesCR, $anoCR) = explode("/", mysqli_real_escape_string($this->conexao, $dados["CRValidade"]));

			$validade = $anoCR . "-" . $mesCR . "-" . $diaCR;

			mysqli_query($this->conexao, "UPDATE atirador SET

          data_nascimento = '" . $dataNascimento . "',

          endereco = '" . $logradouro . "',

          bairro = '" . $bairro . "',

          cidade = '" . $cidade . "',

          cep = '" . $cep . "',

          telefone_residencia = '" . $telefone . "',

          telefone_comercial = '" . $telefoneComercial . "',

          celular = '" . $celular . "',

          email = '" . $email . "',

          cr = '" . $cr . "',

          cr_validade = '" . $validade . "',

          sexo = '" . $genero . "',

          nome_pai = '" . $nomePai . "',

          nome_mae = '" . $nomeMae . "',

          profissao = '" . $profissao . "',

          estado_civil = '" . $estadoCivil . "',

          dies = '" . $dies . "',

          senha = '" . $senha . "'

          WHERE atirador = '" . $_SESSION["IdUsuario"] . "';");

			if (mysqli_affected_rows($this->conexao) > 0) {

				$_SESSION["Sucesso"] = time() + 5;

				return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/alterar-cadastro/'</script>";

			} else {

				$_SESSION["Erro"] = time() + 5;

			}

		} else {

			$email = mysqli_real_escape_string($this->conexao, $dados["Email"]);

			/// verifica se existe alguem com o mesmo e-mail

			if ($this->emailExiste($email, $_SESSION["IdUsuario"])) {
				/// Se o email existir tratar aqui

				$_SESSION["SucessoCadastro"] = false;

				$_SESSION["Erro"] = time() + 5;

				return false;

			}

			$nome = mysqli_real_escape_string($this->conexao, $dados["Nome"]);

			$genero = mysqli_real_escape_string($this->conexao, $dados["Genero"]);

			$diaNascimento = mysqli_real_escape_string($this->conexao, $dados["DiaNascimento"]);

			$mesNascimento = mysqli_real_escape_string($this->conexao, $dados["MesNascimento"]);

			$anoNascimento = mysqli_real_escape_string($this->conexao, $dados["AnoNascimento"]);

			$dataNascimento = $anoNascimento . "-" . $mesNascimento . "-" . $diaNascimento;

			$telefone = mysqli_real_escape_string($this->conexao, $dados["Telefone"]);

			$celular = mysqli_real_escape_string($this->conexao, $dados["Celular"]);

			$nomeMae = mysqli_real_escape_string($this->conexao, $dados["NomeMae"]);

			$nomePai = mysqli_real_escape_string($this->conexao, $dados["NomePai"]);

			$estadoCivil = mysqli_real_escape_string($this->conexao, $dados["EstadoCivil"]);

			$cep = mysqli_real_escape_string($this->conexao, $dados["CEP"]);

			$logradouro = mysqli_real_escape_string($this->conexao, $dados["Logradouro"]);

			$bairro = mysqli_real_escape_string($this->conexao, $dados["Bairro"]);

			$cidade = mysqli_real_escape_string($this->conexao, $dados["Cidade"]);

			$estado = mysqli_real_escape_string($this->conexao, $dados["Estado"]);

			$sqlEstado = mysqli_query($this->conexao, "SELECT * FROM estado WHERE sigla = '" . $estado . "';");

			$ctcbEstado = mysqli_fetch_object($sqlEstado);

			$estado = $ctcbEstado->estado;

			$nacionalidade = mysqli_real_escape_string($this->conexao, $dados["Nacionalidade"]);

			$naturalidade = mysqli_real_escape_string($this->conexao, $dados["Naturalidade"]);

			$profissao = mysqli_real_escape_string($this->conexao, $dados["Profissao"]);

			$telefoneComercial = mysqli_real_escape_string($this->conexao, $dados["TelefoneComercial"]);

			$atleta = mysqli_real_escape_string($this->conexao, $dados["Atleta"]);

			$arbitro = mysqli_real_escape_string($this->conexao, $dados["Arbitro"]);

			$colecionador = mysqli_real_escape_string($this->conexao, $dados["Colecionador"]);

			$cacador = mysqli_real_escape_string($this->conexao, $dados["Cacador"]);

			$recarga = mysqli_real_escape_string($this->conexao, $dados["Recarga"]);

			$dies = mysqli_real_escape_string($this->conexao, $dados["Dies"]);

			$rg = mysqli_real_escape_string($this->conexao, $dados["Identidade"]);

			$orgaoEmissor = mysqli_real_escape_string($this->conexao, $dados["OrgaoEmissor"]);

			list($diaEmissao, $mesEmissao, $anoEmissao) = explode("/", mysqli_real_escape_string($this->conexao, $dados["DataEmissao"]));

			$dataEmissao = $anoEmissao . "-" . $mesEmissao . "-" . $diaEmissao;

			$cr = mysqli_real_escape_string($this->conexao, $dados["CR"]);

			list($diaCR, $mesCR, $anoCR) = explode("/", mysqli_real_escape_string($this->conexao, $dados["CRValidade"]));

			$validade = $anoCR . "-" . $mesCR . "-" . $diaCR;

			mysqli_query($this->conexao, "UPDATE atirador SET

          data_nascimento = '" . $dataNascimento . "',

          endereco = '" . $logradouro . "',

          bairro = '" . $bairro . "',

          cidade = '" . $cidade . "',

          cep = '" . $cep . "',

          telefone_residencia = '" . $telefone . "',

          telefone_comercial = '" . $telefoneComercial . "',

          celular = '" . $celular . "',

          email = '" . $email . "',

          cr = '" . $cr . "',

          cr_validade = '" . $validade . "',

          nome_pai = '" . $nomePai . "',

          nome_mae = '" . $nomeMae . "',

          estado_civil = '" . $estadoCivil . "'

          WHERE atirador = '" . $_SESSION["IdUsuario"] . "';");

			if (mysqli_affected_rows($this->conexao) > 0) {

				$_SESSION["Sucesso"] = time() + 5;

				return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/alterar-cadastro/'</script>";

			} else {

				$_SESSION["Erro"] = time() + 5;

			}

		}

	}

	/**

	 * Método mostra a relação dos despachantes em um combox

	 * Encontra-se na página cadastro-associado.php

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	public function selectDespachantes() {

		$sql = mysqli_query($this->conexao, "SELECT * FROM despachante ORDER BY nome ASC");

		$visualizar = '';

		while ($ctcb = mysqli_fetch_object($sql)) {

			$visualizar .= '<option value="' . $ctcb->despachante . '">' . $ctcb->nome . '</option>';

		}

		return $visualizar;

	}

	/**

	 * Checa se email existe

	 *

	 * o parametro um é o cpf, você passa somente o e-mail para saber se esse e-mail existe

	 *

	 * mas na alteração você deve informar o ID do registro para excluirmos da pesquisa, pois ao menos um registro já pode ter esse email

	 * logo, a condicao abaixo verifica se existe o email menos para o atirador com o id X

	 */

	private function emailExiste($email, $id = null) {

		if (!is_null($id)) {
			$cond = " and atirador <> '{$id}'";
		} else {
			$cond = '';
		}

		$sql = mysqli_query($this->conexao, "SELECT email FROM atirador WHERE email = '" . $email . "'{$cond};");

		$ctcb = mysqli_fetch_object($sql);

		/*var_dump( "SELECT email FROM atirador WHERE email = '".$email."'{$cond};" );

			    var_dump( $ctcb );

		*/

		return !is_null($ctcb);

	}

	/**

	 * Método cadastra os dados do atirador

	 * Encontra-se na alterar-cadastro.php

	 * @access public

	 * @param array $dados

	 * @return true

	 */

	public function cadastrarDadosAtirador(array $dados) {

		$email = mysqli_real_escape_string($this->conexao, $dados["Email"]);

		/// verifica se existe alguem com o mesmo e-mail

		if ($this->emailExiste($email)) {

			$_SESSION["SucessoCadastro"] = false;

			$_SESSION["Erro"] = time() + 5;

			return false;

		}

		/*print_r( $dados );

    die('Passou!');*/

		/// fim verifica

		$cpf = mysqli_real_escape_string($this->conexao, $dados["CPF"]);

		$anuidade = mysqli_real_escape_string($this->conexao, $dados["TipoAnuidade"]);

		$sqlAnuidade = mysqli_query($this->conexao, "SELECT * FROM anuidade_tipo WHERE nome = '" . $anuidade . "';");

		$ctcbAnuidade = mysqli_fetch_object($sqlAnuidade);

		$anuidade = $ctcbAnuidade->anuidade_tipo;

		$despachante = mysqli_real_escape_string($this->conexao, $dados["Despachante"]);

		$senha = mysqli_real_escape_string($this->conexao, $dados["Senha"]);

		$senhaEnviarEmail = mysqli_real_escape_string($this->conexao, $dados["Senha"]);

		$senha = $this->codificar($senha);

		$nome = mysqli_real_escape_string($this->conexao, $dados["Nome"]);

		$genero = mysqli_real_escape_string($this->conexao, $dados["Genero"]);

		$genero = ($genero == "Masculino") ? 'M' : 'F';

		$diaNascimento = mysqli_real_escape_string($this->conexao, $dados["DiaNascimento"]);

		$mesNascimento = mysqli_real_escape_string($this->conexao, $dados["MesNascimento"]);

		$anoNascimento = mysqli_real_escape_string($this->conexao, $dados["AnoNascimento"]);

		$dataNascimento = $anoNascimento . "-" . $mesNascimento . "-" . $diaNascimento;

		$telefone = mysqli_real_escape_string($this->conexao, $dados["Telefone"]);

		$celular = mysqli_real_escape_string($this->conexao, $dados["Celular"]);

		$nomeMae = mysqli_real_escape_string($this->conexao, $dados["NomeMae"]);

		$nomePai = mysqli_real_escape_string($this->conexao, $dados["NomePai"]);

		$estadoCivil = mysqli_real_escape_string($this->conexao, $dados["EstadoCivil"]);

		$cep = mysqli_real_escape_string($this->conexao, $dados["CEP"]);

		$logradouro = mysqli_real_escape_string($this->conexao, $dados["Logradouro"]);

		$bairro = mysqli_real_escape_string($this->conexao, $dados["Bairro"]);

		$cidade = mysqli_real_escape_string($this->conexao, $dados["Cidade"]);

		$estado = mysqli_real_escape_string($this->conexao, $dados["Estado"]);

		$sqlEstado = mysqli_query($this->conexao, "SELECT * FROM estado WHERE sigla = '" . $estado . "';");

		$ctcbEstado = mysqli_fetch_object($sqlEstado);

		$estado = $ctcbEstado->estado;

		$nacionalidade = mysqli_real_escape_string($this->conexao, $dados["Nacionalidade"]);

		$sqlNacionalidade = mysqli_query($this->conexao, "SELECT * FROM nacionalidade WHERE nome = '" . $nacionalidade . "';");

		$ctcbNacionalidade = mysqli_fetch_object($sqlNacionalidade);

		$nacionalidade = $ctcbNacionalidade->nacionalidade;

		$naturalidade = mysqli_real_escape_string($this->conexao, $dados["Naturalidade"]);

		$sqlNaturalidade = mysqli_query($this->conexao, "SELECT * FROM estado WHERE sigla = '" . $naturalidade . "';");

		$ctcbNaturalidade = mysqli_fetch_object($sqlNaturalidade);

		$naturalidade = $ctcbNaturalidade->estado;

		$profissao = mysqli_real_escape_string($this->conexao, $dados["Profissao"]);

		$telefoneComercial = mysqli_real_escape_string($this->conexao, $dados["TelefoneComercial"]);

		$atleta = mysqli_real_escape_string($this->conexao, $dados["Atleta"]);

		$atleta = ($atleta == "Sim") ? 'S' : 'N';

		$instrutor = mysqli_real_escape_string($this->conexao, $dados["Instrutor"]);

		$instrutor = ($instrutor == "Sim") ? 'S' : 'N';

		$arbitro = mysqli_real_escape_string($this->conexao, $dados["Arbitro"]);

		$arbitro = ($arbitro == "Sim") ? 'S' : 'N';

		$colecionador = mysqli_real_escape_string($this->conexao, $dados["Colecionador"]);

		$colecionador = ($colecionador == "Sim") ? 'S' : 'N';

		$cacador = mysqli_real_escape_string($this->conexao, $dados["Cacador"]);

		$cacador = ($cacador == "Sim") ? 'S' : 'N';

		$recarga = mysqli_real_escape_string($this->conexao, $dados["Recarga"]);

		$recarga = ($recarga == "Sim") ? 'S' : 'N';

		$dies = mysqli_real_escape_string($this->conexao, $dados["Dies"]);

		$rg = mysqli_real_escape_string($this->conexao, $dados["Identidade"]);

		$orgaoEmissor = mysqli_real_escape_string($this->conexao, $dados["OrgaoEmissor"]);

		list($diaEmissao, $mesEmissao, $anoEmissao) = explode("/", mysqli_real_escape_string($this->conexao, $dados["DataEmissao"]));

		$dataEmissao = $anoEmissao . "-" . $mesEmissao . "-" . $diaEmissao;

		$cr = mysqli_real_escape_string($this->conexao, $dados["CR"]);

		list($diaCR, $mesCR, $anoCR) = explode("/", mysqli_real_escape_string($this->conexao, $dados["CRValidade"]));

		$validade = $anoCR . "-" . $mesCR . "-" . $diaCR;

		$sqlVerificar = mysqli_query($this->conexao, "SELECT * FROM atirador WHERE cpf = '" . $cpf . "';");

		if (mysqli_num_rows($sqlVerificar) == 0) {

			mysqli_query($this->conexao, "INSERT INTO atirador(anuidade_tipo,data_cadastro,nome,despachante,data_nascimento,endereco,bairro,cidade,estado,cep,telefone_residencia,telefone_comercial,celular,email,identidade,identidade_orgao,identidade_emissao,cpf,cr,cr_validade,sexo,nacionalidade,naturalidade,status,profissao,eh_atleta,eh_instrutor,eh_arbitro,eh_colecionador,eh_cacador,eh_recarga,dies,nome_pai,nome_mae,estado_civil,senha,para_atleta)

                                        VALUES('" . $anuidade . "',

                                          CURDATE(),

                                          '" . $nome . "',

                                          '" . $despachante . "',

                                          '" . $dataNascimento . "',

                                          '" . $logradouro . "',

                                          '" . $bairro . "',

                                          '" . $cidade . "',

                                          '" . $estado . "',

                                          '" . $cep . "',

                                          '" . $telefone . "',

                                          '" . $telefoneComercial . "',

                                          '" . $celular . "',

                                          '" . $email . "',

                                          '" . $rg . "',

                                          '" . $orgaoEmissor . "',

                                          '" . $dataEmissao . "',

                                          '" . $cpf . "',

                                          '" . $cr . "',

                                          '" . $validade . "',

                                          '" . $genero . "',

                                          '" . $nacionalidade . "',

                                          '" . $naturalidade . "',

                                          'A',

                                          '" . $profissao . "',

                                          '" . $atleta . "',

                                          '" . $instrutor . "',

                                          '" . $arbitro . "',

                                          '" . $colecionador . "',

                                          '" . $cacador . "',

                                          '" . $recarga . "',

                                          '" . $dies . "',

                                          '" . $nomePai . "',

                                          '" . $nomeMae . "',

                                          '" . $estadoCivil . "',

                                          '" . $senha . "',

                                          'N');");

			if (mysqli_affected_rows($this->conexao) > 0) {

				$idCadastro = mysqli_insert_id($this->conexao);

				$codigo = sprintf("%05d", $idCadastro);

				mysqli_query($this->conexao, "UPDATE atirador SET codigo = '" . $codigo . "' WHERE atirador = '" . $idCadastro . "';");

				$idCod = $this->codificar($idCadastro);

				mysqli_query($this->conexao, "UPDATE atirador SET id_cod_atirador = '" . $idCod . "' WHERE atirador = '" . $idCadastro . "';");

				require 'phpmailer/PHPMailerAutoload.php';

				require 'phpmailer/class.phpmailer.php';

				$mailer = new PHPMailer;

				$mailer->isSMTP();

				$mailer->SMTPOptions = array(

					'ssl' => array(

						'verify_peer' => false,

						'verify_peer_name' => false,

						'allow_self_signed' => true,

					),

				);

				$assunto = "Mensagem do site - Confederação de Tiro e Caça do Brasil";

				$mailer->Host = 'mail.ctcb.org.br';

				$mailer->SMTPAuth = true;

				$mailer->IsSMTP();

				$mailer->isHTML(true);

				$mailer->Port = 587;

				$mailer->CharSet = 'UTF-8';

				$mailer->Username = 'naoexclua@ctcb.org.br';

				$mailer->Password = 'confeder@c@o';

				$address = $email;

				$mensagem = '<table width="100%" border="0">';

				$mensagem .= '<tr>';

				$mensagem .= '<td style="text-align: left"><img src="https://www.ctcb.org.br/images/logo.png" style="width: 250px" alt="Logomarca da CTCB em letras azuis e partes da arma em verde" title="" class="logo img-fluid"></td>';

				$mensagem .= '<td style="text-align: right"><small>https://www.ctcb.org.br<br>atendimento@ctcb.org.br</small></td>';

				$mensagem .= '</tr>';

				$mensagem .= '</table>';

				$mensagem .= '<br>';

				$mensagem .= '<p style="font-weight: bold">Confirmação de Cadastro</p>';

				$mensagem .= '<p>Seja bem-vindo! Você acaba de ter seu cadastro regularizado em nossa confederação.<br>

                        A partir de agora você poderá usar sua senha para acessar a <strong>Área do Associado</strong><br>

                        em nosso site. Seu login lhe permitirá acesso total devido ao tipo de pagamento que você escolheu.<br><br>

                        Para acessar nosso site utilize os seguintes dados:<br><br>

                        https://www.ctcb.org.br<br><br>

                        e-mail: ' . $email . '<br>

                        senha: ' . $senhaEnviarEmail . '<br><br>

                        Em caso de dúvidas utilize o nosso formulário de Contato no site.</p>';

				$mailer->AddAddress($address, "CTCB - Confederação de Tiro e Caça do Brasil");

				$mailer->From = 'atendimento@ctcb.org.br';

				$mailer->FromName = "CTCB - Confederação de Tiro e Caça do Brasil";

				$mailer->Subject = $assunto;

				$mailer->MsgHTML($mensagem);

				$mailer->Send();

				$_SESSION["SucessoCadastro"] = true;

				return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/confirmar-cadastro/'</script>";

			} else {

				$_SESSION["Erro"] = time() + 5;

			}

		}

	}

	/**

	 * Método altera a senha do atirador. A alteração é para quem já está logado no sistema

	 * Encontra-se na alterar-senha.php

	 * @access public

	 * @param string $novaSenha

	 * @return true

	 */

	public function alterarSenhaAtirador($novaSenha) {

		if (trim($novaSenha) != "") {

			$novaSenha = mysqli_real_escape_string($this->conexao, $novaSenha);

			$codSenha = $this->codificar($novaSenha);

			mysqli_query($this->conexao, "UPDATE atirador SET senha = '" . $codSenha . "' WHERE atirador = '" . $_SESSION["IdUsuario"] . "';");

			if (mysqli_affected_rows($this->conexao) > 0) {

				$_SESSION["Sucesso"] = time() + 5;

				return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/alterar-senha/'</script>";

			} else {

				$_SESSION["Erro"] = time() + 5;

			}

		}

	}

	/** Método altera a senha do atirador para recuperação. A alteração é para quem não está logado no sistema

	 * Encontra-se na alterar-senha.php

	 * @access public

	 * @param string $novaSenha

	 * @return true

	 */

	public function alterarSenhaRecuperacao($key, $novaSenha) {

		if (trim($novaSenha) != "") {

			$novaSenha = mysqli_real_escape_string($this->conexao, $novaSenha);

			$codSenha = $this->codificar($novaSenha);

			mysqli_query($this->conexao, "UPDATE atirador SET senha = '" . $codSenha . "' WHERE atirador = '" . $key . "';");

			if (mysqli_affected_rows($this->conexao) > 0) {

				$_SESSION["Sucesso"] = time() + 5;

				unset($_SESSION["IdAtirador"]);

				$_SESSION["NovaSenha"] = false;

				unset($_SESSION["NovaSenha"]);

				return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/area-associados/'</script>";

			} else {

				$_SESSION["Erro"] = time() + 5;

			}

		}

	}

	/**

	 * Método verifica o email do atirador no banco de dados

	 * Encontra-se na página validar-email.php

	 * @access public

	 * @param string $email

	 * @return true

	 */

	public function verificarEmailAtirador($email) {

		$email = mysqli_real_escape_string($this->conexao, $email);

		$sql = mysqli_query($this->conexao, "SELECT * FROM atirador WHERE email = '" . $email . "';");

		$ctcb = mysqli_fetch_object($sql);

		if (mysqli_num_rows($sql) == 0) {

			return "E-mail não encontrado!";

		} else {

			require 'phpmailer/PHPMailerAutoload.php';

			require 'phpmailer/class.phpmailer.php';

			$mailer = new PHPMailer;

			$mailer->isSMTP();

			$mailer->SMTPOptions = array(

				'ssl' => array(

					'verify_peer' => false,

					'verify_peer_name' => false,

					'allow_self_signed' => true,

				),

			);

			$assunto = "Lembrete da senha - Confederação de Tiro e Caça do Brasil";

			$mailer->Host = 'mail.ctcb.org.br';

			$mailer->SMTPAuth = true;

			$mailer->IsSMTP();

			$mailer->isHTML(true);

			$mailer->Port = 587;

			$mailer->CharSet = 'UTF-8';

			$mailer->Username = 'naoexclua@ctcb.org.br';

			$mailer->Password = 'confeder@c@o';

			$address = $email;

			$mensagem = '<table width="100%" border="0">';

			$mensagem .= '<tr>';

			$mensagem .= '<td style="text-align: left"><img src="https://www.ctcb.org.br/images/logo.png" style="width: 250px" alt="Logomarca da CTCB em letras azuis e partes da arma em verde" title="" class="logo img-fluid"></td>';

			$mensagem .= '<td style="text-align: right"><small>https://www.ctcb.org.br<br>atendimento@ctcb.org.br</small></td>';

			$mensagem .= '</tr>';

			$mensagem .= '</table>';

			$mensagem .= '<br>';

			$mensagem .= '<p style="font-weight: bold">Lembrete da senha</p>';

			$mensagem .= '<pOlá ' . $ctcb->nome . '.<br><br>

                    Para cadastrar uma nova senha, clique no link abaixo:<br><br>

                    <a href="https://ctcb.org.br/validar-conta/?' . $ctcb->id_cod_atirador . '">Cadastrar uma nova senha</a><br><br>

                   Caso não tenha sido você, desconsidere esse e-mail.<br><br>

                   Em caso de dúvidas utilize o nosso formulário de Contato no site.</p>';

			$mailer->AddAddress($address, "CTCB - Confederação de Tiro e Caça do Brasil");

			$mailer->From = 'atendimento@ctcb.org.br';

			$mailer->FromName = "CTCB - Confederação de Tiro e Caça do Brasil";

			$mailer->Subject = $assunto;

			$mailer->MsgHTML($mensagem);

			$mailer->Send();

			$_SESSION["LembrarSenha"] = time() + 10;

			return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/enviado-instrucoes-email/';</script>";

		}

	}

	/**

	 * Método valida a cnta do usuário

	 * @access public

	 * @param string $key

	 * @return true

	 */

	public function validarConta($key) {

		$sql = mysqli_query($this->conexao, "SELECT * FROM atirador WHERE id_cod_atirador = '" . $key . "';");

		$ctcb = mysqli_fetch_object($sql);

		if (mysqli_num_rows($sql) == 0) {

			return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/';</script>";

		} else {

			unset($_SESSION["LembrarSenha"]);

			$_SESSION["NovaSenha"] = true;

			$_SESSION["IdAtirador"] = $ctcb->atirador;

			return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/cadastrar-nova-senha/';</script>";

		}

	}

	/**

	 * Método para cadastrar as armas dos atiradores

	 * Encontra-se na página cadastrar-armas.php

	 * @access public

	 * @param array $dados

	 * @return true

	 */

	public function cadastrarArmasAtirador(array $dados) {

		/*

			      //FUnciona

			      $dados["Descricao"] = filter_input(INPUT_POST,"Descricao", FILTER_SANITIZE_SPECIAL_CHARS);

			      echo "Descricao" .$dados["Descricao"];

		*/

		$descricao = mysqli_real_escape_string($this->conexao, $dados["Descricao"]);

		$serie = mysqli_real_escape_string($this->conexao, $dados["Serie"]);

		$fabricante = mysqli_real_escape_string($this->conexao, $dados["Fabricante"]);

		$anoFabricacao = mysqli_real_escape_string($this->conexao, $dados["AnoFabricacao"]);

		$nacionalidade = mysqli_real_escape_string($this->conexao, $dados["Nacionalidade"]);

		$sqlNacionalidade = mysqli_query($this->conexao, "SELECT * FROM nacionalidade WHERE nome = '" . $nacionalidade . "';");

		$ctcb = mysqli_fetch_object($sqlNacionalidade);

		$nacionalidade = $ctcb->nacionalidade;

		$modelo = mysqli_real_escape_string($this->conexao, $dados["Modelo"]);

		$calibre = mysqli_real_escape_string($this->conexao, $dados["Calibre"]);

		$cor = mysqli_real_escape_string($this->conexao, $dados["Cor"]);

		$cabo = mysqli_real_escape_string($this->conexao, $dados["Cabo"]);

		$dimensao = mysqli_real_escape_string($this->conexao, $dados["Dimensao"]);

		$carregamento = mysqli_real_escape_string($this->conexao, $dados["Carregamento"]);

		$numeroCanos = mysqli_real_escape_string($this->conexao, $dados["NumeroCanos"]);

		$comprimentoCano = mysqli_real_escape_string($this->conexao, $dados["ComprimentoCano"]);

		$unidade = mysqli_real_escape_string($this->conexao, $dados["Unidade"]);

		$almaCano = mysqli_real_escape_string($this->conexao, $dados["AlmaCano"]);

		$numeroRaias = mysqli_real_escape_string($this->conexao, $dados["NumeroRaias"]);

		$numeroCanos = mysqli_real_escape_string($this->conexao, $dados["NumeroCanos"]);

		$sentidoRaia = mysqli_real_escape_string($this->conexao, $dados["SentidoRaia"]);

		$funcionanento = mysqli_real_escape_string($this->conexao, $dados["Funcionamento"]);

		$acabamento = mysqli_real_escape_string($this->conexao, $dados["Acabamento"]);

		$numeroSigma = mysqli_real_escape_string($this->conexao, $dados["NumeroSigma"]);

		$diaCRAF = mysqli_real_escape_string($this->conexao, $dados["DiaCRAF"]);

		$mesCRAF = mysqli_real_escape_string($this->conexao, $dados["MesCRAF"]);

		$anoCRAF = mysqli_real_escape_string($this->conexao, $dados["AnoCRAF"]);

		$vencimentoCRAF = $anoCRAF . "-" . $mesCRAF . "-" . $diaCRAF;

		$sobressalentes = mysqli_real_escape_string($this->conexao, $dados["Sobressalentes"]);

		mysqli_query($this->conexao, "INSERT INTO atirador_arma

                                      VALUES(null,

                                        '0',

                                        '" . $_SESSION["IdUsuario"] . "',

                                        '" . $descricao . "',

                                        '" . $serie . "',

                                        '" . $fabricante . "',

                                        '" . $calibre . "',

                                        '" . $cor . "',

                                        '" . $cabo . "',

                                        '" . $dimensao . "',

                                        '" . $sigma . "',

                                        '" . $vencimentoCRAF . "',

                                        '" . $modelo . "',

                                        '" . $nacionalidade . "',

                                        '" . $carregamento . "',

                                        '" . $numeroCanos . "',

                                        '" . $comprimentoCano . "',

                                        '" . $unidade . "',

                                        '" . $almaCano . "',

                                        '" . $numeroRaias . "',

                                        '" . $sentidoRaia . "',

                                        '" . $funcionanento . "',

                                        '" . $acabamento . "',

                                        '" . $anoFabricacao . "',

                                        '" . $sobressalentes . "');");

		if (mysqli_affected_rows($this->conexao) > 0) {

			$idArma = mysqli_insert_id($this->conexao);

			$idCod = $this->codificar($idArma);

			mysqli_query($this->conexao, "UPDATE atirador_arma SET id_cod_arma = '" . $idCod . "' WHERE atirador_arma = '" . $idArma . "';");

			$_SESSION["Sucesso"] = time() + 10;

			return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/armas/'</script>";

		} else {

			$_SESSION["Erro"] = time() + 10;

		}

	}

	/**

	 * Método para editar as armas dos atiradores

	 * Encontra-se na página editar-armas.php

	 * @access public

	 * @param array $dados

	 * @return true

	 */

	public function editarArmasAtirador(array $dados) {

		$key = mysqli_real_escape_string($this->conexao, $dados["Key"]);

		$descricao = mysqli_real_escape_string($this->conexao, $dados["Descricao"]);

		$serie = mysqli_real_escape_string($this->conexao, $dados["Serie"]);

		$fabricante = mysqli_real_escape_string($this->conexao, $dados["Fabricante"]);

		$anoFabricacao = mysqli_real_escape_string($this->conexao, $dados["AnoFabricacao"]);

		$nacionalidade = mysqli_real_escape_string($this->conexao, $dados["Nacionalidade"]);

		$sqlNacionalidade = mysqli_query($this->conexao, "SELECT * FROM nacionalidade WHERE nome = '" . $nacionalidade . "';");

		$ctcb = mysqli_fetch_object($sqlNacionalidade);

		$nacionalidade = $ctcb->nacionalidade;

		$modelo = mysqli_real_escape_string($this->conexao, $dados["Modelo"]);

		$calibre = mysqli_real_escape_string($this->conexao, $dados["Calibre"]);

		$cor = mysqli_real_escape_string($this->conexao, $dados["Cor"]);

		$cabo = mysqli_real_escape_string($this->conexao, $dados["Cabo"]);

		$dimensao = mysqli_real_escape_string($this->conexao, $dados["Dimensao"]);

		$carregamento = mysqli_real_escape_string($this->conexao, $dados["Carregamento"]);

		$numeroCanos = mysqli_real_escape_string($this->conexao, $dados["NumeroCanos"]);

		$comprimentoCano = mysqli_real_escape_string($this->conexao, $dados["ComprimentoCano"]);

		$unidade = mysqli_real_escape_string($this->conexao, $dados["Unidade"]);

		$almaCano = mysqli_real_escape_string($this->conexao, $dados["AlmaCano"]);

		$numeroRaias = mysqli_real_escape_string($this->conexao, $dados["NumeroRaias"]);

		$numeroCanos = mysqli_real_escape_string($this->conexao, $dados["NumeroCanos"]);

		$sentidoRaia = mysqli_real_escape_string($this->conexao, $dados["SentidoRaia"]);

		$funcionanento = mysqli_real_escape_string($this->conexao, $dados["Funcionamento"]);

		$acabamento = mysqli_real_escape_string($this->conexao, $dados["Acabamento"]);

		$numeroSigma = mysqli_real_escape_string($this->conexao, $dados["NumeroSigma"]);

		$diaCRAF = mysqli_real_escape_string($this->conexao, $dados["DiaCRAF"]);

		$mesCRAF = mysqli_real_escape_string($this->conexao, $dados["MesCRAF"]);

		$anoCRAF = mysqli_real_escape_string($this->conexao, $dados["AnoCRAF"]);

		$vencimentoCRAF = $anoCRAF . "-" . $mesCRAF . "-" . $diaCRAF;

		$sobressalentes = mysqli_real_escape_string($this->conexao, $dados["Sobressalentes"]);

		mysqli_query($this->conexao, "UPDATE atirador_arma

                                    SET descricao = '" . $descricao . "',

                                        numero_serie = '" . $serie . "',

                                        fabricante = '" . $fabricante . "',

                                        calibre = '" . $calibre . "',

                                        cor = '" . $cor . "',

                                        cabo = '" . $cabo . "',

                                        dimensao = '" . $dimensao . "',

                                        sigma = '" . $numeroSigma . "',

                                        vencimento_craf = '" . $vencimentoCRAF . "',

                                        modelo = '" . $modelo . "',

                                        nacionalidade = '" . $nacionalidade . "',

                                        capacidade_carregamento = '" . $carregamento . "',

                                        alma_cano = '" . $almaCano . "',

                                        numero_raias = '" . $numeroRaias . "',

                                        funcionamento = '" . $funcionanento . "',

                                        acabamento = '" . $acabamento . "',

                                        ano_fabricacao = '" . $anoFabricacao . "',

                                        sobressalentes = '" . $sobressalentes . "'

                                    WHERE id_cod_arma = '" . $key . "'");

		if (mysqli_affected_rows($this->conexao) > 0) {

			$_SESSION["Sucesso"] = time() + 5;

			return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/editar-arma/?" . $key . "'</script>";

		}

	}

	/**

	 * Método visualiza as declarações do atirador

	 * @access public

	 * @param string $tipo

	 * @return string $visualizar

	 */

	public function visualizarDeclaracoes($tipo) {

		//echo "SELECT *, DATE_FORMAT(data,'%d/%m/%Y') AS DataDeclaracao FROM declaracao WHERE atirador = '".$_SESSION["IdUsuario"]."' AND tipo = '".$tipo."' ORDER BY Data DESC;";

		$sql = mysqli_query($this->conexao, "SELECT *, DATE_FORMAT(data,'%d/%m/%Y') AS DataDeclaracao FROM declaracao WHERE atirador = '" . $_SESSION["IdUsuario"] . "' AND tipo = '" . $tipo . "' ORDER BY Data DESC;");

		$visualizar = "<table class=\"table table-striped table-bordered\">

                     <thead>

                       <tr>

                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Data</td>

                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Autenticação</td>

                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Reemitir</td>

                       </tr>

                     </thead>

                     <tbody>";

		if (mysqli_num_rows($sql) == 0) {

			$visualizar .= "<tr><td colspan='3' style='color: red; text-align: center'>Ainda não existem declarações emitidas!</td></tr>";

		} else {

			while ($ctcb = mysqli_fetch_object($sql)) {

				$visualizar .= "<tr>

                               <td style=\"text-align: center\">" . $ctcb->DataDeclaracao . "</td>

                               <td style=\"text-align: center\">" . $ctcb->autenticacao . "</td>

                               <td style=\"text-align: center\"><a href=\"!#\" class=\"btn btn-primary autenticacao\" data-toggle=\"modal\" data-id='" . $ctcb->autenticacao . "' data-target=\".bd-example-modal-lg\">Reemitir <i class=\"fas fa-share\"></i></button></td>

                             </tr>";

			}

		}

		/*MODIFICANDO COMO CHAMA A DECLARAÇÃO*/

		$visualizar .= "</tbody>";

		$visualizar .= "</table>";

		return $visualizar;

	}

	/**

	 * Método para listar a filiação

	 * Encontra-se na página filiacao-entidade.php

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	/*

		   public function filiacaoEntidade(){

		     $sql = mysqli_query($this->conexao,"SELECT *, DATE_FORMAT(data,'%d/%m/%Y') AS DataDeclaracao FROM declaracao WHERE atirador = '".$_SESSION["IdUsuario"]."' AND tipo = 'F' ORDER BY Data DESC;");

		     $visualizar = "<table class=\"table table-striped table-bordered\">

		                     <thead>

		                       <tr>

		                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Data</td>

		                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Autenticação</td>

		                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Reemitir</td>

		                       </tr>

		                     </thead>

		                     <tbody>";

		     while($ctcb = mysqli_fetch_object($sql)){

		          $visualizar .= "<tr>

		                           <td style=\"text-align: center\">".$ctcb->DataDeclaracao."</td>

		                           <td style=\"text-align: center\">".$ctcb->autenticacao."</td>

		                           <td style=\"text-align: center\"><a href=\"!#\" class=\"btn btn-primary autenticacao\" data-toggle=\"modal\" data-id='".$ctcb->autenticacao."' data-target=\".bd-example-modal-lg\">Reemitir <i class=\"fas fa-share\"></i></button></td>

		                         </tr>";

		     }

		     $visualizar .= "</tbody>";

		     $visualizar .= "</table>";

		     return $visualizar;

		   }

	*/

	/**

	 * Método para listar a filiação

	 * Encontra-se na página filiacao-entidade.php

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	/*

		   public function habitualidade(){

		     $sql = mysqli_query($this->conexao,"SELECT *, DATE_FORMAT(data,'%d/%m/%Y') AS DataDeclaracao FROM declaracao WHERE atirador = '".$_SESSION["IdUsuario"]."' AND tipo = 'H' ORDER BY Data DESC;");

		     $visualizar = "<table class=\"table table-striped table-bordered\">

		                     <thead>

		                       <tr>

		                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Data</td>

		                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Autenticação</td>

		                         <td style=\"background-color: #4682B4; text-align: center; color: #FFF\">Reemitir</td>

		                       </tr>

		                     </thead>

		                     <tbody>";

		     while($ctcb = mysqli_fetch_object($sql)){

		          $visualizar .= "<tr>

		                           <td style=\"text-align: center\">".$ctcb->DataDeclaracao."</td>

		                           <td style=\"text-align: center\">".$ctcb->autenticacao."</td>

		                           <td style=\"text-align: center\"><a href=\"!#\" class=\"btn btn-primary autenticacao\" data-toggle=\"modal\" data-id='".$ctcb->autenticacao."' data-target=\".bd-example-modal-lg\">Reemitir <i class=\"fas fa-share\"></i></button></td>

		                         </tr>";

		     }

		     $visualizar .= "</tbody>";

		     $visualizar .= "</table>";

		     return $visualizar;

		   }

	*/

	/**

	 * Método conta se existe declaração ativa

	 * Encontra-se na página filiacao-entidade.php

	 * @access public

	 * @param string $tipo

	 * @return int $contar

	 */

	public function contarDeclaracao($tipo, $data) {

		if ($data != null) {

			$sql = mysqli_query($this->conexao, "SELECT * FROM declaracao WHERE atirador = '" . $_SESSION["IdUsuario"] . "' AND DATE_FORMAT(data,'%Y-%m-%d') = '" . $data . "' AND tipo = '" . $tipo . "';");

		} else {

			$sql = mysqli_query($this->conexao, "SELECT * FROM declaracao WHERE atirador = '" . $_SESSION["IdUsuario"] . "' AND tipo = '" . $tipo . "';");

		}

		$contar = mysqli_num_rows($sql);

		return $contar;

	}

	/**

	 * Método para gera o código de autenticação para a filiação

	 * Encontra-se na página codigo-autenticacao.php

	 * @access public

	 * @param string $key

	 * @return string $codigo

	 */

	public function gerarCodigoAutenticacao($key, $tipo) {

		switch ($tipo) {

		case '1':$codigoTipo = 'F';
			break; // Filiação

		case '2':$codigoTipo = 'H';
			break; // Habilidade

		case '3':$codigoTipo = 'R';
			break; // Ranking

		case '4':$codigoTipo = 'M';
			break; // Modalidade

		case '5':$codigoTipo = 'G';
			break; // Guia de Trafego

		case '6':$codigoTipo = 'A';
			break; // Atividade

		}

		if ($key != null) {

			$sqlDeclaracao = mysqli_query($this->conexao, "SELECT * FROM declaracao WHERE atirador = '" . $_SESSION["IdUsuario"] . "' AND tipo = '" . $codigoTipo . "' AND autenticacao = '" . $key . "';");

			$ctcb = mysqli_fetch_object($sqlDeclaracao);

			$codigo = $ctcb->autenticacao;

		} else {

			$sql = mysqli_query($this->conexao, "SELECT * FROM atirador JOIN estado WHERE atirador.estado=estado.estado AND atirador = '" . $_SESSION["IdUsuario"] . "';");

			$ctcb = mysqli_fetch_object($sql);

			$id = sprintf("%06d", $_SESSION["IdUsuario"]);

			$codigo = date("Ymd") . $id . '0' . $tipo;

			$sqlDeclaracao = mysqli_query($this->conexao, "SELECT * FROM declaracao WHERE atirador = '" . $_SESSION["IdUsuario"] . "' AND tipo = '" . $codigoTipo . "' AND autenticacao = '" . $codigo . "';");

			if (mysqli_fetch_object($sqlDeclaracao) == 0) {

				mysqli_query($this->conexao, "INSERT INTO declaracao VALUES(null,'" . $_SESSION["IdUsuario"] . "',NOW(),'" . $codigoTipo . "','" . $codigo . "','0');");

			}

		}

		return $codigo;

	}

	/**

	 * Método mostra os eventos que o atirador participou

	 * @access public

	 * @param int $idAtirador

	 * @return string $visualizar

	 */

	public function eventoAtirador($idAtirador) {

		$sql = mysqli_query($this->conexao, "SELECT * FROM evento_atirador EVEAT INNER JOIN evento EVE ON EVEAT.evento = EVE.evento WHERE EVEAT.atirador = '" . $idAtirador . "';");

		$visualizar = "<table width=\"100%\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\">";

		$visualizar .= "<tr>";

		$visualizar .= "<td style='text-align: center'>Evento</td>";

		$visualizar .= "<td style='text-align: center'>Data Início</td>";

		$visualizar .= "<td style='text-align: center'>Data Término</td>";

		$visualizar .= "<td style='text-align: center'>Prova</td>";

		$visualizar .= "</tr>";

		while ($ctcb = mysqli_fetch_object($sql)) {

			$sqlProva = mysqli_query($this->conexao, "SELECT * FROM evento_prova EVPROVA INNER JOIN prova PRO ON EVPROVA.prova = PRO.prova WHERE EVPROVA.evento_prova = '" . $ctcb->evento_prova . "'");

			$ctcbProva = mysqli_fetch_object($sqlProva);

			list($anoI, $mesI, $diaI) = explode("-", $ctcb->data_inicio);

			$dataInicio = $diaI . '/' . $mesI . '/' . $anoI;

			list($anoT, $mesT, $diaT) = explode("-", $ctcb->data_termino);

			$dataTermino = $diaT . '/' . $mesT . '/' . $anoT;

			$visualizar .= "<tr>";

			$visualizar .= "<td style='font-size: 13px'>" . $ctcb->nome . "</td>";

			$visualizar .= "<td style='font-size: 13px'>" . $dataInicio . "</td>";

			$visualizar .= "<td style='font-size: 13px'>" . $dataTermino . "</td>";

			$visualizar .= "<td style='font-size: 13px'>" . $ctcbProva->nome . "</td>";

			$visualizar .= "</tr>";

		}

		$visualizar .= "</table>";

		return $visualizar;

	}

	/*Funçao de teste para inserção das informações na declaração de habitualidade*/

	public function provasHabitualidade($idAtirador) {

		/* codigo original $sql = mysqli_query($this->conexao,"SELECT * FROM evento_atirador EVEAT INNER JOIN evento EVE ON EVEAT.evento = EVE.evento WHERE EVEAT.atirador = '".$idAtirador."' GROUP BY '".$idAtirador."' ;");*/

		//SQL PARA INSERIR AS PROVAS E EVENTOS NA DECLARAÇÃO DE HABITUALIDADE

		//dalmo $sql = mysqli_query($this->conexao, "select * from evento_atirador EVATIRADOR inner join evento EVE on EVATIRADOR.evento=EVE.evento inner join evento_local EVALOCAL on EVALOCAL.evento=EVE.evento inner join clube CLUBE on EVALOCAL.clube=CLUBE.clube where EVATIRADOR.atirador='".$idAtirador."' order by evento_atirador;");*/

		/*ULTIMO SQL ALTERADO 11/04/2020 - Evandro Machado $sql = mysqli_query($this->conexao, "select * from evento_atirador EVATIRADOR inner join evento EVE on EVATIRADOR.evento=EVE.evento inner join evento_local EVALOCAL on EVALOCAL.evento=EVE.evento where EVATIRADOR.atirador='".$idAtirador."'group by evento_atirador;");*/

//Este select foi incluso em 11/04/2020 para trazer as provas dos atletas

		$sql = mysqli_query($this->conexao, "SELECT * FROM evento EVENTO
INNER JOIN evento_atirador EVATIRADOR
ON EVENTO.evento = EVATIRADOR.evento
INNER JOIN evento_local EVLOCAL
ON EVLOCAL.evento_local = EVATIRADOR.evento_local
INNER JOIN clube CL on EVLOCAL.clube=CL.clube
WHERE EVATIRADOR.atirador ='" . $idAtirador . "'  group by evento_atirador;");

		$visualizar = "<table width=\"100%\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\">";
		$visualizar .= "<tr>";
		$visualizar .= "<td colspan='4' style='text-align: center'><strong> &nbsp;&nbsp;Calibre de uso permitido&nbsp;&nbsp; </strong></td>";
		$visualizar .= "<td colspan='4' style='text-align: center'><strong> &nbsp;&nbsp;EVENTO NACIONAL&nbsp;&nbsp; </strong></td>";
		$visualizar .= "</tr>";
		$visualizar .= "<tr>";
		$visualizar .= "<td style='text-align: center'><strong>&nbsp; LOCAL &nbsp;</strong></td>"; //Antes o nome era: Evento
		$visualizar .= "<td style='text-align: center'><strong> &nbsp;&nbsp;DATA&nbsp;&nbsp; </strong></td>"; // Desabilitei a data fim
		$visualizar .= "<td style='text-align: center'><strong> &nbsp;Sigma&nbsp; </strong></td>"; // Desabilitei a data fim
		$visualizar .= "<td style='text-align: center'><strong> &nbsp;Qt. Munição&nbsp; </strong></td>"; // Desabilitei a data fim
		$visualizar .= "<td style='text-align: center'><strong>&nbsp;&nbsp;</strong></td>"; //Este campo era: Prova
		$visualizar .= "</tr>";
		while ($ctcb = mysqli_fetch_object($sql)) {
			$sigmasQ = $ctcb->sigma;
			$dataHoraQ = $ctcb->dataHora;
			$disparosQ = $ctcb->disparos;
			$calibreQ = $ctcb->calibre;
			$numVirgulas = substr_count($sigmasQ, ',');
			$numRegistrosSigmas = $numVirgulas + 1;
			$sigmas = explode(", ", substr($registro, strrpos($registro, ", ") + 2));

			$sigmasArray = explode(",", $sigmasQ);
			$dataHoraArray = explode(",", $dataHoraQ);
			$disparosArray = explode(",", $disparosQ);
			$calibreArray = explode(",", $calibreQ);

			// Atribua cada elemento do array a uma variável individual
			foreach ($sigmasArray as $key => $sigma) {
				${'sigma' . ($key + 1)} = trim($sigma);
			}
			foreach ($dataHoraArray as $key => $dataHora) {
				${'dataHora' . ($key + 1)} = trim($dataHora);
			}
			foreach ($disparosArray as $key => $disparos) {
				${'disparos' . ($key + 1)} = trim($disparos);
			}
			foreach ($calibreArray as $key => $calibre) {
				${'calibre' . ($key + 1)} = trim($calibre);
			}

			
			$sqlProva = mysqli_query($this->conexao, "SELECT PRO.nome FROM evento_prova EVPROVA 
			INNER JOIN prova PRO ON EVPROVA.prova = PRO.prova 
			WHERE EVPROVA.evento_prova = '" . $ctcb->evento_prova . "' ");
			$ctcbProva = mysqli_fetch_object($sqlProva);
			list($anoI, $mesI, $diaI) = explode("-", $ctcb->data_inicio);
			$dataInicio = $diaI . '/' . $mesI . '/' . $anoI;
			list($anoT, $mesT, $diaT) = explode("-", $ctcb->data_termino);
			$dataTermino = $diaT . '/' . $mesT . '/' . $anoT;
			
			if($calibre1 !="" && $calibre1 != "restrito"){
			$visualizar .= "<tr>";
			$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
			//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
			$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora1 . "&nbsp;</td>";
			$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma1 . "&nbsp;</td>";
			$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos1 . "</td>";
			$nomeProva = $ctcbProva->nome;
			$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcbProva->nome .  "</td>";
			$visualizar .= "</tr>";
			}

			// CASO HOUVER SIGMA 2
			if($calibre2 !="" && $calibre2 != "restrito"){
				$visualizar .= "<tr>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
				//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora2 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma2 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos2 . "</td>";
				$nomeProva = $ctcbProva->nome;
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcbProva->nome .  "</td>";
				$visualizar .= "</tr>";
			}

			// CASO HOUVER SIGMA 3
			if($calibre3 !="" && $calibre3 != "restrito"){
				$visualizar .= "<tr>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
				//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora3 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma3 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos3 . "</td>";
				$nomeProva = $ctcbProva->nome;
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcbProva->nome .  "</td>";
				$visualizar .= "</tr>";
			}

			// CASO HOUVER SIGMA 4
			if($calibre4 !="" && $calibre4 != "restrito"){
				$visualizar .= "<tr>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
				//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora4 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma4 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos4 . "</td>";
				$nomeProva = $ctcbProva->nome;
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcbProva->nome .  "</td>";
				$visualizar .= "</tr>";
			}

			// CASO HOUVER SIGMA 5
			if($calibre5 !="" && $calibre5 != "restrito"){
				$visualizar .= "<tr>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
				//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora5 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma5 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos5 . "</td>";
				$nomeProva = $ctcbProva->nome;
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcbProva->nome .  "</td>";
				$visualizar .= "</tr>";
			}

			
		}
			
		
		$visualizar .= "</table>";
		return $visualizar;
	}


	public function provasHabitualidadeRestrito($idAtirador) {
		
		$sql = mysqli_query($this->conexao, "SELECT * FROM evento EVENTO
INNER JOIN evento_atirador EVATIRADOR
ON EVENTO.evento = EVATIRADOR.evento
INNER JOIN evento_local EVLOCAL
ON EVLOCAL.evento_local = EVATIRADOR.evento_local
INNER JOIN clube CL on EVLOCAL.clube=CL.clube
WHERE EVATIRADOR.atirador ='" . $idAtirador . "'  group by evento_atirador;");

		$visualizar = "<table width=\"100%\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\">";
		$visualizar .= "<tr>";
		$visualizar .= "<td colspan='4' style='text-align: center'><strong> &nbsp;&nbsp;Calibre de uso restrito&nbsp;&nbsp; </strong></td>";
		$visualizar .= "<td colspan='4' style='text-align: center'><strong> &nbsp;&nbsp;EVENTO NACIONAL&nbsp;&nbsp; </strong></td>";
		$visualizar .= "</tr>";
		$visualizar .= "<tr>";
		$visualizar .= "<td style='text-align: center'><strong>&nbsp; LOCAL &nbsp;</strong></td>"; //Antes o nome era: Evento
		$visualizar .= "<td style='text-align: center'><strong> &nbsp;&nbsp;DATA&nbsp;&nbsp; </strong></td>"; // Desabilitei a data fim
		$visualizar .= "<td style='text-align: center'><strong> &nbsp;Sigma&nbsp; </strong></td>"; // Desabilitei a data fim
		$visualizar .= "<td style='text-align: center'><strong> &nbsp;Qt. Munição&nbsp; </strong></td>"; // Desabilitei a data fim
		$visualizar .= "<td style='text-align: center'><strong>&nbsp;&nbsp;</strong></td>"; //Este campo era: Prova
		$visualizar .= "</tr>";
		while ($ctcb = mysqli_fetch_object($sql)) {
			$sigmasQ = $ctcb->sigma;
			$dataHoraQ = $ctcb->dataHora;
			$disparosQ = $ctcb->disparos;
			$calibreQ = $ctcb->calibre;
			$numVirgulas = substr_count($sigmasQ, ',');
			$numRegistrosSigmas = $numVirgulas + 1;
			$sigmas = explode(", ", substr($registro, strrpos($registro, ", ") + 2));

			$sigmasArray = explode(",", $sigmasQ);
			$dataHoraArray = explode(",", $dataHoraQ);
			$disparosArray = explode(",", $disparosQ);
			$calibreArray = explode(",", $calibreQ);

			// Atribua cada elemento do array a uma variável individual
			foreach ($sigmasArray as $key => $sigma) {
				${'sigma' . ($key + 1)} = trim($sigma);
			}
			foreach ($dataHoraArray as $key => $dataHora) {
				${'dataHora' . ($key + 1)} = trim($dataHora);
			}
			foreach ($disparosArray as $key => $disparos) {
				${'disparos' . ($key + 1)} = trim($disparos);
			}
			foreach ($calibreArray as $key => $calibre) {
				${'calibre' . ($key + 1)} = trim($calibre);
			}

			
			$sqlProva = mysqli_query($this->conexao, "SELECT PRO.nome FROM evento_prova EVPROVA 
			INNER JOIN prova PRO ON EVPROVA.prova = PRO.prova 
			WHERE EVPROVA.evento_prova = '" . $ctcb->evento_prova . "' ");
			$ctcbProva = mysqli_fetch_object($sqlProva);
			list($anoI, $mesI, $diaI) = explode("-", $ctcb->data_inicio);
			$dataInicio = $diaI . '/' . $mesI . '/' . $anoI;
			list($anoT, $mesT, $diaT) = explode("-", $ctcb->data_termino);
			$dataTermino = $diaT . '/' . $mesT . '/' . $anoT;
			
			if($calibre1 !="" && $calibre1 != "permitido"){
			$visualizar .= "<tr>";
			$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
			//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
			$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora1 . "&nbsp;</td>";
			$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma1 . "&nbsp;</td>";
			$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos1 . "</td>";
			$nomeProva = $ctcbProva->nome;
			$nomeProva = $ctcbProva->nome ?? '';
$nomeProvaLimitado = (mb_strlen($nomeProva) > 50) ? mb_substr($nomeProva, 0, 47) . '...' : $nomeProva;

$visualizar .= "<td style='font-size:11px;text-align:center; white-space: nowrap;'>" . $nomeProvaLimitado . "</td>";

			$visualizar .= "</tr>";
			}

			// CASO HOUVER SIGMA 2
			if($calibre2 !="" && $calibre2 != "permitido"){
				$visualizar .= "<tr>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
				//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora2 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma2 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos2 . "</td>";
				$nomeProva = $ctcbProva->nome;
				$nomeProva = $ctcbProva->nome ?? '';
$nomeProvaLimitado = (mb_strlen($nomeProva) > 50) ? mb_substr($nomeProva, 0, 47) . '...' : $nomeProva;

$visualizar .= "<td style='font-size:11px;text-align:center; white-space: nowrap;'>" . $nomeProvaLimitado . "</td>";

				$visualizar .= "</tr>";
			}

			// CASO HOUVER SIGMA 3
			if($calibre3 !="" && $calibre3 != "permitido"){
				$visualizar .= "<tr>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
				//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora3 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma3 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos3 . "</td>";
				$nomeProva = $ctcbProva->nome;
				$nomeProva = $ctcbProva->nome ?? '';
$nomeProvaLimitado = (mb_strlen($nomeProva) > 50) ? mb_substr($nomeProva, 0, 47) . '...' : $nomeProva;

$visualizar .= "<td style='font-size:11px;text-align:center; white-space: nowrap;'>" . $nomeProvaLimitado . "</td>";

				$visualizar .= "</tr>";
			}

			// CASO HOUVER SIGMA 4
			if($calibre4 !="" && $calibre4 != "permitido"){
				$visualizar .= "<tr>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
				//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora4 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma4 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos4 . "</td>";
				$nomeProva = $ctcbProva->nome;
				$nomeProva = $ctcbProva->nome ?? '';
$nomeProvaLimitado = (mb_strlen($nomeProva) > 50) ? mb_substr($nomeProva, 0, 47) . '...' : $nomeProva;

$visualizar .= "<td style='font-size:11px;text-align:center; white-space: nowrap;'>" . $nomeProvaLimitado . "</td>";

				$visualizar .= "</tr>";
			}

			// CASO HOUVER SIGMA 5
			if($calibre5 !="" && $calibre5 != "permitido"){
				$visualizar .= "<tr>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $ctcb->nome . "</td>";
				//$visualizar .= "<td style='font-size: 13px'>".$dataInicio."</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $dataHora5 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>&nbsp;" . $sigma5 . "&nbsp;</td>";
				$visualizar .= "<td style='font-size:11px;text-align:center;'>" . $disparos5 . "</td>";
				$nomeProva = $ctcbProva->nome;
				$nomeProva = $ctcbProva->nome ?? '';
$nomeProvaLimitado = (mb_strlen($nomeProva) > 50) ? mb_substr($nomeProva, 0, 47) . '...' : $nomeProva;

$visualizar .= "<td style='font-size:11px;text-align:center; white-space: nowrap;'>" . $nomeProvaLimitado . "</td>";

				$visualizar .= "</tr>";
			}

			
		}
			
		
		$visualizar .= "</table>";
		return $visualizar;

	}

	/*Término da função para inserção das informações na declaração de habitualidade*/

	/**

	 * Método lista os eventos em um combox

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

//Teste de função

	public function exibeProvas($idAtirador) {

		$sql = mysqli_query($this->conexao, "select * from evento_atirador EVATIRADOR inner join evento EVE on EVATIRADOR.evento=EVE.evento inner join evento_local EVALOCAL on EVALOCAL.evento=EVE.evento inner join clube CLUBE on EVALOCAL.clube=CLUBE.clube where EVATIRADOR.atirador='" . $idAtirador . "'group by evento_atirador;");

		while ($exibeProvas = mysqli_fetch_assoc($sql));

		echo "<table>";

		echo "<tr><td>Nome:</td>";

		echo "<td>" . $exibeProvas["evento"] . "</td></tr>";

	}

//Fim teste função

	public function listarEventosCombox() {

		$visualizar = "<div class=\"form-groupz\">";

		$visualizar .= "<label>Eventos:</label>";

		$visualizar .= "<select class=\"form-control\" id='id_categoria' name=\"Eventos\">";

		$visualizar .= "<option value=\"\">Selecione uma opção</option>";

		$dataInicio = mktime(23, 59, 59, date('m'), date('d') - date('j'), date('Y'));

		$data = date("Y-m-d", $dataInicio);

		$sqlEventos = mysqli_query($this->conexao, "SELECT evento,idcodevento,nome,data_inicio, data_termino, DATE_FORMAT(data_inicio,'%d/%m') AS DataInicio, DATE_FORMAT(data_termino,'%d/%m/%Y') AS DataTermino FROM evento");

		while ($ctcb = mysqli_fetch_object($sqlEventos)) {

			if ($ctcb->data_termino > $data) {

				$visualizar .= "<option value='" . $ctcb->evento . "'>" . $ctcb->DataTermino . " - " . $ctcb->nome . "</option>";

			}

		}

		$visualizar .= "</select>";

		$visualizar .= "</div>";

		$visualizar .= "<div class=\"form-group\">

                        <label>Provas:</label>

                        <span class=\"carregando\">Aguarde, carregando...</span>

                        <select class=\"form-control\" name=\"Provas\" id='id_sub_categoria'>

                          <option value=\"\">Selecione uma opção acima</option>

                        </select>

                      </div>";

		return $visualizar;

	}

	/**

	 * Método list os certificados do Atirador

	 * @access public

	 * @param int $idAtirador

	 * @return string $visualizar

	 */

	public function listarEventosCertificado($idAtirador) {

		//  echo "SELECT *,EVENTO.evento,EVENTO.idcodevento,EVENTO.nome,EVENTO.data_inicio, EVENTO.data_termino, DATE_FORMAT(EVENTO.data_inicio,'%d/%m') AS DataInicio, DATE_FORMAT(EVENTO.data_termino,'%d/%m/%Y') AS DataTermino FROM evento EVENTO INNER JOIN evento_atirador EVATIRADOR ON EVENTO.evento = EVATIRADOR.evento WHERE atirador = '".$idAtirador."';";

		$dataInicio = mktime(23, 59, 59, date('m'), date('d') - date('j'), date('Y'));

		$data = date("Y-m-d", $dataInicio);

		$sqlEventos = mysqli_query($this->conexao, "SELECT *,EVENTO.evento,EVENTO.idcodevento,EVENTO.nome,EVENTO.data_inicio, EVENTO.data_termino, DATE_FORMAT(EVENTO.data_inicio,'%d/%m') AS DataInicio, DATE_FORMAT(EVENTO.data_termino,'%d/%m/%Y') AS DataTermino FROM evento EVENTO INNER JOIN evento_atirador EVATIRADOR ON EVENTO.evento = EVATIRADOR.evento WHERE EVATIRADOR.atirador = '" . $idAtirador . "' GROUP BY EVATIRADOR.evento;");

		if (mysqli_num_rows($sqlEventos) == 0) {

			$visualizar = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Você ainda não participou de nenhum evento</div>';

		} else {

			$visualizar = "<div class=\"form-group\">";

			$visualizar .= "<label>Eventos:</label>";

			$visualizar .= "<select class=\"form-control\" id='id_categoria' name=\"Eventos\">";

			$visualizar .= "<option value=\"\">Selecione uma opção</option>";

			while ($ctcb = mysqli_fetch_object($sqlEventos)) {

				//  if($ctcb->data_termino > $data)

				//  {

				$visualizar .= "<option value='" . $ctcb->evento . "'>" . $ctcb->DataTermino . " - " . $ctcb->nome . "</option>";

				//  }

			}

			$visualizar .= "</select>";

			$visualizar .= "</div>";

			$visualizar .= "<div class=\"form-group\">

                          <label>Provas:</label>

                          <span class=\"carregando\">Aguarde, carregando...</span>

                          <select class=\"form-control\" name=\"Provas\" id='id_sub_categoria'>

                            <option value=\"\">Selecione uma opção acima</option>

                          </select>

                        </div>";

		}

		return $visualizar;

	}

	/**

	 * Método lista as subcategorias da categoria dentro dos certificados

	 * @access public

	 * @param int $idCategoria

	 * @return ArrayObject json_encode($sub_categorias_post)

	 */

	public function listarSubCategoriaCertificado($idCategoria) {

		//  $sqlSubCategoria = mysqli_query($this->conexao,"SELECT * FROM evento EVENTO INNER JOIN evento_prova EVPROVA ON EVENTO.evento = EVPROVA.evento INNER JOIN prova PROVA ON PROVA.prova = EVPROVA.prova WHERE EVPROVA.evento = '".$idCategoria."';");

		//$sqlSubCategoria = mysqli_query($this->conexao,"SELECT * FROM evento_prova EVPROVA INNER JOIN prova PROVA ON PROVA.prova = EVPROVA.prova WHERE EVPROVA.evento_prova = '".$idCategoria."';");

		$sqlSubCategoria = mysqli_query($this->conexao, "SELECT * FROM evento_atirador WHERE evento = '" . $idCategoria . "' AND atirador = '" . $_SESSION["IdUsuario"] . "';");

		//$sqlSubcateg = mysqli_query($this->conexao,"SELECT * FROM evento_atirador A INNER JOIN evento_prova B ON A.evento_prova = B.evento_prova INNER JOIN prova C ON B.prova = C.prova WHERE B.evento_prova = '".$ctcb['evento_prova']."';");

		while ($ctcb = mysqli_fetch_assoc($sqlSubCategoria)) {

			$sqlClube = mysqli_query($this->conexao, "SELECT * FROM evento_prova EVPROVA INNER JOIN prova PROVA ON EVPROVA.prova = PROVA.prova WHERE EVPROVA.evento_prova = '" . $ctcb['evento_prova'] . "';");

			while ($ctcbClube = mysqli_fetch_assoc($sqlClube)) {

				$sub_categorias_post[] = array(

					'id' => $ctcb['evento_prova'],

					'nomeProva' => $ctcbClube['nome'],

				);

			}

		}

		return (json_encode($sub_categorias_post));

	}

	/**

	 * Método mostra a pontuação do atirador

	 * @access public

	 * @param int $idAtirador

	 * @param string $evento

	 * @param string $prova

	 * @return string $lugar

	 */

	public function verPontuacao($idAtirador, $evento, $prova) {

		$sql = mysqli_query($this->conexao, "SELECT *,GREATEST(serie1, serie2, serie3, serie4,serie5) AS MaiorNota FROM `evento_atirador` WHERE evento = '" . $evento . "' and evento_prova = " . $prova . " ORDER BY MaiorNota DESC;");

		$posicao = 1;

		$lugar = '';

		while ($l = mysqli_fetch_object($sql)) {

			$sqlAtirador = mysqli_query($this->conexao, "SELECT * FROM atirador WHERE atirador = '" . $l->atirador . "';");

			$jmAtirador = mysqli_fetch_object($sqlAtirador);

			if ($jmAtirador->atirador == $idAtirador) {

				$lugar .= $posicao . "º lugar<br>";

			}

			$posicao++;

		}

		return $lugar;

	}

/**

 * Método mostra a pontuação do atirador

 * @access public

 * @param int $idAtirador

 * @param string $dataInicio

 * @param string $dataFinal

 * @return string $visualizar

 */

	/*INICIO DO SQL PARA A DECLARAÇÃO DE HABITUALIDADE*/

	/*FIM DA FUNÇÃO PARA NOVA DECLARAÇÃO DE HABITUALIDADE*/

	public function curriculo($idAtirador, $dataInicio, $dataFinal) {

		$dataInicio = mysqli_real_escape_string($this->conexao, $dataInicio);

		$dataFinal = mysqli_real_escape_string($this->conexao, $dataFinal);

		list($diaI, $mesI, $anoI) = explode("/", $dataInicio);

		$dataInicio = $anoI . "-" . $mesI . "-" . $anoI;

		list($diaF, $mesF, $anoF) = explode("/", $dataFinal);

		$dataFinal = $anoF . "-" . $mesF . "-" . $anoF;

		$sql = mysqli_query($this->conexao, "SELECT * FROM evento EVENTO INNER JOIN evento_atirador EVATIRADOR ON EVENTO.evento = EVATIRADOR.evento WHERE EVATIRADOR.atirador = '" . $idAtirador . "' AND (EVENTO.data_inicio >= '" . $dataInicio . "' AND EVENTO.data_termino <= '" . $dataFinal . "');");

		if (mysqli_num_rows($sql) == 0) {

			$visualizar = '<tr><td colspan="3" style="color: red; text-align: center">Ainda não existem resultados!</td></tr>';

		} else {

			$visualizar = '';

			while ($ctcb = mysqli_fetch_object($sql)) {

				//echo "SELECT * FROM evento_prova EVPROVA INNER JOIN prova PROVA ON EVPROVA.prova = EVPROVA.prova WHERE EVPROVA.evento_prova = '".$ctcb->evento_prova."' AND EVPROVA.evento = '".$ctcb->evento."';";

				$sqlEventoProva = mysqli_query($this->conexao, "SELECT * FROM evento_prova WHERE evento_prova = '" . $ctcb->evento_prova . "' AND evento = '" . $ctcb->evento . "';");

				$ctcbEvento = mysqli_fetch_object($sqlEventoProva);

				$sqlProva = mysqli_query($this->conexao, "SELECT * FROM prova WHERE prova = '" . $ctcbEvento->prova . "';");

				$ctcbProva = mysqli_fetch_object($sqlProva);

				//  $sqlProva = mysqli_query($this->conexao,"SELECT * FROM evento_prova EVPROVA INNER JOIN prova PROVA ON EVPROVA.prova = EVPROVA.prova WHERE EVPROVA.evento_prova = '".$ctcb->evento_prova."' AND EVPROVA.evento = '".$ctcb->evento."';");

				//  $ctcbProva = mysqli_fetch_object($sqlProva);

				$sqlCategoria = mysqli_query($this->conexao, "SELECT * FROM categoria WHERE categoria = '" . $ctcb->categoria . "';");

				$ctcbCategoria = mysqli_fetch_object($sqlCategoria);

				$visualizar .= '<tr>

                            <td style="border: 1px #999 solid;"><small>' . $ctcbProva->nome . '</small></td>

                            <td style="border: 1px #999 solid;"><small>' . $ctcb->nome . '</small></td>

                            <td style="border: 1px #999 solid;" align="center"><small>' . $this->verPontuacao($idAtirador, $ctcb->evento, $ctcbEvento->evento_prova) . '</small></td>

                          </tr>';

			}

		}

		return $visualizar;

	}

	/**

	 * Mostra a nota final do atirador

	 * @access public

	 * @param int $idAtirador

	 * @param string @evento

	 * @return string $nota

	 */

	public function notaFinal($idAtirador, $evento) {

		//echo "SELECT GREATEST(serie1, serie2, serie3, serie4) AS MaiorNota FROM evento_atirador WHERE atirador = '".$idAtirador."' AND evento = '".$evento."';";

		$sql = mysqli_query($this->conexao, "SELECT GREATEST(serie1, serie2, serie3, serie4,serie5) AS MaiorNota FROM evento_atirador WHERE atirador = '" . $idAtirador . "' AND evento = '" . $evento . "';");

		$ctcb = mysqli_fetch_object($sql);

		$nota = $ctcb->MaiorNota;

		return $nota;

	}

	/**

	 * Método mostra a listagem da subcategoria em um combox

	 * @access public

	 * @param int $idCategoria

	 * @return ArrayObject json_encode($sub_categorias_post)

	 */

	public function listarSubCategoriaCombox($idCategoria) {

		$sqlSubCategoria = mysqli_query($this->conexao, "SELECT * FROM evento EVENTO INNER JOIN evento_prova EVPROVA ON EVENTO.evento = EVPROVA.evento INNER JOIN prova PROVA ON PROVA.prova = EVPROVA.prova WHERE EVPROVA.evento = '" . $idCategoria . "';");

		while ($ctcb = mysqli_fetch_assoc($sqlSubCategoria)) {

			$sub_categorias_post[] = array(

				'id' => $ctcb['idcodevento'],

				'nomeProva' => $ctcb['nome'],

			);

		}

		return (json_encode($sub_categorias_post));

	}

/**

 * Método mostra a listagem dos resultados da subcategoria

 * @access public

 * @param int $idCategoria

 * @return ArrayObject json_encode($sub_categorias_post)

 */

	public function listarSubCategoriaResultados($idCategoria) {

		$sqlSubCategoria = mysqli_query($this->conexao, "SELECT * FROM evento EVENTO INNER JOIN evento_prova EVPROVA ON EVENTO.evento = EVPROVA.evento INNER JOIN prova PROVA ON PROVA.prova = EVPROVA.prova WHERE EVPROVA.evento = '" . $idCategoria . "';");

		while ($ctcb = mysqli_fetch_assoc($sqlSubCategoria)) {

			$sub_categorias_post[] = array(

				'id' => $ctcb['prova'],

				'nomeProva' => $ctcb['nome'],

			);

		}

		return (json_encode($sub_categorias_post));

	}

	/**

	 * Método lista os anos dos eventos

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	public function listarAnosEventos() {

		$sql = mysqli_query($this->conexao, "SELECT DATE_FORMAT(data_inicio,'%Y') AS AnoEvento FROM evento GROUP BY DATE_FORMAT(data_inicio,'%Y');");

		$visualizar = '';

		while ($ctcb = mysqli_fetch_object($sql)) {

			//  if($ctcb->AnoEvento <> date("Y"))

			//  {

			$visualizar .= '<a href="' . $this->caminhoAbsoluto() . '/resultados/?' . $ctcb->AnoEvento . '" style="font-weight: bold">' . $ctcb->AnoEvento . '</a> &nbsp;';

			//}

		}

		return $visualizar;

	}

	/**

	 * Método lista os eventos do ano

	 * @access public

	 * @param string $ano

	 * @return string $visualizar

	 */

	public function listarEventosAno($ano) {

		$sql = mysqli_query($this->conexao, "SELECT idcodevento,nome,DATE_FORMAT(data_inicio,'%d/%Y') AS DataInicio, DATE_FORMAT(data_termino,'%d/%Y') AS DataFinal FROM evento WHERE YEAR(data_inicio) = '" . $ano . "' ORDER BY data_inicio DESC;");

		if (mysqli_num_rows($sql) == 0) {

			$visualizar = '<tr><td>Nenhum evento encontrado no ano ' . $ano . '</td></tr>';

		} else {

			$visualizar = '';

			while ($ctcb = mysqli_fetch_object($sql)) {

				$visualizar .= '<tr>

         		               <td height="35px" valign="top"><i class="fas fa-caret-right"></i> ' . $ctcb->DataInicio . ' a ' . $ctcb->DataFinal . ' - <a href="#!" id="btnVisualizarCasas" data-id="' . $ctcb->idcodevento . '">' . $ctcb->nome . '</a></td>

         		              </tr>';

			}

			$visualizar .= " <div class=\"modal fade\" id=\"casasRegionais\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"\" aria-hidden=\"true\">

                          <div class=\"modal-dialog modal-lg\">

                              <div>

                                  <div id=\"tela\">

                                  </div>

                              </div>

                          </div>

                      </div>";

			$visualizar .= "<script>

                         $(\"tr\").on('click',\"#btnVisualizarCasas\", function(){

                             var posts = $(this).attr('data-id');

                             $.post('" . $this->caminhoAbsoluto() . "/visualizar-resultados/', {key: posts}, function(retorno){

                              // console.log(retorno);

                                    $(\"#casasRegionais\").modal({ backdrop: 'static' });

                                    $(\"#tela\").html(retorno);

                             });

                         });

                         </script>";

		}

		return $visualizar;

	}

/**

 * Método lista os resultados do evento

 * @access public

 * @param string $evento

 * @return string $visualizar

 */

	public function listarResultadosEventos($evento) {

		$sql = mysqli_query($this->conexao, "SELECT *, GREATEST(EVATIRADOR.serie1, EVATIRADOR.serie2, EVATIRADOR.serie3, EVATIRADOR.serie4) AS MaiorNota FROM evento EVENTO

                                             INNER JOIN evento_atirador EVATIRADOR

                                             ON EVENTO.evento = EVATIRADOR.evento

                                             INNER JOIN evento_local EVLOCAL

                                             ON EVLOCAL.evento_local = EVATIRADOR.evento_local

                                            WHERE EVENTO.evento = '" . $evento . "' ORDER BY MaiorNota DESC;");

		$c = 1;

		while ($ctcb = mysqli_fetch_object($sql)) {

			$sqlClube = mysqli_query($this->conexao, "SELECT  * FROM clube WHERE clube = '" . $ctcb->clube . "';");

			$ctcbClube = mysqli_fetch_object($sqlClube);

			$sqlAtirador = mysqli_query($this->conexao, "SELECT  * FROM atirador WHERE atirador = '" . $ctcb->atirador . "';");

			$ctcbAtirador = mysqli_fetch_object($sqlAtirador);

			$visualizar .= '<tr height="20" style="font-size: 13px">

        										<td height="15" align="center"><a class="texto9p">' . $c . '</a></td>

        										<td align="center"><a class="texto9p">' . $ctcbAtirador->codigo . '</a></td>

        										<td align="center"><a class="texto9p">' . $ctcbClube->nome . '</a></td>

        										<td><a class="texto9p">' . $ctcbAtirador->nome . '</a></td>

        										<td width="25" align="right"><a class="texto9p">' . $ctcb->serie1 . '</a></td>

        										<td width="25" align="right"><a class="texto9p">' . $ctcb->serie2 . '</a></td>

        										<td width="25" align="right"><a class="texto9p">' . $ctcb->serie3 . '</a></td>

        										<td width="25" align="right"><a class="texto9p">' . $ctcb->serie4 . '</a></td>

        										<td width="25" align="right"><a class="texto9p">' . $ctcb->serie5 . '</a></td>

        										<td width="25" align="right"><a class="texto9p">' . $ctcb->MaiorNota . '</a></td>

    								       </tr>';

			$c++;

		}

		return $visualizar;

	}

	/**

	 * Método mostra os anos do calendário

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	public function listarAnosCalendario() {

		$sql = mysqli_query($this->conexao, "SELECT DATE_FORMAT(data_inicio,'%Y') AS AnoEvento FROM evento GROUP BY DATE_FORMAT(data_inicio,'%Y');");

		$visualizar = '';

		while ($ctcb = mysqli_fetch_object($sql)) {

			//  if($ctcb->AnoEvento <> date("Y"))

			//  {

			$visualizar .= '<a href="' . $this->caminhoAbsoluto() . '/calendario/?' . $ctcb->AnoEvento . '" style="font-weight: bold">' . $ctcb->AnoEvento . '</a> &nbsp;';

			//}

		}

		return $visualizar;

	}

	/**

	 * Método mostra o calendário por ano

	 * @access public

	 * @param string $ano

	 * @return string $visualizar

	 */

	public function visualizarCalendario($ano) {

		if ($ano == "") {

			$ano = date("Y");

		} else {

			$ano = $ano;

		}

		$sql = mysqli_query($this->conexao, "SELECT *,DATE_FORMAT(data_inicio,'%d/%Y') AS DataInicio, DATE_FORMAT(data_termino,'%d/%Y') AS DataFinal, MONTH(data_inicio) AS MesEvento, DAY(data_inicio) AS DiaInicio, DAY(data_termino) AS DiaTermino FROM evento WHERE YEAR(data_inicio) = '" . $ano . "' ORDER BY data_inicio ASC;");

		$visualizar = '<table width="100%" border="0" cellpadding="0" cellspacing="0">

                     <tr>

                     <td height="22" colspan="2"><a class="texto24p">

                       <strong> <i class="far fa-calendar-check"></i> ' . $ano . ' </strong>

                     </a>

                   </td>

                     <td height="22" align="right"><a class="texto11p">Escolha o ano:&nbsp;</a>';

		$visualizar .= $this->listarAnosCalendario();

		$visualizar .= '</td>

                         </tr>

                       </table>';

		while ($ctcb = mysqli_fetch_object($sql)) {

			//  echo $ctcb->evento."<br>";

			$mesExtenso = $this->mesExtenso($ctcb->MesEvento);

			$visualizar .= '<table width="100%" border="1" style="margin-top: 10px">';

			$visualizar .= '<tr>';

			$visualizar .= '<td style="text-transform: uppercase;background: #4682B4; color: #FFF">' . $mesExtenso . '</td>';

			$mesBusca = $ctcb->MesEvento;

			//  $ano = $ano;

			setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');

			$mes = date('m', strtotime($ano . '-' . $mesBusca));

			$ano = date('Y', strtotime($ano . '-' . $mesBusca));

			$qtdMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);

			for ($dias = 1; $dias <= $qtdMes; $dias++) {

				$diaSemana = strftime("%A", strtotime($ano . '-' . $mes . '-' . $dias));

				if ($diaSemana != "sábado" && $diaSemana != 'domingo') {

					$visualizar .= "<td style='background: #4682B4; color: #fff; font-size: 13px; font-weight: bold; text-align: center; width: 13px'>" . $dias . "</td>";

				} else {

					$visualizar .= "<td style='background: #59A22D; color: #FFF; font-size: 13px; font-weight: bold; text-align: center; width: 13px'>" . $dias . "</td>";

				}

			}

			$visualizar .= '<tr>';

			$visualizar .= '<td style="width: 380px; background: #FAFAFA">' . $ctcb->nome . '</td>';

			for ($col = 1; $col <= $qtdMes; $col++) {

				if ($col >= $ctcb->DiaInicio and $col <= $ctcb->DiaTermino) {

					$visualizar .= '<td style="background: #FAFAFA; text-align: center; font-size: 13px; font-weight: bold">X</td>';

				} else {

					$visualizar .= '<td style="background: #FAFAFA; text-align: center; font-size: 13px; font-weight: bold"></td>';

				}

			}

			$visualizar .= '</tr></table>';

		}

		return $visualizar;

	}

	/**

	 * Método lista os anos dos Vìdeos

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	public function listarAnosVideos() {

		$sql = mysqli_query($this->conexao, "SELECT DATE_FORMAT(data,'%Y') AS AnoEvento FROM video GROUP BY DATE_FORMAT(data,'%Y');");

		$visualizar = '';

		while ($ctcb = mysqli_fetch_object($sql)) {

			//  if($ctcb->AnoEvento <> date("Y"))

			//  {

			$visualizar .= '<a href="' . $this->caminhoAbsoluto() . '/videos/?' . $ctcb->AnoEvento . '" style="font-weight: bold">' . $ctcb->AnoEvento . '</a> &nbsp;';

			//}

		}

		return $visualizar;

	}

	/**

	 * Método lista os vídeos por ano

	 * @access public

	 * @param string $ano

	 * @return string $visualizar

	 */

	public function listarVideosAno($ano) {

		$sqlVideos = mysqli_query($this->conexao, "SELECT *, DATE_FORMAT(data,'%d/%m/%Y') AS DataVideo FROM video WHERE YEAR(data) = '" . $ano . "';");

		if (mysqli_num_rows($sqlVideos) > 0) {

			$visualizar = '';

			while ($ctcb = mysqli_fetch_object($sqlVideos)) {

				$visualizar .= '<h5>' . $ctcb->titulo . '</h5>';

				$visualizar .= '<small>' . $ctcb->dataVideo . '</small>';

				$visualizar .= '<iframe id="ytplayer" type="text/html" width="640" height="360" src="https://www.youtube.com/embed/' . $ctcb->embed . '?autoplay=0&origin=https://www.ctcb.org.br" frameborder="0"/></iframe>';

			}

			return $visualizar;

		}

	}

	/**

	 * Método mostra a listagem das fotos

	 * @access public

	 * @param string null

	 * @return string $visualizar

	 */

	public function listarAnosFotos() {

		$sql = mysqli_query($this->conexao, "SELECT DATE_FORMAT(data,'%Y') AS AnoEvento FROM galeria GROUP BY DATE_FORMAT(data,'%Y');");

		$visualizar = '';

		while ($ctcb = mysqli_fetch_object($sql)) {

			//  if($ctcb->AnoEvento <> date("Y"))

			//  {

			$visualizar .= '<a href="' . $this->caminhoAbsoluto() . '/fotos/?' . $ctcb->AnoEvento . '" style="font-weight: bold">' . $ctcb->AnoEvento . '</a> &nbsp;';

			//}

		}

		return $visualizar;

	}

	/**

	 * Método lista as fotos por ano

	 * @access public

	 * @param string $ano

	 * @return string $visualizar

	 */

	public function listarFotosAno($ano) {

		$sqlFotos = mysqli_query($this->conexao, "SELECT *, DATE_FORMAT(data,'%d/%m/%Y') AS DataFoto FROM galeria WHERE YEAR(data) = '" . $ano . "';");

		if (mysqli_num_rows($sqlFotos) > 0) {

			$visualizar = '<table class="table table-striped">';

			$visualizar .= '<tr>';

			$visualizar .= '<td style="background: #59a22d; color: #FFF; text-align: center; font-weight: bold">Data</td><td style="background: #59a22d; color: #FFF; text-align: center; font-weight: bold">Título</td>';

			$visualizar .= '</tr>';

			while ($ctcb = mysqli_fetch_object($sqlFotos)) {

				$visualizar .= '<tr>';

				$visualizar .= '<td>' . $ctcb->DataFoto . '</td>';

				$visualizar .= '<td><a href="' . $this->caminhoAbsoluto() . '/galeria-fotos/?' . $ctcb->galeria . '">' . $ctcb->descricao . '</a></td>';

				$visualizar .= '</tr>';

			}

			$visualizar .= '</table>';

			return $visualizar;

		}

	}

	/**

	 * Método mostra as fotos separadas por galeria

	 * @access public

	 * @param int $galeria

	 * @return string $visualizar

	 */

	public function mostrarFotos($galeria) {

		$sqlNovo = mysqli_query($this->conexao, "SELECT * FROM galeria_fotos_novo WHERE IdGaleria = '" . $galeria . "';");

		$ctcbNovo = mysqli_fetch_object($sqlNovo);

		if (mysqli_num_rows($sqlNovo) > 0) {

			$diretorio = $this->caminhoAbsoluto() . '/' . $ctcbNovo->Diretorio . '/';

			$sqlGaleria = mysqli_query($this->conexao, "SELECT *, DATE_FORMAT(data, '%d/%m/%Y') AS DataFoto FROM galeria  WHERE galeria = '" . $galeria . "';");

			$ctcbGaleria = mysqli_fetch_object($sqlGaleria);

			$visualizar = '<h4>' . $ctcbGaleria->descricao . '</h4>';

			$visualizar .= '<small>' . $ctcbGaleria->DataFoto . '</small>';

			$visualizar .= '<div class="row">';

			$sqlFotos = mysqli_query($this->conexao, "SELECT * FROM galeria_fotos_novo WHERE IdGaleria = '" . $galeria . "';");

			while ($ctcb = mysqli_fetch_object($sqlFotos)) {

				$foto = $diretorio . '/' . $ctcb->Fotos;

				$visualizar .= '<div class="col-md-3" style="padding: 10px"><img src="' . $foto . '" style="width: 220px" class="img-thumbnail"></div>';

			}

			$visualizar .= '</div>';

		} else {

			$sql = mysqli_query($this->conexao, "SELECT * FROM galeria_fotos WHERE galeria = '" . $galeria . "';");

			$codigo = sprintf("%03d", $galeria);

			$diretorio = $this->caminhoAbsoluto() . '/galeria/' . $codigo;

			$sqlGaleria = mysqli_query($this->conexao, "SELECT *, DATE_FORMAT(data, '%d/%m/%Y') AS DataFoto FROM galeria  WHERE galeria = '" . $galeria . "';");

			$ctcbGaleria = mysqli_fetch_object($sqlGaleria);

			$visualizar = '<h4>' . $ctcbGaleria->descricao . '</h4>';

			$visualizar .= '<small>' . $ctcbGaleria->DataFoto . '</small>';

			$visualizar .= '<div class="row">';

			while ($ctcb = mysqli_fetch_object($sql)) {

				$codigoFoto = sprintf("%03d", $ctcb->foto);

				$foto = $diretorio . '/thumbnails/g' . $codigo . '_' . $codigoFoto . '.jpg';

				$visualizar .= '<div class="col-md-3" style="padding: 10px"><img src="' . $foto . '" style="width: 220px" class="img-thumbnail"></div>';

			}

			$visualizar .= '</div>';

		}

		return $visualizar;

	}

	/**

	 * Método lista os anos das notícias

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	public function listarAnosNoticias() {

		$sql = mysqli_query($this->conexao, "SELECT DATE_FORMAT(data,'%Y') AS AnoEvento FROM noticia GROUP BY DATE_FORMAT(data,'%Y');");

		$visualizar = '';

		while ($ctcb = mysqli_fetch_object($sql)) {

			//  if($ctcb->AnoEvento <> date("Y"))

			//  {

			$visualizar .= '<a href="' . $this->caminhoAbsoluto() . '/noticias/?' . $ctcb->AnoEvento . '" style="font-weight: bold">' . $ctcb->AnoEvento . '</a> &nbsp;';

			//}

		}

		return $visualizar;

	}

	/**

	 * Método lista as notícias por ano

	 * @access public

	 * @param string $ano

	 * @return string $visualizar

	 */

	public function listarNoticiasAno($ano) {

		$sqlFotos = mysqli_query($this->conexao, "SELECT *, DATE_FORMAT(data,'%d/%m/%Y') AS DataNoticia FROM noticia WHERE YEAR(data) = '" . $ano . "';");

		if (mysqli_num_rows($sqlFotos) > 0) {

			$visualizar = '<table class="table table-striped">';

			$visualizar .= '<tr>';

			$visualizar .= '<td style="background: #59a22d; color: #FFF; text-align: center; font-weight: bold">Data</td><td style="background: #59a22d; color: #FFF; text-align: center; font-weight: bold">Título</td>';

			$visualizar .= '</tr>';

			while ($ctcb = mysqli_fetch_object($sqlFotos)) {

				$visualizar .= '<tr>';

				$visualizar .= '<td style="font-size: 14px">' . $ctcb->DataNoticia . '</td>';

				$visualizar .= '<td style="text-transform: uppercase; font-size: 14px"><a href="' . $this->caminhoAbsoluto() . '/detalhes-noticias/?' . $ctcb->id_noticia . '">' . $ctcb->titulo . '</a></td>';

				$visualizar .= '</tr>';

			}

			$visualizar .= '</table>';

			return $visualizar;

		}

	}

	/**

	 * Método mostra as fotos das notícias

	 * @access public

	 * @param int $idNoticia

	 * @return string $visualizar

	 */

	public function mostrarFotosNoticias($idNoticia) {

		$idNoticia = mysqli_real_escape_string($this->conexao, $idNoticia);

		$sql = mysqli_query($this->conexao, "SELECT * FROM fotos_noticias WHERE IdNoticias = '" . $idNoticia . "';");

		if (mysqli_num_rows($sql) > 0) {

			while ($ctcb = mysqli_fetch_object($sql)) {

				$diretorio = 'fotos_noticias';

				$fotos = $ctcb->Fotos;

				$legendas = $ctcb->Legenda;

				if (mysqli_num_rows($sql) > 1) {

					$imagemThumb = $this->gerarThumb($diretorio, $fotos);

					return '<div class="col-md-4"><img src="' . $diretorio . '/' . $imagemThumb . '" class="img-thumbnail"><br>' . $legendas . '</div>';

				} else {

					$imagemThumb = $this->gerarThumb($diretorio, $fotos);

					return '<img src="' . $diretorio . '/' . $imagemThumb . '" class="img-thumbnail"><br>' . $legendas;

				}

			}

		} else {

			$imagens = glob("imagens-noticias\/pequena\/{$idNoticia}(_\d+)?\.jpg");

			$visualizar = '';

			foreach ($imagens as $imagem) {

				$visualizar .= '<img src="' . $imagens . '">';

			}

		}

		return $visualizar;

	}

	/**

	 * Método mostra os arquivos daas notícias filtrados por notícias

	 * @access public

	 * @param int $idNoticia

	 * @return string $visualizar

	 */

	public function mostrarArquivosNoticias($idNoticia) {

		$sqlArquivos = mysqli_query($this->conexao, "SELECT * FROM arquivo_noticias WHERE IdNoticias = '" . $idNoticia . "';");

		if (mysqli_num_rows($sqlArquivos) > 0) {

			$sqlNoticias = mysqli_query($this->conexao, "SELECT * FROM noticia N INNER JOIN arquivo_noticias A ON N.id_noticia = A.IdNoticias WHERE N.id_noticia = '" . $idNoticia . "';");

			$ctcbNoticias = mysqli_fetch_object($sqlNoticias);

			$visualizar = '<div style="height: 20px; background-color:#999;"><a class="texto14b">DOWNLOADS</a></div><div align="left">';

			while ($ctcbArquivos = mysqli_fetch_object($sqlArquivos)) {

				$visualizar .= '<i class="fas fa-caret-right"></i> <a href="' . $this->caminhoAbsoluto() . '/arquivos/' . $ctcbArquivos->Arquivos . '" target="_blank"><b>' . $ctcbArquivos->Arquivos . '</b></a><a style="font-size: 14px"> </a><br>';

				if (mysqli_num_rows($sqlArquivos) > 1) {

					$visualizar .= '<hr>';

				}

			}

		} else {

			$sql = mysqli_query($this->conexao, 'SELECT * FROM noticia WHERE id_noticia = "' . $idNoticia . '" AND (arquivo1 <> "" OR arquivo2 <> "" OR arquivo3 <> "" OR arquivo4 <> "");');

			if (mysqli_num_rows($sql) > 0) {

				$visualizar = '<div style="height: 20px; background-color:#999;"><a class="texto14b">DOWNLOADS</a></div><div align="left">';

				while ($ctcb = mysqli_fetch_object($sql)) {

					$sqlArquivo = mysqli_query($this->conexao, "SELECT * FROM arquivo WHERE arquivo = '" . $ctcb->arquivo1 . "';");

					$ctcbArquivo = mysqli_fetch_object($sqlArquivo);

					$visualizar .= '<i class="fas fa-caret-right"></i> <a href="' . $this->caminhoAbsoluto() . '/arquivos/' . $ctcbArquivo->nome . '" target="_blank"><b>' . $ctcbArquivo->descricao . '</b></a><a style="font-size: 14px"></a><br>';

				}

			}

		}

		return $visualizar;

	}

	/**

	 * Método mostra as notícias na página inicial

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	public function noticiasPaginaInicial() {

		$sql = mysqli_query($this->conexao, "SELECT * FROM noticia ORDER BY id_noticia DESC LIMIT 6");

		if (mysqli_num_rows($sql) == 0) {

			$visualizar = '<span style="color: red"> Não existem notícias para serem mostradas!</span>';

		} else {

			$visualizar = '<ul style="font-size:13px">';

			while ($ctcb = mysqli_fetch_object($sql)) {

				$visualizar .= '<li><a href="' . $this->caminhoAbsoluto() . '/detalhes-noticias/?' . $ctcb->id_noticia . '" style="color: green; text-transform: uppercase">' . $ctcb->titulo . '</a></li>';

				$visualizar .= '<hr>';

			}

			$visualizar .= '</ul>';

			return $visualizar;

		}

	}

	/**

	 * Método busca o atirador pela busca inteligente

	 * Encontra-se na página atiradores.php

	 * @access public

	 * @param string $atirador

	 * @return ArrayObject json_encode($visualizar)

	 */

	public function buscarAtiradores($atirador) {

		$sql = mysqli_query($this->conexao, "SELECT *, COUNT(B.evento_atirador) AS ContarProvas

                                             FROM atirador A

                                                INNER JOIN evento_atirador B

                                                ON A.atirador = B.atirador

                                              WHERE A.nome

                                                LIKE '" . $atirador . "%'

                                                GROUP BY nome

                                                ORDER BY nome ASC

                                                LIMIT 7;");

		while ($ctcb = mysqli_fetch_assoc($sql)) {

			$sqlAtiradorPagto = mysqli_query($this->conexao, "SELECT * FROM atirador_pagamento C

                                                               INNER JOIN atirador D

                                                               ON C.atirador = D.atirador

                                                             WHERE C.valor_pago <> '0.00';");

			if (mysqli_num_rows($sqlAtiradorPagto) > 0) {

				$texto = ($ctcb["ContarProvas"] > 1) ? 'provas' : 'prova';

				$visualizar[] = $ctcb['nome'] . ' (participou de ' . $ctcb["ContarProvas"] . ' ' . $texto . ')';

			}

		}

		return json_encode($visualizar);

	}

	/**

	 * Método busca o atirador pela busca inteligente

	 * Encontra-se na página atiradores.php

	 * @access public

	 * @param string $atirador

	 * @return ArrayObject json_encode($visualizar)

	 */

	public function buscarAtiradoresCTCB($atirador) {

		$sql = mysqli_query($this->conexao, "SELECT * FROM atirador

                                                WHERE nome

                                                  LIKE '" . $atirador . "%'

                                                  GROUP BY nome

                                                  ORDER BY nome ASC

                                                  LIMIT 7;");

		while ($ctcb = mysqli_fetch_assoc($sql)) {

			$visualizar[] = $ctcb['nome'];

		}

		return json_encode($visualizar);

	}

	/**

	 * Método lista os despachantes dos atiradores

	 * @access public

	 * @param int $idDespachante

	 * @return string $visualizar

	 */

	public function listarAtiradoresDespachantes($idDespachante) {

		$sql = mysqli_query($this->conexao, "SELECT * FROM atirador WHERE despachante = '" . $idDespachante . "' ORDER BY nome;");

		$visualizar = '';

		while ($ctcb = mysqli_fetch_object($sql)) {

			$visualizar .= '<option value="' . $ctcb->atirador . '">' . $ctcb->nome . '</option>';

		}

		return $visualizar;

	}

	/**

	 * Método lista as provas dos altetas

	 * @access public

	 * @param int $idAtleta

	 * @param string $prova

	 * @return string $visualizar

	 */

	public function listarProvasAtletas($idAtleta, $prova) {

		$sqlProvas = mysqli_query($this->conexao, "SELECT *, D.nome AS NomeProva FROM atirador A

                                                       INNER JOIN evento_atirador B

                                                       ON A.atirador = B.atirador

                                                       INNER JOIN evento_prova C

                                                       ON C.evento_prova = B.evento_prova

                                                       INNER JOIN prova D

                                                       ON C.prova = D.prova

                                                    WHERE B.atirador = '" . $idAtleta . "'

                                                    GROUP BY C.prova;");

		$visualizar = '<div class="input-group">

                            <select class="form-control" name="Prova">

                            <label for="">Selecione a prova:</label><br>';

		while ($ctcb = mysqli_fetch_object($sqlProvas)) {

			$selected = ($ctcb->prova == $prova) ? 'selected' : null;

			//$idProva[] = $ctcb->evento_prova;

			$idProva[] = $ctcb->prova;

			//  $nomeProvaArray[] = $ctcb->NomeProva;

			//  $nomeProva = $ctcb->NomeProva;

			$visualizar .= '<option value="' . $ctcb->prova . '" ' . $selected . '>' . $ctcb->NomeProva . '</option>';

		}

		$visualizar .= '</select>

                              <div class="input-group-prepend">

                                <button type="submit" class="input-group-text" id="btnBuscar" style="cursor: pointer"><i class="fas fa-search"></i></button>

                             </div>

                          </div>';

		if ($prova == null) {

			$sqlProva = mysqli_query($this->conexao, "SELECT *, D.nome AS NomeProva FROM atirador A

                                                          INNER JOIN evento_atirador B

                                                          ON A.atirador = B.atirador

                                                          INNER JOIN evento_prova C

                                                          ON C.evento_prova = B.evento_prova

                                                          INNER JOIN prova D

                                                          ON C.prova = D.prova

                                                       WHERE B.atirador = '" . $idAtleta . "';");

		} else {

			$sqlProva = mysqli_query($this->conexao, "SELECT * FROM prova WHERE prova = '" . $prova . "';");

		}

		/*

			          $ids = array();

			          foreach($idProva as $idd)

			          {

			             $ids[] = $idd;

			          }

			          $id = implode(',',$ids);

		*/

		if ($prova == "") {

			$prova = $idProva[0];

		} else {

			$prova = $prova;

		}

		$sqlEventos = mysqli_query($this->conexao, "SELECT *,GREATEST(B.serie1, B.serie2, B.serie3, B.serie4,B.serie5) AS MaiorNota,

                                                       D.nome AS NomeEvento,

                                                       DATE_FORMAT(D.data_termino,'%d/%m/%Y') AS DataEvento

                                                     FROM atirador A

                                                       INNER JOIN evento_atirador B

                                                       ON A.atirador = B.atirador

                                                       INNER JOIN evento_prova C

                                                       ON C.evento_prova = B.evento_prova

                                                       INNER JOIN evento D

                                                       ON D.evento = C.evento

                                                    WHERE B.atirador = '" . $idAtleta . "'

                                                       AND C.prova = $prova;"); //AND C.prova in ($prova);");

		$contarProva = (mysqli_num_rows($sqlEventos) < 10) ? '0' . mysqli_num_rows($sqlEventos) : mysqli_num_rows($sqlEventos);

		$sqlProva = mysqli_query($this->conexao, "SELECT * FROM prova WHERE prova = '" . $prova . "';");

		$ctcbProva = mysqli_fetch_object($sqlProva);

		$nomeProva = $ctcbProva->nome;

		$visualizar .= '<div class="text-center" style="padding: 10px"><h5 style="text-transform: uppercase; font-weight: bold; margin-top: 10px"><i class="fas fa-calendar-check fa-lg"></i> ' . $nomeProva . '</h5></div>';

		$visualizar .= '<div class="row">';

		$visualizar .= '<div class="col-md-6"><div class="text-left">Total de provas: <strong>' . $contarProva . '</strong></div></div>';

		$visualizar .= '<div class="col-md-6"><div class="text-right"><small>RC: Resultado Completo</small></div></div>';

		$visualizar .= '</div>';

		$visualizar .= '<table class="table table-bordered table-striped">

                            <tr>

                              <th style="background: #C4CCDF; text-align: center">Total</th>

                              <th style="background: #C4CCDF; text-align: center">Data</th>

                              <th style="background: #C4CCDF; text-align: center">Evento</th>

                              <th style="background: #C4CCDF; text-align: center">RC</th>

                            </tr>

                          </thead>

                          <tbody>';

		while ($listar = mysqli_fetch_object($sqlEventos)) {

			$visualizar .= '<tr>';

			$visualizar .= '<td style="background: #FAFAFA; text-align: center">' . ceil($listar->MaiorNota) . '</td>';

			$visualizar .= '<td>' . $listar->DataEvento . '</td>';

			$visualizar .= '<td>' . $listar->NomeEvento . '</td>';

			$visualizar .= '<td style="text-align: center"><a href="#!" id="btnVisualizarCasas" data-id="' . $listar->evento . '" data-toggle="modal-3"><i class="fas fa-list-ol fa-lg"></i></a></td>';

			$visualizar .= '</tr>';

		}

		$visualizar .= '</tbody>

                          </table>';

		$visualizar .= "<div class=\"modal fade\" id=\"casasRegionais\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"\" aria-hidden=\"true\">

                             <div class=\"modal-dialog modal-lg\">

                                 <div>

                                     <div id=\"tela\">

                                     </div>

                                 </div>

                             </div>

                         </div>";

		$visualizar .= "<script>

                            $(\"table\").on('click',\"#btnVisualizarCasas\", function(){

                                var posts = $(this).attr('data-id');

                                var prova = " . $prova . ";";

		$visualizar .= "$.post('" . $this->caminhoAbsoluto() . "/listar-resultados/', {key: posts, prova: prova}, function(retorno){

                                 // console.log(retorno);

                                       $(\"#casasRegionais\").modal({ backdrop: 'static' });

                                       $(\"#tela\").html(retorno);

                                });

                            });

                            </script>";

		return $visualizar;

	}

	/**

	 * Método conta os eventos de cada atleta

	 * @access public

	 * @param string #evento

	 * @return int $contar

	 */

	public function contarAtletasEventos($evento) {

		$sql = mysqli_query($this->conexao, "SELECT * FROM evento EVENTO

                                             INNER JOIN evento_atirador EVATIRADOR

                                             ON EVENTO.evento = EVATIRADOR.evento

                                             INNER JOIN evento_local EVLOCAL

                                             ON EVLOCAL.evento_local = EVATIRADOR.evento_local

                                            WHERE EVENTO.evento = '" . $evento . "';");

		$contar = mysqli_num_rows($sql);

		return $contar;

	}

	/**

	 * Método lista os clubes cadastrados

	 * @access public

	 * @param null

	 * @return int $contar

	 */

	public function listarClubes() {

		//  $sqlClubesEstados = mysqli_query($this->conexao,"SELECT * FROM clube GROUP BY estado ORDER BY estado ASC");

		$sqlClubesEstados = mysqli_query($this->conexao, "SELECT * FROM clube C INNER JOIN estado E ON C.estado = E.estado WHERE C.status = 'A' GROUP BY C.estado ORDER BY E.nome ASC");

		$visualizar = '<table width="100%">';

		while ($ctcbEstados = mysqli_fetch_object($sqlClubesEstados)) {

			$sqlEstados = mysqli_query($this->conexao, "SELECT * FROM estado WHERE estado = '" . $ctcbEstados->estado . "';");

			$ctcbEstado = mysqli_fetch_object($sqlEstados);

			$visualizar .= '<tr><td><div style="background-color:#CCC; padding: 10px; text-transform: uppercase; font-weight: bold">' . $ctcbEstado->nome . '</div></td></tr>';

			$sqlClubes = mysqli_query($this->conexao, "SELECT * FROM clube WHERE status = 'A' AND estado = '" . $ctcbEstado->estado . "';");

			while ($ctcbClubes = mysqli_fetch_object($sqlClubes)) {

				$visualizar .= '<tr><td style="height: 10px"></td></tr>';

				$visualizar .= '<tr><td>';

				$visualizar .= '<div style="padding-left: 10px;">';

				$visualizar .= '<span style="text-transform: uppercase"><b><i class="fas fa-landmark"></i> ' . $ctcbClubes->nome . '</b></span><br>';

				if ($ctcbClubes->presidente != '') {

					$visualizar .= 'Presidente: ' . $ctcbClubes->presidente . '<br>';

				}

				if ($ctcbClubes->endereco != '') {

					$visualizar .= $ctcbClubes->endereco . ' - ' . $ctcbClubes->bairro . '<br>';

					if ($ctcbClubes->bairro != '') {

						$visualizar .= 'Bairro: ' . $ctcbClubes->bairro . '<br>';

					}

//Aqui eu coloco para exibir a cidade

					if ($ctcbClubes->cidade != '') {

						$visualizar .= 'Cidade: ' . $ctcbClubes->cidade . '<br>';

					}

//Término da exibição da cidade

					if ($ctcbClubes->cep != '') {

						$visualizar .= 'CEP: ' . $ctcbClubes->cep . '<br>';

					}

				}

				if ($ctcbClubes->telefone != '') {

					$visualizar .= 'Telefone: ' . $ctcbClubes->telefone . '<br>';

				}

				if ($ctcbClubes->email != '') {

					$visualizar .= 'E-mail: ' . $ctcbClubes->email . '<br>';

				}

				if ($ctcbClubes->site != '') {

					$visualizar .= 'Site: <a href="' . $ctcbClubes->site . '" target="_blank">' . $ctcbClubes->site . '<br>';

				}

				$visualizar .= '</div>';

				$visualizar .= '<hr>';

				$visualizar . '</td></tr>';

			}

		}

		$visualizar .= '</table>';

		return $visualizar;

	}

	/**

	 * Método lista os instrutores cadasrtados

	 * @access public

	 * @param null

	 * @return int $contar

	 */

	public function listarInstrutores() {

		$sqlClubesEstados = mysqli_query($this->conexao, "SELECT * FROM atirador A INNER JOIN estado E ON A.estado = E.estado GROUP BY A.estado ORDER BY E.nome ASC");

		$visualizar = '<table width="100%">';

		$visualizar .= '  <tr>

                         <td height="50" valign="top"><a class="texto18p"><b>Credenciados pela Confederação conforme artigo 100 da Portaria 51 Colog</b></a></td>

                       </tr>';

		while ($ctcbEstados = mysqli_fetch_object($sqlClubesEstados)) {

			$sqlEstados = mysqli_query($this->conexao, "SELECT * FROM estado WHERE estado = '" . $ctcbEstados->estado . "';");

			$ctcbEstado = mysqli_fetch_object($sqlEstados);

			$sql = mysqli_query($this->conexao, "SELECT * FROM atirador WHERE estado = '" . $ctcbEstado->estado . "' AND eh_instrutor = 'S';");

			if (mysqli_num_rows($sql) > 0) {

				$visualizar .= '<tr><td><div style="background-color:#CCC; padding: 10px; text-transform: uppercase; font-weight: bold">' . $ctcbEstado->nome . ' (' . mysqli_num_rows($sql) . ')</div></td></tr>';

				while ($ctcb = mysqli_fetch_object($sql)) {

					$visualizar .= '<tr><td style="height: 10px"></td></tr>';

					$visualizar .= '<tr><td>';

					$visualizar .= '<div style="padding-left: 10px;">';

					$visualizar .= '<span style="text-transform: uppercase"><b><i class="fas fa-landmark"></i> ' . $ctcb->nome . '</b></span><br>';

					if ($ctcb->bairro != '') {

						$visualizar .= $ctcb->bairro;

					}

					if ($ctcb->telefone_comercial != '') {

						$visualizar .= ' - Telefone: ' . $ctcb->telefone_comercial . '<br>';

					}

					if ($ctcbClubes->telefone_celular != '') {

						$visualizar .= ' - ' . $ctcb->telefone_celular . '<br>';

					}

					$visualizar .= '</div>';

					$visualizar .= '<hr>';

					$visualizar . '</td></tr>';

				}

			}

		}

		$visualizar .= '</table>';

		return $visualizar;

	}

	/**

	 * Método mostra as modalidades de cada atirador

	 * @access public

	 * @param int $idAtirador

	 * @return string $visualizar

	 */

	public function visualizarModalidades($idAtirador) {

		$visualizar = '';

		$sql = mysqli_query($this->conexao, "SELECT * FROM evento_atirador EVATIRADOR

                                          INNER JOIN evento_prova EVPROVA

                                          ON EVATIRADOR.evento_prova = EVPROVA.evento_prova

                                          WHERE EVATIRADOR.atirador = '" . $idAtirador . "'");

		while ($ctcb = mysqli_fetch_object($sql)) {

			$sqlProva = mysqli_query($this->conexao, "SELECT * FROM prova WHERE prova = '" . $ctcb->prova . "';");

			$ctcbProva = mysqli_fetch_object($sqlProva);

			$sqlModalidade = mysqli_query($this->conexao, "SELECT * FROM modalidade WHERE modalidade = '" . $ctcbProva->modalidade . "';");

			$ctcbModalidade = mysqli_fetch_object($sqlModalidade);

			$visualizar .= '<tr>

                          <td style="border: 1px #999 solid;"><a class="texto11p">' . $ctcbProva->nome . '</a></td>

                          <td style="border: 1px #999 solid;"><a class="texto11p">' . $ctcbModalidade->nome . '</a></td>

                          <td style="border: 1px #999 solid;"><a class="texto11p">' . $ctcbProva->arma . '</a></td>

                        </tr>';

		}

		return $visualizar;

	}

	/**

	 * Método mostra o vencimento dos atiradores

	 * @access public

	 * @param int $idAtirador

	 * @return string $validade

	 */

	public function validadeAtirador($idAtirador) {

		$sql = mysqli_query($this->conexao, "SELECT * FROM atirador_pagamento WHERE atirador = '" . $idAtirador . "' ORDER BY atirador_pagamento DESC LIMIT 1;");

		$ctcb = mysqli_fetch_object($sql);

		list($ano, $mes, $dia) = explode('-', $ctcb->data_pagamento);

		$validade = $dia . '/' . $mes . '/' . ($ano + 1);

		return $validade;

	}

	/**

	 * Método lista os resultados dos eventos em uma combox

	 * @access public

	 * @param null

	 * @return string $visualizar

	 */

	public function listarEventosResultadosCombox() {

		$visualizar = "<div class=\"form-group\">";

		$visualizar .= "<label>Eventos:</label>";

		$visualizar .= "<select class=\"form-control\" id='id_categoria' name=\"Eventos\">";

		$visualizar .= "<option value=\"\">Selecione uma opção</option>";

		$dataInicio = mktime(23, 59, 59, date('m'), date('d') - date('j'), date('Y') - 1);

		$data = date("Y-m-d", $dataInicio);

		$sqlEventos = mysqli_query($this->conexao, "SELECT evento,idcodevento,nome,data_inicio, data_termino, DATE_FORMAT(data_inicio,'%d/%m') AS DataInicio, DATE_FORMAT(data_termino,'%d/%m/%Y') AS DataTermino FROM evento");

		while ($ctcb = mysqli_fetch_object($sqlEventos)) {

			if ($ctcb->data_termino > $data) {

				$visualizar .= "<option value='" . $ctcb->evento . "'>" . $ctcb->DataTermino . " - " . $ctcb->nome . "</option>";

			}

		}

		$visualizar .= "</select>";

		$visualizar .= "</div>";

		$visualizar .= "<div class=\"form-group\">

                          <label>Provas:</label>

                          <span class=\"carregando\">Aguarde, carregando...</span>

                          <select class=\"form-control\" name=\"Provas\" id='id_sub_categoria'>

                            <option value=\"\">Selecione uma opção acima</option>

                          </select>

                        </div>";

		return $visualizar;

	}

	/**

	 * Método mostra os resultados da prova

	 * @access public

	 * @param string $evento

	 * @param string $prova

	 * @return string $visualizar

	 */

	public function resultadoProvas($evento, $prova) {

		$visualizar = '<div class="row">';

		$sqlEvento = mysqli_query($this->conexao, "SELECT * FROM evento WHERE evento = '" . $evento . "';");

		$ctcbEvento = mysqli_fetch_object($sqlEvento);

		list($anoI, $mesI, $diaI) = explode("-", $ctcbEvento->data_inicio);

		$dataInicio = $diaI . "/" . $mesI . "/" . $anoI;

		list($anoF, $mesF, $diaF) = explode("-", $ctcbEvento->data_termino);

		$dataFinal = $diaF . "/" . $mesF . "/" . $anoF;

		$sqlEvento = mysqli_query($this->conexao, "SELECT *, DATE_FORMAT(data_inicio,'%d/%m/%Y') AS DataInicio, DATE_FORMAT(data_termino,'%d/%m/%Y') AS DataFinal FROM evento WHERE evento = '" . $evento . "';");

		$peEvento = mysqli_fetch_object($sqlEvento);

		$visualizar .= '<h5 style="padding: 10px;">' . $peEvento->nome . ' - ' . $peEvento->DataInicio . ' a ' . $peEvento->DataFinal . '</h5>';

		$sqlProva = mysqli_query($this->conexao, "SELECT * FROM prova WHERE prova = '" . $prova . "';");

		$ctcbProva = mysqli_fetch_object($sqlProva);

		$visualizar .= '<p style="padding: 10px; margin-top: -10px; font-size: 16px; text-transform: uppercase; font-weight: bold"> ' . $ctcbProva->nome . '</span></p>';

		$visualizar .= '<table class="table table-sm table-bordered table-striped">

                      <thead>

                        <tr>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">CL</th>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">Cód.</th>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">Nome</th>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">R1</th>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">R2</th>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">R3</th>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">R4</th>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">R5</th>

                          <th scope="col" style="background: #4682B4; color: #FFF; text-align: center">RF</th>

                        </tr>

                      </thead>

                      <tbody>';

		/*

			    $sqlResultados = mysqli_query($this->conexao,"SELECT *,ATIRADOR.nome AS NomeAtirador,GREATEST(EVATIRADOR.serie1, EVATIRADOR.serie2, EVATIRADOR.serie3, EVATIRADOR.serie4, EVATIRADOR.serie5) AS MaiorNota

			                                        FROM atirador ATIRADOR

			                                        INNER JOIN evento_atirador EVATIRADOR ON ATIRADOR.atirador = EVATIRADOR.atirador

			                                        INNER JOIN evento_local EVLOCAL ON EVLOCAL.evento_local = EVATIRADOR.id_evento_local

			                                        WHERE  EVATIRADOR.evento = '".mysqli_real_escape_string($this->conexao,$evento)."'

			                                        AND EVLOCAL.clube = '".$clube."' ORDER BY MaiorNota DESC;");

		*/

		$sqlResultados = mysqli_query($this->conexao, "SELECT *,ATIRADOR.nome AS NomeAtirador, GREATEST(EVATIRADOR.serie1, EVATIRADOR.serie2, EVATIRADOR.serie3, EVATIRADOR.serie4, EVATIRADOR.serie5) AS MaiorNota

                                         FROM atirador ATIRADOR

                                         INNER JOIN evento_atirador EVATIRADOR ON ATIRADOR.atirador = EVATIRADOR.atirador

                                         INNER JOIN evento_prova EVPROVA ON EVPROVA.evento_prova = EVATIRADOR.evento_prova

                                         WHERE EVATIRADOR.evento = '" . $evento . "'

                                         AND EVPROVA.prova = '" . $prova . "'

                                         ORDER BY MaiorNota DESC;");

		if (mysqli_num_rows($sqlResultados) == 0) {

			$visualizar .= '<tr><td colspan="9" style="color: red; text-align: center">Nenhum resultado encontrado!</td></tr>';

		} else {

			$c = 1;

			while ($ctcbResultados = mysqli_fetch_object($sqlResultados)) {

				$visualizar .= '<tr>';

				$visualizar .= '<td>' . $c . 'º</td>';

				$visualizar .= '<td>' . $ctcbResultados->atirador . '</td>';

				$visualizar .= '<td>' . $ctcbResultados->NomeAtirador . '</td>';

				$visualizar .= '<td>' . $ctcbResultados->serie1 . '</td>';

				$visualizar .= '<td>' . $ctcbResultados->serie2 . '</td>';

				$visualizar .= '<td>' . $ctcbResultados->serie3 . '</td>';

				$visualizar .= '<td>' . $ctcbResultados->serie4 . '</td>';

				$visualizar .= '<td>' . $ctcbResultados->serie5 . '</td>';

				$visualizar .= '<td>' . $ctcbResultados->MaiorNota . '</td>';

				$c++;

			}

		}

		$visualizar .= '</tbody>';

		$visualizar .= '</table>';

		$visualizar .= '</div>';

		return $visualizar;

	}

	/**

	 * Método mostra o mês por extenso

	 * @access public

	 * @param string $mes

	 * @return string $mes

	 */

	public function mesExtenso($mes) {

		switch ($mes) {

		case '01':$mes = "janeiro";
			break;

		case '02':$mes = "fevereiro";
			break;

		case '03':$mes = "março";
			break;

		case '04':$mes = "abril";
			break;

		case '05':$mes = "maio";
			break;

		case '06':$mes = "junho";
			break;

		case '07':$mes = "julho";
			break;

		case '08':$mes = "agosto";
			break;

		case '09':$mes = "setembro";
			break;

		case '10':$mes = "outubro";
			break;

		case '11':$mes = "novembro";
			break;

		case '12':$mes = "dezembro";
			break;

		}

		return $mes;

	}

	/**

	 * Cria thumbnail das imagens

	 * @return $diretorioThumb.$codificarFoto

	 * @param $foto

	 */

	public function gerarThumb($diretorio, $foto) {

		$diretorioNormal = $diretorio;

		$diretorioThumb = $diretorio . "/thumb/";

		$fotoDir = $diretorioNormal . $foto;

		list($largura, $altura) = getimagesize($fotoDir);

		list($arquivo, $extensao) = explode(".", $foto);

		if ($extensao == "jpg" || $extensao == "jpeg" || $extensao == "JPG") {

			if ($largura > $altura) {

				$novaLargura = 296;

				$novaAltura = 219;

				$miniatura = imagecreatetruecolor($novaLargura, $novaAltura);

				$imagem = imagecreatefromjpeg($fotoDir);

				imagecopyresampled($miniatura, $imagem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

				imagejpeg($miniatura, $diretorioThumb . $foto, 90);

			}

			if ($altura > $largura) {

				$novaLargura = 170;

				$novaAltura = 240;

				$miniatura = imagecreatetruecolor($novaLargura, $novaAltura);

				$imagem = imagecreatefromjpeg($fotoDir);

				imagecopyresampled($miniatura, $imagem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

				imagejpeg($miniatura, $diretorioThumb . $foto, 90);

			}

		}

		if ($extensao == "png") {

			$miniaturaPNG = imagecreatetruecolor($novaLargura, $novaAltura);

			$imagemPNG = imagecreatefrompng($fotoDir);

			imagecopyresampled($miniaturaPNG, $imagemPNG, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

			imagepng($miniaturaPNG, $diretorioThumb . $foto, null, 90);

		}

		return $diretorioThumb . $foto;

	}

	/**

	 * Método visualiza genericamente todas as tabelas

	 * @access public

	 * @param string $tabela

	 * @param int $idTabela, $idBusca

	 * @return array

	 */

	public function visualizar($tabela, $idTabela, $idBusca) {

		$sqlVisualizar = mysqli_query($this->conexao, "SELECT * FROM " . $tabela . " WHERE " . $idTabela . " = '" . mysqli_real_escape_string($this->conexao, $idBusca) . "';");

		return array(mysqli_num_rows($sqlVisualizar), mysqli_fetch_object($sqlVisualizar));

	}

	/**

	 * Codifica a senha em 03 métodos de mão única. Sha1, MD5 invertido e crypt

	 * @param string $senhaUsuario

	 * @return string $codificar

	 */

	public function codificar($key) {

		$salt = "$" . md5(strrev($key)) . "%";

		$codifica = crypt($key, $salt);

		$codificar = hash('sha512', $codifica);

		return $codificar;

	}

	/**

	 * Método para sair do sistema. Encontra-se na página sair.php

	 * @access public

	 * @param null

	 * @return true

	 */

	public function sairSistema() {

		$_SESSION["Logado"] = false;

		unset($_SESSION["Logado"]);

		unset($_SESSION["IdUsuario"]);

		session_destroy();

		return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/'</script>";

	}

	/**

	 * Método cadastra o clube

	 * @access public

	 * @param array $dados

	 * @return true

	 */

	public function cadastrarClubes(array $dados) {

		$nomeClube = mysqli_real_escape_string($this->conexao, $dados['Nome']);

		$sigla = mysqli_real_escape_string($this->conexao, $dados['Sigla']);

		$senha = mysqli_real_escape_string($this->conexao, $dados['Senha']);

		$status = mysqli_real_escape_string($this->conexao, $dados['Status']);

		$telefoneComercial = mysqli_real_escape_string($this->conexao, $dados['TelefoneComercial']);

		$telefoneCelular = mysqli_real_escape_string($this->conexao, $dados['TelefoneCelular']);

		$email = mysqli_real_escape_string($this->conexao, $dados['Email']);

		$presidente = mysqli_real_escape_string($this->conexao, $dados['Presidente']);

		$fimMandato = mysqli_real_escape_string($this->conexao, $dados['FimMandato']);

		list($dia, $mes, $ano) = explode("/", $fimMandato);

		$fimMandato = $ano . "-" . $mes . "-" . $dia;

		$site = mysqli_real_escape_string($this->conexao, $dados['Site']);

		$cep = mysqli_real_escape_string($this->conexao, $dados['CEP']);

		$logradouro = mysqli_real_escape_string($this->conexao, $dados['Logradouro']);

		$numero = mysqli_real_escape_string($this->conexao, $dados['Numero']);

		$complemento = mysqli_real_escape_string($this->conexao, $dados['Complemento']);

		$bairro = mysqli_real_escape_string($this->conexao, $dados['Bairro']);

		$cidade = mysqli_real_escape_string($this->conexao, $dados['Cidade']);

		$estado = mysqli_real_escape_string($this->conexao, $dados['Estado']);

		$cpf_resp = mysqli_real_escape_string($this->conexao, $dados['Cpf_resp']);

		$email_resp = mysqli_real_escape_string($this->conexao, $dados['Email_resp']);

		$celular_resp = mysqli_real_escape_string($this->conexao, $dados['Celular_resp']);

		$cnpj_clube = mysqli_real_escape_string($this->conexao, $dados['Cnpj_clube']);

		$cr_clube = mysqli_real_escape_string($this->conexao, $dados['Cr_clube']);

		$cr_validade = mysqli_real_escape_string($this->conexao, $dados['Cr_validade']);

		list($dia, $mes, $ano) = explode("/", $cr_validade);

		$cr_validade = $ano . "-" . $mes . "-" . $dia;

		$rm_clube = mysqli_real_escape_string($this->conexao, $dados['Rm_clube']);

		$sql = mysqli_query($this->conexao, "SELECT * FROM estado WHERE sigla = '" . $estado . "';");

		$ctcb = mysqli_fetch_object($sql);

		$estado = $ctcb->estado;

		mysqli_query($this->conexao, "INSERT INTO clube(status,sigla,nome,endereco,numero,complemento,bairro,cidade,estado,cep,telefone,celular,presidente,fim_mandato,email,site,cpf_resp,email_resp,celular_resp,cnpj_clube,cr_clube,cr_validade,rm_clube,senha)

                                   VALUES ('" . $status . "',

                                           '" . $sigla . "',

                                           '" . $nomeClube . "',

                                           '" . $logradouro . "',

                                           '" . $numero . "',

                                           '" . $complemento . "',

                                           '" . $bairro . "',

                                           '" . $cidade . "',

                                           '" . $estado . "',

                                           '" . $cep . "',

                                           '" . $telefoneComercial . "',

                                           '" . $telefoneCelular . "',

                                           '" . $presidente . "',

                                           '" . $fimMandato . "',

                                           '" . $email . "',

                                           '" . $site . "',

                                           '" . $cpf_resp . "',

                                           '" . $email_resp . "',

                                           '" . $celular_resp . "',

                                           '" . $cnpj_clube . "',

                                           '" . $cr_clube . "',

                                           '" . $cr_validade . "',

                                           '" . $rm_clube . "',

                                           '" . $this->codificar($senha) . "'

                                         )");

		//Subindo o CR

		if ($arquivo != "") {

			$a = 0;

			// Cadastrar arquivos

			foreach ($arquivo as $arquivos) {

				$diretorio = 'arquivos';

				$extensao = pathinfo($arquivos, PATHINFO_EXTENSION);

				$nomeArquivo = explode("." . $extensao, $arquivos);

				//$codArquivo[$a] = md5(date("d-m-Y H:i:s").$nomeArquivo[0]).".".$extensao;

				$codArquivo[$a] = $nomeArquivo[0] . "." . $extensao;

				move_uploaded_file($tempA[$a], $diretorio . "/" . $codArquivo[$a]);

				mysqli_query($this->conexao, "INSERT INTO cr_clube VALUES(null,0,'" . $idCR . "','" . $codArquivo[$a] . "');");

				$idArquivos = mysqli_insert_id($this->conexao);

				$idCodArquivos = $this->codificar($idCR);

				mysqli_query($this->conexao, "UPDATE cr_clube SET IdCodClube = '" . $idCodArquivos . "' WHERE IdArquivos = '" . $idArquivos . "';");

				$a++;

			}

		}

//Fim subindo o CR

		if (mysqli_affected_rows($this->conexao) > 0) {

			$id = mysqli_insert_id($this->conexao);

			$idCod = $this->codificar($id);

			mysqli_query($this->conexao, "UPDATE clube SET id_cod_clube = '" . $idCod . "' WHERE clube = '" . $id . "';");

			$_SESSION["Sucesso"] = time() + 5;

//Enviar email apos o cadastro do Clube

			require 'phpmailer/PHPMailerAutoload.php';

			require 'phpmailer/class.phpmailer.php';

			$mailer = new PHPMailer;

			$mailer->isSMTP();

			$mailer->SMTPOptions = array(

				'ssl' => array(

					'verify_peer' => false,

					'verify_peer_name' => false,

					'allow_self_signed' => true,

				),

			);

			$assunto = "Novo Clube: '.$nomeClube.' cadastrado na Confederação";

			$mailer->Host = 'mail.ctcb.org.br';

			$mailer->SMTPAuth = true;

			$mailer->IsSMTP();

			$mailer->isHTML(true);

			$mailer->Port = 587;

			$mailer->CharSet = 'UTF-8';

			$mailer->Username = 'naoexclua@ctcb.org.br';

			$mailer->Password = 'confeder@c@o';

			$address = 'atendimento@ctcb.org.br'; //$email;

			$mensagem = '<table width="100%" border="0">';

			$mensagem .= '<tr>';

			$mensagem .= '<td style="text-align: left"><img src="https://www.ctcb.org.br/images/logo.png" style="width: 250px" alt="Logomarca da CTCB em letras azuis e partes da arma em verde" title="" class="logo img-fluid"></td>';

			$mensagem .= '<td style="text-align: right"><small>https://www.ctcb.org.br<br>atendimento@ctcb.org.br</small></td>';

			$mensagem .= '</tr>';

			$mensagem .= '</table>';

			$mensagem .= '<br>';

			$mensagem .= '<p style="font-weight: bold">Novo Cadastro de Clube de Tiro</p>';

			$mensagem .= '<p>Um novo clube de Tiro se cadastrou no site da Confederação.<br>

                        O clube está cadastrado com o Status <strong>Inativo</strong>.<br>

                        Você precisará aprovar o cadastro pela administração passando de inativo para ativo.<br>

                        Lá você também poderá enviar a senha de acesso para o Clube<br><br>

                        <strong>Nome do Clube</strong>: ' . $nomeClube . '<br><br>

                        <strong>Sigla do Clube</strong>: ' . $sigla . '<br><br>';

			$mailer->AddAddress($address, "CTCB - Confederação de Tiro e Caça do Brasil");

			$mailer->From = 'atendimento@ctcb.org.br';

			$mailer->FromName = "CTCB - Confederação de Tiro e Caça do Brasil";

			$mailer->Subject = $assunto;

			$mailer->MsgHTML($mensagem);

			$mailer->Send();

//Fim do envio de email

// Envia e-mail para o Clube

// Fim do email do Clube

			return "<script>window.location.href='" . $this->caminhoAbsoluto() . "/cadastro_clube_sucesso/';</script>";

		} else {

			$_SESSION["Erro"] = time() + 5;

		}

	}

	/**

	 * Método gera a senha aleatória

	 * Encontra-se na página novo-atirador.php, detalhes-atirador.php

	 * @access public

	 * @param int $qtyCaraceters

	 * @return string $password

	 */

	function generatePassword() {

		$qtyCaraceters = 6;

		$smallLetters = str_shuffle('abcdefghijklmnopqrstuvwxyz');

		$capitalLetters = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');

		$numbers = (((date('Ymd') / 12) * 24) + mt_rand(800, 9999));

		$numbers .= 1234567890;

		$specialCharacters = str_shuffle('!@#$%');

		$characters = $capitalLetters . $smallLetters . $numbers . $specialCharacters;

		$password = substr(str_shuffle($characters), 0, $qtyCaraceters);

		return $password;

	}

} // Fim da Classe
