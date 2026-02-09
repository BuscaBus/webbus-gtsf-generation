<?php
    include("../connection.php");

    // Declaração das variaveis
    $codigo = $_POST['codigo'];
    $tarifa = $_POST['tarifa'];
    $tipo = $_POST['tipo'];
    $data = $_POST['data'];

    // Consulta no banco de dados
    $sql = "INSERT INTO fare_attributes (fare_id, price, route_group, update_date) VALUES ('$codigo', '$tarifa', '$tipo', '$data')";
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
      //echo "Operadora editada com sucesso";        
    //}
    //else{
      // echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Mantem na mesma página após o cadastro
    header ('location: list.php'); 
    
   mysqli_close($conexao);
    
?>