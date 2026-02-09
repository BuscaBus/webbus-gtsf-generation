<?php
   include("../connection.php");

    // Recebe as variaveis
    $id_original = $_POST['id_original']; // antigo service_id 
    $servico = $_POST['servico']; // novo service_id
    $inicio = $_POST['inic_vig'];
    $termino = $_POST['term_vig'];

    // Altera no banco de dados
    $sql = "UPDATE calendar SET start_date = '$inicio', end_date = '$termino' WHERE service_id = '$id_original'";
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