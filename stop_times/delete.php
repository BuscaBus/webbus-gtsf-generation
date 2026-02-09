<?php
    include("../connection.php");

    // Trazendo e declarando a variavel id
    $id = $_POST['id'];
    $trip_id = $_POST['trip-id'];
   
    // Exclusão pelo id no banco de dados
    $sql = "DELETE FROM stop_times WHERE time_id = '$id'";

    $result = mysqli_query($conexao,$sql);

    //if(mysqli_query($conexao, $sql)){
             
    //}
    //else{
       //echo "Erro ao excluir".mysqli_connect_error($conexao);
    //}
    
    // Mantem na mesma página após a exclusão
    header ("Location: register.php?id=$trip_id");

    mysqli_close($conexao);
    
?>