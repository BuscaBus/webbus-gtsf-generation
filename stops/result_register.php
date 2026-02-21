<?php
   include("../connection.php");   

    // Recebe as variaveis
    $codigo = $_POST['codigo'];
    $ponto = $_POST['ponto'];
    $descricao = $_POST['descricao'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $tp_local = $_POST['tp-local'];    
    $terminal = $_POST['terminal'];
    $box = $_POST['box']; 
    
    
    // Verifica se o código do ponto já existe
    $sql_check = "SELECT stop_id FROM stops WHERE stop_code = '$codigo'";
    $result = mysqli_query($conexao, $sql_check);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>
            alert('❌ Código já cadastrado!');
            history.back();
        </script>";
        exit;
    }

    
    // Altera no banco de dados
    $sql = "INSERT INTO stops (
                stop_code, 
                stop_name, 
                stop_desc,                
                stop_lat,
                stop_lon,
                location_type,
                parent_station,
                platform_code                
            ) 
            VALUES (
                '$codigo', 
                '$ponto', 
                '$descricao',                
                '$latitude',
                '$longitude',
                '$tp_local',
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

    header("Location: list.php");
    
    mysqli_close($conexao);
?>
