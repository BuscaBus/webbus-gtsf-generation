<?php
include("../connection.php");

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
    echo json_encode(["status"=>"erro"]);
    exit;
}

foreach($data as $item){

    $seq = $item['seq'];
    $codigo = $item['codigo'];
    $ponto = $item['ponto'];
    $intervalo = $item['intervalo'];
    $shape_id = $item['shape_id'];
    $stop_id = $item['stop_id'];

    $sql = "INSERT INTO shape_stops 
            (shape_id, stop_id, seq, codigo, ponto, intervalo)
            VALUES 
            ('$shape_id','$stop_id','$seq','$codigo','$ponto','$intervalo')";

    mysqli_query($conexao,$sql);
}

echo json_encode(["status"=>"ok"]);