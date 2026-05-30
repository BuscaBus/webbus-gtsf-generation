<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json");

$shape_antigo = $_POST['shape_antigo'] ?? '';
$shape_novo   = $_POST['shape_novo'] ?? '';

if (!$shape_antigo || !$shape_novo) {

    echo json_encode([
        "status" => "erro",
        "mensagem" => "Dados inválidos"
    ]);
    exit;
}

mysqli_begin_transaction($conexao);

try {

    mysqli_query(
        $conexao,
        "UPDATE shapes
         SET shape_id = '$shape_novo'
         WHERE shape_id = '$shape_antigo'"
    );

    mysqli_query(
        $conexao,
        "UPDATE maps_trips
         SET shape_id = '$shape_novo'
         WHERE shape_id = '$shape_antigo'"
    );

    mysqli_commit($conexao);

    echo json_encode([
        "status" => "ok"
    ]);

} catch (Exception $e) {

    mysqli_rollback($conexao);

    echo json_encode([
        "status" => "erro",
        "mensagem" => $e->getMessage()
    ]);
}