<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");


$pattern_id = isset($_GET["pattern_id"])
    ? (int) $_GET["pattern_id"]
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


if (
    empty($pattern["shape_id"])
) {

    echo json_encode([]);
    exit;
}


$shape_id =
    $pattern["shape_id"];


/*
|--------------------------------------------------------------------------
| Busca os pontos do Shape
|--------------------------------------------------------------------------
*/

$stmt = $conexao->prepare("
    SELECT
        shape_pt_lat,
        shape_pt_lon

    FROM shapes

    WHERE shape_id = ?

    ORDER BY shape_pt_sequence
");


$stmt->bind_param(
    "s",
    $shape_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$coords = [];


while (
    $row = $result->fetch_assoc()
) {

    $coords[] = [

        (float) $row["shape_pt_lat"],

        (float) $row["shape_pt_lon"]

    ];
}


echo json_encode($coords);