<?php
    include("../connection.php");

    // Trazendo e declarando a variavel id
    $id = $_POST['id'];
    $data = $_POST['date'];
   
    // Exclusão pelo id no banco de dados
    $sql = "DELETE FROM calendar_dates WHERE service_id = '$id' AND date = '$data'";

    $result = mysqli_query($conexao,$sql);

    //if(mysqli_query($conexao, $sql)){
             
    //}
    //else{
       //echo "Erro ao excluir".mysqli_connect_error($conexao);
    //}
    
    // Mantem na mesma página após a exclusão
    header ('location: register_date.php');

    mysqli_close($conexao);
    
?>