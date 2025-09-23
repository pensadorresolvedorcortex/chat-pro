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
$tabela = "atirador";
$idTabela = "atirador";
$idBusca = $_SESSION["IdUsuario"];
$visualizar = $metodos->visualizar($tabela, $idTabela, $idBusca);
?>
<html>
<head>
		<title>Confedera&ccedil;&atilde;o de Tiro e Ca&ccedil;a do Brasil</title>
    <meta name="description" content="Entidade que regula o Tiro e a caça em todo o território nacional">
    <meta name="keywords" content="tiro, esportivo, alvo, municao, calibre, revolver, esporte, precisao, mira, instrutor, atleta, competicao, medalha, fuzil, campeonato, oficial, federacao, brasil, distancia, bala, prato, fossa, double, skeet, deitado, carabina, prático, caça, javali, lune">
    <meta name="robots" content="index,follow">
    <meta name="revisit-after" content="1 days">
    <meta name="contact" content="atendimento@ctcb.org.br">
    <meta name="distribution" content="global">
    <meta name="language" content="pt-br">
    <meta name="no-email-collection" content="os e-mails deste site são de uso exclusivo da empresa.  violações poderão incorrer em sanções da lei">
    <meta name="rating" content="general">
    <meta name="reply-to" content="atendimento@ctcb.org.br">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <base target="_self">
		<link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon">
    <style type="text/css">
body {
    -webkit-print-color-adjust: exact;
}
    @import url(https://fonts.googleapis.com/css?family=Alegreya+Sans:500,800);

    table.bordasimples {border-collapse: collapse;}
    table.bordasimples tr td {border:1px solid #999999;}

    /* strengthmeter */
    .password
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; font-size: 14px; color: #000000; border-style: solid; border-width: 1; border-radius: 5px;	-moz-border-radius: 5px;	-webkit-border-radius: 5px; border-color: ; padding: 2px 0px 2px 0px; height: 27px; }
    .pstrength-minchar
    { font-size : 10px }
    /* ------------- */

    .campo
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; font-size: 14px; color: #000000; border-style: solid; border-width: 1; border-radius: 3px;	-moz-border-radius: 3px;	-webkit-border-radius: 3px; border-color: ; padding: 2px 0px 2px 0px; height: 27px; }
    .campo_memo
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; font-size: 14px; color: #000000; border-style: solid; border-width: 1;	border-radius: 5px; border-color: ; padding: 2px 0px 2px 0px; height: 200px; }
    .campo_numero
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; font-size: 14px;	color: #000000;	border-style: solid; border-color: ; border-width: 1;	border-radius: 3px;	text-align: right; }
    .campo_radio
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; font-size: 14px; color: #000000; border-style: solid; border-width: 0; }
    .figura
    { border-top-color: ; border-right-color: ; border-bottom-color: ; border-left-color: ; }
    .borda
    { border:  solid 1px; text-align: center; }

    .campo_tag
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; width:25%; vertical-align: middle; text-decoration: none; line-height: 35px; font-size: 14px;	color: #000000; height: 35px; text-align:right; }
    .campo_erro
    {	width:5%; text-align: center; vertical-align: top; padding: 10px 0px 0px 0px; }
    .campo_conteudo
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; width:70%; text-align: justify; text-decoration: none; line-height: 35px; font-size: 14px;	color: #000000; }

    .caixa
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-align: justify; vertical-align:top; text-decoration: none; line-height: 150%; letter-spacing: 1px; font-size: 18px;	color: #222222; background-color: #DDDDDD; border-top: 3px  solid; border-bottom: 3px  solid; padding: 10px; }
    .caixa_titulo
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 800; text-align: center; vertical-align:top; text-decoration: none; line-height: 150%; letter-spacing: 1px; font-size: 18px;	color: #FFFFFF; background-color: ; border-top: 3px  solid; padding: 3px; }
    .caixa_aviso
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-align: justify; vertical-align:top; text-decoration: none; line-height: 150%; letter-spacing: 1px; font-size: 18px;	color: #FFFF00; background-color: #FF0000; border-top: 3px #AF573E solid; border-bottom: 3px #AF573E solid; padding: 10px; }

    ul
    { list-style: square; color:#000; }
    li
    { margin-bottom: 10px; }

    p
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; line-height: 150%; }

    p.calendario2
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; font-size: 14px;	color: #FFFFFF; border: 0px solid; }
    p.calendario2p
    {	font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; font-size: 14px;	color: #000000; border: 0px solid; }

    a
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; text-decoration: none; line-height: 150%; letter-spacing: 1px;  }

    a.galeria
    {}

    ul.menu { margin: 0; padding: 0; }
    ul.menu li { list-style: none; display: inline; }
    ul.menu li a { font-size: 14px; color: #FFFFFF; float: left; width: 12.5%; text-align:center; line-height:60px; vertical-align: middle; background: #DA251C; text-decoration: none; }
    ul.menu li a:hover { font-size: 14px; color: #FFFFFF; float: left; width: 12.5%; text-align:center; line-height:60px; vertical-align: middle; background: #666666; text-decoration: none; }

    a.botao_menu1
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 16px; color: #FFFFFF; height:15px; border: 0px; padding: 0px 12px 0px 12px; }
    a:hover.botao_menu1
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 16px;	color: #FFFF00; height:15px; border: 0px; padding: 0px 12px 0px 12px; }

    a.botao_menu2
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 18px; color: #3E4095; height:18px; border: 0px; padding: 0px 12px 0px 12px; }
    a:hover.botao_menu2
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 18px;	color: #FFFF00; height:18px; border: 0px; padding: 0px 12px 0px 12px; }

    a.botao_menu3
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 14px; color: #FFFF00; height:14px; border: 0px; padding: 0px 5px 5px 5px; }
    a:hover.botao_menu3
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 14px;	color: #3E4095; height:14px; border: 0px; padding: 0px 5px 5px 5px; }

    a.botao
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 14px; color: #FFFFFF; height:15px; border: 1px solid ;	border-radius: 5px; background-color: ; padding: 5px 8px 5px 8px; }
    a:hover.botao
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 14px;	color: #FFFF00; height:15px; border: 1px solid ;	border-radius: 5px; background-color: ; padding: 5px 8px 5px 8px; }

    a.botao2
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 14px; color: #FFFFFF; height:15px; border: 1px solid #FFFFFF; background-color: ; padding: 3px; }
    a:hover.botao2
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 14px;	color: #FFFF00; height:15px; border: 1px solid #FFFFFF; background-color: ; padding: 3px; }

    a.botao_login
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 11px; color: #FFFFFF; height:10px; border: 1px solid #777777;	border-radius: 5px; background-color: ; padding: 4px 6px 4px 6px; }
    a:hover.botao_login
    { font-family: 'Alegreya Sans', sans-serif; font-weight: 800;	font-size: 11px;	color: #FFFF00; height:10px; border: 1px solid #777777;	border-radius: 5px; background-color: ; padding: 4px 6px 4px 6px; }


    a.texto9p  { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 9px; color: #000000; } a.link9p { font-size: 9px; color: #000000; } a:hover.link11p { font-size: 9px; color: #000000; text-decoration: underline; }
    a.texto9b  { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 9px; color: #FFFFFF; } a.link9b { font-size: 9px; color: #FFFFFF; } a:hover.link11b { font-size: 9px; color: #FFFFFF; text-decoration: underline; }

    a.texto11c { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 11px; color: #CCCCCC; }
    a.texto11p { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 11px; color: #000000; } a.link11p { font-size: 11px; color: #000000; } a:hover.link11p { font-size: 11px; color: #000000; text-decoration: underline; }
    a.texto11b { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 11px; color: #FFFFFF; } a.link11b { font-size: 11px; color: #FFFFFF; } a:hover.link11b { font-size: 11px; color: #FFFFFF; text-decoration: underline; }
    a.texto11x { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 11px; color: ;  } a.link11x { font-size: 11px; color: ;  } a:hover.link11x { font-size: 11px; color: ;  text-decoration: underline; }
    a.texto11y { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 11px; color: ; } a.link11y { font-size: 11px; color: ; } a:hover.link11y { font-size: 11px; color: ; text-decoration: underline; }

    a.texto12p { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 12px; color: #000000; } a.link12p { font-size: 12px; color: #000000; } a:hover.link12p { font-size: 12px; color: #000000; text-decoration: underline; }
    a.texto12b { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 12px; color: #FFFFFF; } a.link12b { font-size: 12px; color: #FFFFFF; } a:hover.link12b { font-size: 12px; color: #FFFFFF; text-decoration: underline; }

    a.texto14p { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 14px; color: #000000; } a.link14p { font-size: 14px; color: #000000; } a:hover.link14p { font-size: 14px; color: #000000; text-decoration: underline; }
    a.texto14b { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 14px; color: #FFFFFF; } a.link14b { font-size: 14px; color: #FFFFFF; } a:hover.link14b { font-size: 14px; color: #FFFFFF; text-decoration: underline; }
    a.texto14l { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 14px; color: #FF0000; } a.link14l { font-size: 14px; color: #FF0000; } a:hover.link14b { font-size: 14px; color: #FF0000; text-decoration: underline; }
    a.texto14x { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 14px; color: ;  } a.link14x { font-size: 14px; color: ;  } a:hover.link14x { font-size: 14px; color: ;  text-decoration: underline; }
    a.texto14y { font-family: 'Alegreya Sans', sans-serif; font-weight: 500; font-size: 14px; color: ; } a.link14y { font-size: 14px; color: ; } a:hover.link14y { font-size: 14px; color: ; text-decoration: underline; }

    a.texto18p { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 18px; color: #000000; } a.link18p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 18px; color: #000000; } a:hover.link18p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 18px; color: #000000; text-decoration: underline; }
    a.texto18b { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 18px; color: #FFFFFF; } a.link18b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 18px; color: #FFFFFF; } a:hover.link18b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 18px; color: #FFFFFF; text-decoration: underline; }
    a.texto18x { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 18px; color: ;  } a.link18x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 18px; color: ; } a:hover.link18x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 18px; color: ; text-decoration: underline; }
    a.texto18y { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 18px; color: ; } a.link18y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 18px; color: ; } a:hover.link18y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 18px; color: ; text-decoration: underline; }

    a.texto24p { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 24px; color: #000000; } a.link24p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 24px; color: #000000; } a:hover.link24p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 24px; color: #000000; text-decoration: underline; }
    a.texto24b { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 24px; color: #FFFFFF; } a.link24b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 24px; color: #FFFFFF; } a:hover.link24b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 24px; color: #FFFFFF; text-decoration: underline; }
    a.texto24x { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 24px; color: ;  } a.link24x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 24px; color: ; } a:hover.link24x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 24px; color: ; text-decoration: underline; }
    a.texto24y { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 24px; color: ; } a.link24y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 24px; color: ; } a:hover.link24y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 24px; color: ; text-decoration: underline; }

    a.texto28p { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 28px; color: #000000; } a.link28p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 28px; color: #000000; } a:hover.link28p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 28px; color: #000000; text-decoration: underline; }
    a.texto28b { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 28px; color: #FFFFFF; } a.link28b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 28px; color: #FFFFFF; } a:hover.link28b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 28px; color: #FFFFFF; text-decoration: underline; }
    a.texto28x { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 28px; color: ;  } a.link28x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 28px; color: ; } a:hover.link28x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 28px; color: ; text-decoration: underline; }
    a.texto28y { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 28px; color: ; } a.link28y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 28px; color: ; } a:hover.link28y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 28px; color: ; text-decoration: underline; }

    a.texto30c { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 30px; color: #777777; }
    a.texto30p { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 30px; color: #000000; } a.link30p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 30px; color: #000000; } a:hover.link30p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 30px; color: #000000; text-decoration: underline; }
    a.texto30b { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 30px; color: #FFFFFF; } a.link30b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 30px; color: #FFFFFF; } a:hover.link30b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 30px; color: #FFFFFF; text-decoration: underline; }
    a.texto30x { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 30px; color: ;  } a.link30x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 30px; color: ; } a:hover.link30x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 30px; color: ; text-decoration: underline; }
    a.texto30y { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 30px; color: ; } a.link30y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 30px; color: ; } a:hover.link30y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 30px; color: ; text-decoration: underline; }

    a.texto40c { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 40px; color: #777777; }
    a.texto40p { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 40px; color: #000000; } a.link40p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 40px; color: #000000; } a:hover.link40p { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 40px; color: #000000; text-decoration: underline; }
    a.texto40b { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 40px; color: #FFFFFF; } a.link40b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 40px; color: #FFFFFF; } a:hover.link40b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 40px; color: #FFFFFF; text-decoration: underline; }
    a.texto40x { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 40px; color: ;  } a.link40x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 40px; color: ; } a:hover.link40x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 40px; color: ; text-decoration: underline; }
    a.texto40y { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 40px; color: ; } a.link40y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 40px; color: ; } a:hover.link40y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 40px; color: ; text-decoration: underline; }

    a.texto50b { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 50px; color: #FFFFFF;  } a.link50b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 50px; color: #FFFFFF; } a:hover.link50b { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 50px; color: #FFFFFF; text-decoration: underline; }
    a.texto50x { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 50px; color: ;  } a.link50x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 50px; color: ; } a:hover.link50x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 50px; color: ; text-decoration: underline; }
    a.texto50y { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 50px; color: ; } a.link50x { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 50px; color: ; } a:hover.link50y { font-family: 'Alegreya Sans', sans-serif; font-weight: 700; font-size: 50px; color: ; text-decoration: underline; }
    a.texto50c { font-family: 'Alegreya Sans', sans-serif; font-weight: 800; font-size: 50px; color: #777777;  }
    </style>

  <script src="/include/funcoes.js"></script>

</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" border="0" cellpadding="0" cellspacing="0">

  <tr>
		  <td height="10"></td>
		</tr>
  <tr>
		  <td align="center" valign="middle">
      <table width="676" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="338" height="218" align="center" style="border: #999 solid 1px;">
            <table width="338" height="218" border="0" cellpadding="0" cellspacing="0" style="background-color:#FFFF99">
              <tr style="background-color:#FFFF99">
                <td height="9" align="center" valign="middle" bgcolor="#FFFF99"><img src="<?php echo $caminhoAbsoluto; ?>/images/logo.png" width="250" alt="logo" border="0"></td>
              </tr>
            </table>
          </td>
      		  <td width="338" height="218" align="center" valign="top" style="border: #999 solid 1px;">
            <table width="338" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td height="9" colspan="3"></td>
              </tr>
              <tr>
                <td align="center" valign="top" colspan="3">
                  <a class="texto11y">
                  Av. Beira Mar 200 sala 504/2<br>
                  Centro &bull; Rio de Janeiro &bull; RJ<br>
                  (21) 2292-0888<br>
                  https://www.ctcb.org.br &bull; atendimento@ctcb.org.br
                </a>
                </td>
              </tr>
              <tr>
                <td height="5" colspan="3"></td>
              </tr>
              <tr>
                <td></td>
                <td colspan="2"><a class="texto9p">Nome:</a><br><a class="texto14p"><?php echo utf8_decode($visualizar[1]->nome); ?></a></td>
              </tr>
              <tr>
                <td height="5" colspan="3"></td>
              </tr>
              <tr>
                <td width="10"></td>
                <td width="100"><a class="texto9p">Matr&iacute;cula: </a><br><a class="texto14p"><?php echo $visualizar[1]->codigo; ?></a></td>
                <td width="108"><a class="texto9p">CPF: </a><br><a class="texto14p"><?php echo $visualizar[1]->cpf; ?></a></td>
              </tr>
              <tr>
                <td height="5" colspan="3"></td>
              </tr>
              <tr>
                <td width="10"></td>
                <?php
                list($anoC,$mesC,$diaC) = explode("-",$visualizar[1]->data_cadastro);
                $dataCadastro = $diaC."/".$mesC."/".$anoC;
                ?>
                <td width="100"><a class="texto9p">Cadastro: </a><br><a class="texto14p"><?php echo $dataCadastro; ?></a></td>
                <?php $dataValidade = $metodos->validadeAtirador($_SESSION["IdUsuario"]); ?>
                <td width="108"><a class="texto9p">Validade: </a><br><a class="texto14p"><?php echo $dataValidade; ?></a></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
		</tr>
</table>
 <script>
setTimeout(function () {
   window.print(); window.close();
 }, 1000);
</script>
</body>
</html>
