<?php
    include("../connection.php");

    // Declaração das variaveis
    $service_id = $_POST['servico'];
    $date = $_POST['data'];
    $status   = $_POST['status'];

    // Consulta no banco de dados
    $sql = "INSERT INTO calendar_dates (service_id, date, exception_type)
        VALUES ('$service_id', '$date', '$status')";
    $query = mysqli_query($conexao, $sql);

    //if(mysqli_query($conexao, $sql)){
      //echo "Operadora editada com sucesso";        
    //}
    //else{
      // echo "Erro ao editar".mysqli_connect_error($conexao);
    //}

    // Mantem na mesma página após o cadastro
    header ('location: register_date.php'); 
    
   mysqli_close($conexao);
    
?>