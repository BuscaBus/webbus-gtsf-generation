<?php
    include("../connection.php");

    // Declaração das variaveis
    $nome = $_POST['nome'];
    $cidade = $_POST['cidade'];
    $url = $_POST['url'];

    // Consulta no banco de dados
    $sql = "INSERT INTO agency (agency_name, agency_city, agency_url) VALUES ('$nome', '$cidade', '$url')";
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
    

    