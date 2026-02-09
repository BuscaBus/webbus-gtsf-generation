<?php
   include("../connection.php");

    // Recebe as variaveis   
    $trip_id = $_POST['viagem'];
    $sequencia = $_POST['sequencia'];
    $ponto = $_POST['ponto'];
   
    // Altera no banco de dados
    $sql = "INSERT INTO stop_routes (
                trip_id, 
                stop_sequence,
                stop_code                
            ) 
            VALUES (
                '$trip_id', 
                '$sequencia', 
                '$ponto'                              
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
