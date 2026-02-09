<?php
    include("../connection.php");

    // Trazendo e declarando a variavel id
    $id = $_POST['id'];
   
    // Exclusão pelo id no banco de dados
    $sql = "DELETE FROM agency WHERE agency_id = '$id'";

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
    