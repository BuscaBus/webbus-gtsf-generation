<?php
    include("../connection.php");

    // Trazendo e declarando a variavel id
    $codigo = $_POST['codigo'];
   
    // Exclusão pelo id no banco de dados
    $sql = "DELETE FROM fare_attributes WHERE fare_id = '$codigo'";
    $result = mysqli_query($conexao,$sql);

    //if(mysqli_query($conexao, $sql)){
             
    //}
    //else{
       //echo "Erro ao excluir".mysqli_connect_error($conexao);
    //}
    
    // Mantem na mesma página após a exclusão
    header ('location: list.php');

    mysqli_close($conexao);
    
?>