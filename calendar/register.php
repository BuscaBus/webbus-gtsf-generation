<?php
    include("../connection.php");

    // Declaração das variaveis
    $service_id = $_POST['servico'];
    $start_date = $_POST['inic_vig'];
    $end_date   = $_POST['term_vig'];

    // Se não marcar, vira 0
    $monday    = isset($_POST['monday']) ? 1 : 0;
    $tuesday   = isset($_POST['tuesday']) ? 1 : 0;
    $wednesday = isset($_POST['wednesday']) ? 1 : 0;
    $thursday  = isset($_POST['thursday']) ? 1 : 0;
    $friday    = isset($_POST['friday']) ? 1 : 0;
    $saturday  = isset($_POST['saturday']) ? 1 : 0;
    $sunday    = isset($_POST['sunday']) ? 1 : 0;

    // Consulta no banco de dados
    $sql = "INSERT INTO calendar 
        (service_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, start_date, end_date)
        VALUES 
        ('$service_id', $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, '$start_date', '$end_date')";
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

