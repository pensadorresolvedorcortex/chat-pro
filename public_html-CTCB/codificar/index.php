<?php
$conexao = mysqli_connect('localhost','gestaope_ctb_adm','0Z#]2!=MdVc&','gestaope_ctcb');
if($_POST){
  function codificar($key){
      $salt = "$" . md5(strrev($key)) . "%";
      $codifica = crypt($key, $salt);
      $codificar = hash('sha512', $codifica);
      return $codificar;
  }
   $login = $_POST["Login"];
   $senha = codificar($_POST["Senha"]);
   $sql = mysqli_query($conexao, "select * from atirador where email = '".$login."' and cbte_senha = '".$senha."';");
   if(mysqli_num_rows($sql) > 0){
     $mensagem = "OK.. Acessamos";
   }else{
     $mensagem = "Login ou senha inválidos";
   }    
 }
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
  <div style="margin: 10px auto">
    <?php echo $mensagem; ?>
    <form class="" action="#" method="post">
      <label>Digite seu login:</label><br>
      <input type="text" name="Login"><br>
      <label>Digite sua senha:</label><br>
      <input type="password" name="Senha"><br>
      <input type="submit" value="Acessar">
    </form>
  </div>
  </body>
</html>
