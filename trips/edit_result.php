<?php
   include("../connection.php");

    // Recebe as variaveis
    $id = $_POST['id'];
    $id_route = $_POST['id-route'];
    $origem = $_POST['origem'];
    $destino = $_POST['destino'];
    $partida = $_POST['partida'];

    // Altera no banco de dados
    $sql = "UPDATE trips SET trip_short_name = '$origem', trip_headsign = '$destino',  departure_location = '$partida' WHERE trip_id = '$id'";
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
       //echo "Operadora editada com sucesso";        
    //}
    //else{
       //echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Mantem na mesma página após a exclusão
    header ("location: register.php?id=$id_route");
    
   mysqli_close($conexao);
    
?>