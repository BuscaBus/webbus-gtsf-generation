<?php
   include("../connection.php");

    // Recebe as variaveis
    $operadora = $_POST['operadora'];
    $codigo = $_POST['codigo'];
    $linha = $_POST['linha'];
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $corLinha = $_POST['cor-linha'];
    $corTexto = $_POST['cor-texto'];
    $ordem = $_POST['ordem'];
    $grupo = $_POST['grupo'];
    
    // Altera no banco de dados
    $sql = "INSERT INTO routes (
                agency_id, 
                route_short_name, 
                route_long_name,
                route_desc,
                route_type,
                route_color,
                route_text_color,
                route_sort_order,
                network_id                
            ) 
            VALUES (
                '$operadora', 
                '$codigo', 
                '$linha',
                '$descricao',
                '$tipo',
                '$corLinha', 
                '$corTexto',  
                '$ordem',
                '$grupo'             
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