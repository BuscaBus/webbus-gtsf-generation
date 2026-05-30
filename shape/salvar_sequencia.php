<?php
require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "erro"]);
    exit;
}

$shape_id = mysqli_real_escape_string(
    $conexao,
    $data[0]['shape_id']
);

// Remove a sequência antiga
mysqli_query(
    $conexao,
    "DELETE FROM shape_stops WHERE shape_id = '$shape_id'"
);

// Grava a nova sequência
foreach ($data as $item) {

    $seq       = mysqli_real_escape_string($conexao, $item['seq']);
    $codigo    = mysqli_real_escape_string($conexao, $item['codigo']);
    $ponto     = mysqli_real_escape_string($conexao, $item['ponto']);
    $intervalo = mysqli_real_escape_string($conexao, $item['intervalo']);
    $stop_id   = mysqli_real_escape_string($conexao, $item['stop_id']);

    $sql = "
        INSERT INTO shape_stops
        (
            shape_id,
            stop_id,
            seq,
            codigo,
            ponto,
            intervalo
        )
        VALUES
        (
            '$shape_id',
            '$stop_id',
            '$seq',
            '$codigo',
            '$ponto',
            '$intervalo'
        )
    ";

    mysqli_query($conexao, $sql);
}

echo json_encode(["status" => "ok"]);