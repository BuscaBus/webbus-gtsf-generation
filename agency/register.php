<?php
    include("../connection.php");

    // Declaração das variaveis
    $nome = $_POST['nome'];
    $url = $_POST['url'];
    $fuso = $_POST['fuso'];
    $lang = $_POST['lang'];
    $tel = $_POST['tel'];
    $cidade = $_POST['cidade'];
    $sitecred = $_POST['site-cred'];
    $email = $_POST['email'];
    

    // Consulta no banco de dados
    $sql = "INSERT INTO agency (agency_name, agency_url, agency_timezone, agency_lang, agency_phone, agency_city, agency_fare_url, agency_email) VALUES ('$nome', '$url', '$fuso', '$lang', '$tel', '$cidade', '$sitecred', '$email')";
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
      //echo "Operadora editada com sucesso";        
    //}
    //else{
      // echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Mantem na mesma página após o cadastro
    header ('location: list.php'); 
    
   mysqli_close($conexao);
    
?>
    

    