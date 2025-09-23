<?php
if($_POST){
   // Limpando as variáveis
   $nome = filter_input(INPUT_POST,'nome',FILTER_SANITIZE_SPECIAL_CHARS);
   $idade = filter_input(INPUT_POST,'idade',FILTER_SANITIZE_NUMBER_INT);
   $email = filter_input(INPUT_POST,'email',FILTER_SANITIZE_EMAIL);
   // Validando os campos
   $idade = filter_input(INPUT_POST,'idade',FILTER_VALIDATE_INT);
   $email = filter_input(INPUT_POST,'email',FILTER_VALIDATE_EMAIL);
   $peso = filter_input(INPUT_POST,'peso',FILTER_VALIDATE_FLOAT);


   // Validações
   /*
   if($idade == false){
     echo "Favor colocar corretamente a idade";
   }else if($email == false){
    echo "Favor colocar um e-mail válido";
  }else if($url == false){
    echo "Favor a url correta";
  }else{
    echo "OK. Vamos enviar";
  }
  */
}
?>
<!DOCTYPE html>
<html lang="pt-br" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
    <form class="" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
      Idade: <input type="text" name="idade" value=""><br>
      Email: <input type="text" name="email" value=""><br>
      Peso: <input type="text" name="peso" value=""><br>
      IP: <input type="text" name="ip" value=""><br>
      Url: <input type="text" name="url" value=""><br>
      <input type="submit" name="" value="Enviar">
    </form>
  </body>
</html>
