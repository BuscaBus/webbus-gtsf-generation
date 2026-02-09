<?php
   include("../connection.php");

   $data_edicao = date('Y-m-d'); // Editar a data no UPDATE

    // Recebe as variaveis
    $id = $_POST['id'];
    $codigo = $_POST['codigo'];
    $ponto = $_POST['ponto'];
    $box = $_POST['box'];
    
    // Altera no banco de dados
    $sql = "UPDATE stops SET stop_code = '$codigo', stop_name = '$ponto', platform_code = '$box', update_date = '$data_edicao' WHERE stop_id = '$id'";
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