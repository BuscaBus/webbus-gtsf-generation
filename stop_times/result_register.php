<?php
   include("../connection.php");

    // Recebe as variaveis
    $trip_id = $_POST['id'];
    $hr_inicio = $_POST['hora_inicio'];    
    $ponto = $_POST['ponto']; 
    $destino = $_POST['destino'];  
        
    // Altera no banco de dados
    $sql = "INSERT INTO stop_times (
                trip_id, 
                stop_code,
                arrival_time, 
                stop_headsign                                
            ) 
            VALUES (
                '$trip_id', 
                '$ponto',
                '$hr_inicio', 
                '$destino'                                
            )";
            
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
      //echo "Operadora editada com sucesso";        
    //}
    //else{
      // echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Redireciona para a página anterior passando o id
    header("Location: register.php?id=$trip_id");
    exit;

?>
