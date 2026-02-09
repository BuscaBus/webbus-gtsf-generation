<?php
   include("../connection.php");

    // Recebe as variaveis
    $id = $_POST['id'];
    $linha = $_POST['linha'];
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $status = $_POST['status'];

    // Altera no banco de dados
    $sql = "UPDATE routes SET 
               route_long_name = '$linha', 
               route_desc = '$descricao', 
               route_group = '$tipo',                
               route_status = '$status',
               update_date = NOW()
            WHERE 
               route_id = '$id'";
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