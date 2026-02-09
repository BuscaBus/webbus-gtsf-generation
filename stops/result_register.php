<?php
   include("../connection.php");

    // Recebe as variaveis
    $codigo = $_POST['codigo'];
    $ponto = $_POST['ponto'];
    $cidade = $_POST['cidade'];
    $bairro = $_POST['bairro'];
    $local = $_POST['local'];
    $terminal = $_POST['terminal'];
    $box = $_POST['box'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    
    // Altera no banco de dados
    $sql = "INSERT INTO stops (
                stop_code, 
                stop_name, 
                stop_city,
                stop_district,
                stop_lat,
                stop_lon,
                location_type,
                parent_station,
                platform_code                
            ) 
            VALUES (
                '$codigo', 
                '$ponto', 
                '$cidade',
                '$bairro',
                '$latitude',
                '$longitude',
                '$local',
                '$terminal',
                '$box'                
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