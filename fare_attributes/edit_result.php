<?php
   include("../connection.php");

    // Recebe as variaveis
    $codigo = $_POST['codigo'];
    $tarifa = $_POST['tarifa'];
    $tipo = $_POST['tipo'];
    $data = $_POST['data'];

    // Altera no banco de dados
    $sql = "UPDATE fare_attributes SET price = '$tarifa', route_group = '$tipo', update_date = '$data' WHERE fare_id = '$codigo'";
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