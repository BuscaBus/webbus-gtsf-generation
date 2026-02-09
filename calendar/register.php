<?php
    include("../connection.php");

    // Declaração das variaveis
    $servico = $_POST['servico'];
    $inicio = $_POST['inic_vig'];
    $termino = $_POST['term_vig'];

    // Consulta no banco de dados
    $sql = "INSERT INTO calendar (service_id,  start_date, end_date) VALUES ('$servico', '$inicio', '$termino')";
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