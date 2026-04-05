<?php
   include("../connection.php");

    // Recebe as variaveis
    $codigo_atual = $_POST['codigo-atual'];
    $codigo_novo = $_POST['codigo-novo'];
    $tarifa = $_POST['tarifa'];
    $meio_pag = $_POST['meio-pag'];
    $data = $_POST['data'];

    // Altera no banco de dados
    $sql = "UPDATE fare_attributes SET fare_id = '$codigo_novo', price = '$tarifa', payment_method = '$meio_pag', update_date = '$data' WHERE fare_id = '$codigo_atual'";
    $query = mysqli_query($conexao, $sql);

   //if(mysqli_query($conexao, $sql)){
       //echo "Operadora editada com sucesso";        
   //}
    //else{
       //echo "Erro ao editar".mysqli_connect_error($conexao);
  //}

    // Mantem na mesma página após a exclusão
    header ('location: list.php');
    
    mysqli_close($conexao);
    
?>