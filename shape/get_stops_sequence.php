<?php
require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

$shape_id = $_GET['shape_id'] ?? '';

if(!$shape_id){
    echo json_encode([]);
    exit;
}


$stmt = mysqli_prepare($conexao, "
    SELECT 
        id,
        seq,
        stop_id,
        codigo,
        ponto,
        intervalo
    FROM shape_stops
    WHERE shape_id = ?
    ORDER BY seq ASC
");

mysqli_stmt_bind_param($stmt, "s", $shape_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


$dados = [];

while($row = mysqli_fetch_assoc($result)){

    $row['id'] = (int)$row['id'];
    $row['seq'] = (int)$row['seq'];

    $dados[] = $row;
}


echo json_encode($dados);