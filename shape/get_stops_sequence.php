<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


$pattern_id = isset($_GET['pattern_id'])
    ? (int) $_GET['pattern_id']
    : 0;


if ($pattern_id <= 0) {

    echo json_encode([]);
    exit;
}


/*
|--------------------------------------------------------------------------
| Busca o Shape do Pattern
|--------------------------------------------------------------------------
*/

$stmt = $conexao->prepare("
    SELECT shape_id

    FROM trip_patterns

    WHERE pattern_id = ?

    LIMIT 1
");


$stmt->bind_param(
    "i",
    $pattern_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    echo json_encode([]);
    exit;
}


$pattern =
    $result->fetch_assoc();


$shape_id =
    $pattern['shape_id'];


if (empty($shape_id)) {

    echo json_encode([]);
    exit;
}


/*
|--------------------------------------------------------------------------
| Busca a sequência de paradas
|--------------------------------------------------------------------------
*/

$stmt = $conexao->prepare("
    SELECT
        id,
        seq,
        stop_id,
        codigo,
        ponto,
        intervalo,
        timepoint,
        stop_headsign

    FROM shape_stops

    WHERE shape_id = ?

    ORDER BY seq ASC
");


$stmt->bind_param(
    "s",
    $shape_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$dados = [];


/*
|--------------------------------------------------------------------------
| Monta retorno
|--------------------------------------------------------------------------
*/

while (
    $row = $result->fetch_assoc()
) {

    $row['id'] =
        (int) $row['id'];

    $row['seq'] =
        (int) $row['seq'];

    $row['stop_id'] =
        (int) $row['stop_id'];

    $row['timepoint'] =
        (int) $row['timepoint'];


    $dados[] =
        $row;
}


echo json_encode($dados);