<?php
   require_once __DIR__ . "/../connection.php";

    // Recebe as variaveis
    $id_route = $_POST['id_route'];  
    $id_trip = $_POST['id_trip'];   
    $origem = $_POST['origem'];
    $destino = $_POST['destino']; 
    $local_partida = $_POST['partida'];    

    // Altera no banco de dados
    $sql = "UPDATE trips SET 
               trip_short_name = '$origem',                              
               trip_headsign = '$destino',
               departure_location = '$local_partida'               
            WHERE 
               trip_id = '$id_trip'";
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
       //echo "Operadora editada com sucesso";        
    //}
    //else{
       //echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Redireciona para a página trips
    header("Location: register.php?id=$id_route");
    exit;

    
   mysqli_close($conexao);
    
?>