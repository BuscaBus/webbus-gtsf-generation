<?php
   include("../connection.php");

    // Recebe as variaveis
    $route_id = $_POST['id'];
    $servico = $_POST['servico'];
    $origem = $_POST['origem'];
    $destino = $_POST['destino'];
    $sentido = $_POST['sentido'];
    $hrpartida = $_POST['hora-partida'];
    $partida = $_POST['partida'];
    $tracado = $_POST['tracado'];
    
    // Altera no banco de dados
    $sql = "INSERT INTO trips (
                route_id, 
                service_id, 
                trip_short_name,
                trip_headsign,
                direction_id,
                departure_time,
                departure_location, 
                shape_id               
            ) 
            VALUES (
                '$route_id', 
                '$servico', 
                '$origem',
                '$destino',
                '$sentido',
                '$hrpartida',
                '$partida',  
                '$tracado'              
            )";
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
      //echo "Operadora editada com sucesso";        
    //}
    //else{
      // echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Redireciona para a página anterior passando o id
    header("Location: register.php?id=$route_id");
    exit;

?>
