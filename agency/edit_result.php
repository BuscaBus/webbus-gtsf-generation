<?php
   include("../connection.php");

    // Recebe as variaveis
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $cidade = $_POST['cidade'];
    $url = $_POST['url'];

    // Altera no banco de dados
    $sql = "UPDATE agency SET agency_name = '$nome', agency_city = '$cidade', agency_url = '$url' WHERE agency_id = '$id'";
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