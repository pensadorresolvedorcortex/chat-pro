<?php
error_reporting(0);
$servidor = 'ctcb_confedera.mysql.dbaas.com.br';
$usuario = 'ctcb_confedera';
$senha = 'b@nc0Confeder@';
$banco = 'ctcb_confedera';
$conexao = mysqli_connect($servidor,$usuario,$senha,$banco);
mysqli_set_charset($conexao, "utf8");
require 'phpmailer/PHPMailerAutoload.php';
require 'phpmailer/class.phpmailer.php';
$mailer = new PHPMailer;
$quant = 1; // quantidade de e-mails para disparo
$sec = 3; // segundos
$sqlContar = mysqli_query($conexao,"SELECT * FROM enviar_cobranca;");
$contar = mysqli_num_rows($sqlContar);
if($contar == 0)
{
    $sql = mysqli_query($conexao,"SELECT * FROM atirador;");
    while($ctcb = mysqli_fetch_object($sql))
    {
        $dataVencimento = $ctcb->data_vencimento;
        list($ano,$mes,$dia) = explode('-',$dataVencimento);
        $ano = date("Y");
        $dataLembrete30 = date('Y-m-d',mktime(0,0,0,$mes,$dia - 30,$ano)); // 30 dias antes do vencimento
        $dataLembrete10 = date('Y-m-d',mktime(0,0,0,$mes,$dia - 10,$ano)); // 10 dias antes do vencimento
        $dataLembrete05 = date('Y-m-d',mktime(0,0,0,$mes,$dia - 5,$ano)); // 05 dias antes do vencimento
        $diaAtual = date('Y-m-d');
        list($anoV,$mesV,$diaV) = explode('-',$ctcb->data_vencimento);
        $dataVencimento = $diaV.'/'.$mesV.'/'.$anoV;
        if($dataLembrete30 == $diaAtual || $dataLembrete10 == $diaAtual || $dataLembrete05 == $diaAtual)
        {
            mysqli_query($conexao, "INSERT INTO enviar_cobranca VALUES(null,'".$ctcb->atirador."','0');");
        }        
    }
}
else
{
   mysqli_query($conexao, "TRUNCATE TABLE enviar_cobranca;");
}
$sqlAtualizar = mysqli_query($conexao, "SELECT * FROM enviar_cobranca WHERE StatusEnvio = 0");
$ctcbAtualizar = mysqli_num_rows($sqlAtualizar);
if($ctcbAtualizar > 0)
{
    $atualizar = 1;  
}
else
{
    $atualizar = 0;    
}
?>
<html>
<head>
<?php
$sqlDisparo = mysqli_query($conexao, "SELECT * FROM enviar_cobranca WHERE StatusEnvio = 0 LIMIT ".$quant.";");
$ctcbDisparo = mysqli_fetch_object($sqlDisparo);
if($atualizar == 1)
{
?>
<meta http-equiv="refresh" url="index.php" content="<?=$sec;?>">
<?php 
} 
?>
<body></body>
</head>
</html>
<?php
$sqlDisparar = mysqli_query($conexao,"SELECT * FROM atirador WHERE atirador = '".$ctcbDisparo->IdAtirador."';");
$ctcbDisparar = mysqli_fetch_object($sqlDisparar);
$mailer->isSMTP();
$mailer->SMTPOptions = array(
                           'ssl' => array(
                           'verify_peer' => false,
                           'verify_peer_name' => false,
                           'allow_self_signed' => true
                           )
                     );
 $dataVencimento = $ctcbDisparar->data_vencimento;
 list($ano,$mes,$dia) = explode('-',$dataVencimento);
 $dataVencimento = $dia.'/'.$mes.'/'.$ano;
 $assunto = 'Fatura em aberto';
 $mailer->Host = 'mail.ctcb.org.br';
 $mailer->SMTPAuth = true;
 $mailer->IsSMTP();
 $mailer->isHTML(true);
 $mailer->Port = 587;
 $mailer->CharSet = 'UTF-8';
 $mailer->Username = 'naoexclua@ctcb.org.br';
 $mailer->Password = 'confeder@c@o';
 $mensagem = 'Prezado,'.$ctcbDisparar->nome.'.<br><br> ';
 $mensagem .= 'Sua fatura está para vencer no dia <strong>'.$dataVencimento.'</strong>. Para continuar ter acesso exclusivo, efetue o pagamento abaixo:<br><br>';
 $mensagem .= 'DEPÓSITO EM CONTA-CORRENTE<br>';
 $mensagem .= '<strong>Valor:</strong> R$ 230,00<br>';
 $mensagem .= 'Banco Bradesco - Ag 0469 - C/C 136861-3<br><strong>CNPJ</strong> 12.499.864/0001-89<br><br>';
 $mensagem .= 'Favor enviar o comprovante para atendimento@ctcb.gov.br<br><br>';
 //$mensagem .= 'PAGAMENTO PELO PAGSEGURO<br>';
 //$mensagem .= 'Pagar pelo PagSeguro<br><br>';
 $mensagem .= 'Atenciosamente,<br>CTCB - Confereração de Tiro e Caça do Brasil.';
 $mailer->AddAddress($ctcbDisparar->email, 'CTCB - Confederação de Tiro e Caça do Brasil');
 $mailer->From = 'atendimento@ctcb.org.br';
 $mailer->FromName = 'CTCB - Confederação de Tiro e Caça do Brasil';
 $mailer->Subject = $assunto;
 $mailer->MsgHTML($mensagem);
 if($mailer->Send())
 {
    mysqli_query($conexao, "UPDATE enviar_cobranca SET StatusEnvio = 1 WHERE IdAtirador = '".$ctcbDisparar->atirador."';");
 } 
?>
