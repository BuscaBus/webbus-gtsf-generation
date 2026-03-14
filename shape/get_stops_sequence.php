<?php
include("../connection.php");
header("Content-Type: application/json");

$shape_id = $_GET['shape_id'] ?? '';

if(!$shape_id){
    echo json_encode([]);
    exit;
}

$shape_id = mysqli_real_escape_string($conexao,$shape_id);

$sql = "SELECT 
            Id as id,
            seq,
            stop_id,
            codigo,
            ponto,
            intervalo
        FROM shape_stops
        WHERE shape_id = '$shape_id'
        ORDER BY seq ASC";

$result = mysqli_query($conexao,$sql);

$dados = [];

while($row = mysqli_fetch_assoc($result)){
    $dados[] = $row;
}

echo json_encode($dados);