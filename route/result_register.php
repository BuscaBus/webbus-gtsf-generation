<?php
   include("../connection.php");

    // Recebe as variaveis
    $operadora = $_POST['operadora'];
    $codigo = $_POST['codigo'];
    $linha = $_POST['linha'];
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $tarifa = $_POST['tarifa'];
    
    // Altera no banco de dados
    $sql = "INSERT INTO routes (
                agency_id, 
                route_short_name, 
                route_long_name,
                route_desc,
                route_group,
                price                
            ) 
            VALUES (
                '$operadora', 
                '$codigo', 
                '$linha',
                '$descricao',
                '$tipo',
                '$tarifa'                
            )";
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
      //echo "Operadora editada com sucesso";        
    //}
    //else{
      // echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Mantem na mesma página após a exclusão
    header ('location: list.php');
    
    mysqli_close($conexao);
    
?>