<?php
    include("../connection.php");

    // Declaração das variaveis
    $codigo = $_POST['codigo'];
    $tp_moeda = $_POST['tp-moeda'];
    $tarifa = $_POST['tarifa'];
    $meio_pag = $_POST['meio-pag'];
    $data = $_POST['data'];

    // Consulta no banco de dados
    $sql = "INSERT INTO fare_attributes (fare_id, price, currency_type, payment_method, update_date) VALUES ('$codigo', '$tarifa', '$tp_moeda', '$meio_pag', '$data')";
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