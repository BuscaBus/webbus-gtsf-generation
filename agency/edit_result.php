<?php
   include("../connection.php");

    // Recebe as variaveis
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $url = $_POST['url'];
    $fuso = $_POST['fuso'];
    $tel = $_POST['tel'];
    $sitecred = $_POST['cred'];
    $email = $_POST['email'];

    // Altera no banco de dados
    $sql = "UPDATE agency SET 
               agency_name = '$nome', 
               agency_url = '$url', 
               agency_timezone = '$fuso',  
               agency_phone = '$tel', 
               agency_fare_url = '$sitecred', 
               agency_email = '$email' 
            WHERE agency_id = '$id'";
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
       //echo "Operadora editada com sucesso";        
    //}
    //else{
       //echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Mantem na mesma página após a exclusão
    header ('location: list.php');
    
   mysqli_close($conexao);
    
?>