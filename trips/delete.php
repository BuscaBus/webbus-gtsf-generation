<?php
    include("../connection.php");

    // Trazendo e declarando a variavel id
    $id = $_POST['id'];
    $id_route = $_POST['id-route'];
   
    // Exclusão pelo id no banco de dados
    $sql = "DELETE FROM trips WHERE trip_id = '$id'";

    $result = mysqli_query($conexao,$sql);

    //if(mysqli_query($conexao, $sql)){
             
    //}
    //else{
       //echo "Erro ao excluir".mysqli_connect_error($conexao);
    //}
    
    // Mantem na mesma página após a exclusão
    header ("location: register.php?id=$id_route");

    mysqli_close($conexao);
    
?>