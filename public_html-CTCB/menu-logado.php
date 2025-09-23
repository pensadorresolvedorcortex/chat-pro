<?php
error_reporting(0);
session_start();
$tabela = "atirador";
$idTabela = "atirador";
$idBusca = $_SESSION["IdUsuario"];
$visualizar = $metodos->visualizar($tabela, $idTabela, $idBusca);
list($nome,$sobrenome) = explode(" ",$visualizar[1]->nome);
//if($_SESSION["TipoAcesso"] == "Atirador")
//{
  $pagamento = $metodos->verificarPagamento($_SESSION["IdUsuario"]);
//}
echo $pagamento[1];
?>
<div class="bg-success">
   <div class="container text-white">
    <?php if($_SESSION["TipoAcesso"] == "Atirador"){ ?>
     <div class="text-right">
     Bem-vindo de volta, <strong><?php echo $metodos->palavraMinuscula($nome); ?></strong>
   </div>
    <?php } ?>
    <?php if($_SESSION["TipoAcesso"] == "Despachante"){ ?>
     <div class="text-right">
     <strong><?php echo $metodos->palavraMinuscula($visualizar[1]->nome); ?></strong> <br> <a href="<?php echo $caminhoAbsoluto; ?>/index-despachantes/" style="color: #FFF">Trocar de atirador</a>
   </div>
    <?php } ?>
    <?php if($_SESSION["TipoAcesso"] == "CTCB"){ ?>
     <div class="text-right">
     <strong><?php echo $metodos->palavraMinuscula($visualizar[1]->nome); ?></strong> <br> <a href="<?php echo $caminhoAbsoluto; ?>/index-ctcb/" style="color: #FFF">Trocar de atirador</a>
   </div>
    <?php } ?>
     <br>
     <div class="row">
       <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/alterar-cadastro/'" style="cursor: pointer" title="Altere seu cadastro"><i class="fas fa-user fa-lg"></i><p style="font-size: 12px">Cadastro</p></div>
       <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/alterar-senha/'" style="cursor: pointer" title="Altere sua senha"><i class="fas fa-key fa-lg"></i><p style="font-size: 12px">Senha</p></div>
      <!-- <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/inscricao/'" style="cursor: pointer" title="Faça novas inscrições"><i class="fas fa-pen-nib fa-lg"></i><p style="font-size: 12px">Inscrição</p></div> -->

<?php if($pagamento[2] == 0){ ?>
       <div class="col-md-3 text-center" style="color: #C0C0C0" title="Imprima sua carteira"><i class="fas fa-address-card fa-lg"></i><p style="font-size: 12px">Carteira</p></div>
<?php }else{ ?>
  <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/carteira/'" style="cursor: pointer" title="Imprima sua carteira"><i class="fas fa-address-card fa-lg"></i><p style="font-size: 12px">Carteira</p></div>
<?php } ?>

 <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/pagamentos/'" style="cursor: pointer" title="Veja os valores em aberto"><i class="fas fa-dollar-sign fa-lg"></i><p style="font-size: 12px">Pagamentos</p></div>
</div>

<div class="row" style="margin-top: 10px">
<!--<div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/valores-pagos/'" style="cursor: pointer" title="Veja os valores pagos"><i class="fas fa-hand-holding-usd fa-2x"></i><p style="font-size: 12px">Valor pago</p></div>-->

<div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/armas/'" style="cursor: pointer"  title="Cadastre sua arma"><i class="fas fa-crosshairs fa-lg"></i><p style="font-size: 12px">Armas</p></div>

<?php if($pagamento[2] == 0){ ?>
<div class="col-md-3 text-center" style="color: #C0C0C0" title="Anuncie sua arma"><i class="fas fa-file-invoice-dollar fa-lg"></i><p style="font-size: 12px">Anúncio</p></div>
<?php }else{ ?>
<div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/anuncios/'" style="cursor: pointer" title="Anuncie sua arma"><i class="fas fa-file-invoice-dollar fa-lg"></i><p style="font-size: 12px">Anúncio</p></div>
<?php } ?>

<div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/sair/'" style="cursor: pointer"><i class="fas fa-power-off fa-lg"></i><p style="font-size: 12px">Sair</p></div>

</div>

 <div class="text-center" style="margin-top: 5px">
 <small>Em 12 meses - <?php $contarEventos = $metodos->contarEventosAtirador($_SESSION["IdUsuario"]); echo $contarEventos[1]; ?></small>
 <hr>
 <h5>Declarações e Impressos</h5>
 <div class="row" style="margin-top: 20px;">

<?php if($pagamento[2] == 0){ ?>
   <div class="col-md-3 text-center" style="color: #C0C0C0" title="Imprima sua declaração a Entidade"><i class="fas fa-stamp fa-lg"></i><p style="font-size: 12px">Filiação Entidade</p></div>
<?php }else{ ?>
   <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/filiacao-entidade/'" style="cursor: pointer" title="Imprima sua declaração a Entidade"><i class="fas fa-stamp fa-lg"></i><p style="font-size: 12px">Filiação Entidade</p></div>
<?php } ?>

<?php if($pagamento[2] == 0){ ?>
  <div class="col-md-3 text-center" style="color: #C0C0C0" title="Imprima sua declaração de habitualidade"><i class="fas fa-user-clock fa-lg"></i><p style="font-size: 12px">Habitua-<br>lidade</p></div>
<?php }else{ ?>
   <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/habitualidade/'" style="cursor: pointer" title="Imprima sua declaração de habitualidade"><i class="fas fa-user-clock fa-lg"></i><p style="font-size: 12px">Habitua-<br>lidade</p></div>
<?php } ?>

<?php if($pagamento[2] >= 1 and date("Y-m-d") <= $visualizar[1]->data_declaracao){ ?>
   <div class="col-md-3 text-center" title="Imprima seu hanking"  onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/hanking/'" style="cursor: pointer"><i class="fas fa-medal fa-lg"></i><small>Ranking</small></div>
<?php }else{ ?>
  <div class="col-md-3 text-center" style="color: #C0C0C0"><i class="fas fa-medal fa-lg"></i><small>Ranking</small></div>
<?php } ?>

<?php if($pagamento[2] == 0){ ?>
   <div class="col-md-3 text-center" style="color: #C0C0C0" title="Imprima sua declaração de modalidades e provas"><i class="fas fa-clock fa-lg"></i></i><p style="font-size: 12px">Modalidade e prova</p></div>
<?php }else{ ?>
   <div class="col-md-3 text-center"onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/modalidades-provas/'" style="cursor: pointer" title="Imprima sua declaração de modalidades e provas"><i class="fas fa-clock fa-lg"></i></i><p style="font-size: 12px">Modalidade e prova</p></div>
<?php } ?>

</div>

<div class="row" style="margin-top: 10px">

<?php if($pagamento[2] == 0){ ?>
 <div class="col-md-3 text-center" style="color: #C0C0C0" title="Imprima sua declaração para solicitar sua guia de tráfego"><i class="fas fa-walking fa-2x"></i><p style="font-size: 12px">Guia de Tráfego</p></div>
<?php }else{ ?>
 <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/guia-trafego/'" style="cursor: pointer" title="Imprima sua declaração para solicitar sua guia de tráfego"><i class="fas fa-walking fa-2x"></i><p style="font-size: 12px">Guia de Tráfego</p></div>
<?php } ?>

<?php if($pagamento[2] == 0){ ?>
 <div class="col-md-3 text-center" style="color: #C0C0C0" title="Imprima sua declaração de atividades"><i class="fas fa-chart-line fa-2x"></i><p style="font-size: 12px">Atividade</p></div>
<?php }else{ ?>
 <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/atividades/'" style="cursor: pointer" title="Imprima sua declaração de atividades"><i class="fas fa-chart-line fa-2x"></i><p style="font-size: 12px">Atividade</p></div>
<?php } ?>

<?php if($pagamento[2] == 0){ ?>
 <div class="col-md-3 text-center" style="color: #C0C0C0" title="Imprima seus certificados"><i class="fas fa-award fa-2x"></i><p style="font-size: 12px">Certificado</p></div>
<?php }else{ ?>
 <div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/certificados/'" style="cursor: pointer" title="Imprima seus certificados"><i class="fas fa-award fa-2x"></i><p style="font-size: 12px">Certificado</p></div>
<?php } ?>

<?php if($pagamento[2] == 0){ ?>
 <div class="col-md-3 text-center" style="color: #c0c0c0" title="Imprima seu currículo esportivo"><i class="fas fa-file-signature fa-2x"></i><p style="font-size: 12px">Currículo esportivo</p></div>
<?php }else{ ?>
<div class="col-md-3 text-center" onclick="window.location.href='<?php echo $caminhoAbsoluto; ?>/curriculo-esportivo/'" style="cursor: pointer" title="Imprima seu currículo esportivo"><i class="fas fa-file-signature fa-2x"></i><p style="font-size: 12px">Currículo esportivo</p></div>
<?php } ?>

</div>
</div>
   </div>
 </div>
<!-- Versão 11/03/2019 -->
