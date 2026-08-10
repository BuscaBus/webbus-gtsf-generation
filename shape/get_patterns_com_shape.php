<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


$route_id = isset($_GET['route_id'])
    ? (int) $_GET['route_id']
    : 0;


$pattern_id = isset($_GET['pattern_id'])
    ? (int) $_GET['pattern_id']
    : 0;


if ($route_id <= 0) {

    echo json_encode([]);
    exit;
}


$sql = "
    SELECT
        tp.pattern_id,
        tp.shape_id,
        tp.trip_short_name,
        tp.trip_headsign

    FROM trip_patterns tp

    WHERE tp.route_id = ?

    AND tp.pattern_id <> ?

    AND tp.shape_id IS NOT NULL

    AND tp.shape_id <> ''

    ORDER BY tp.pattern_id ASC
";


$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "ii",
    $route_id,
    $pattern_id
);

$stmt->execute();


$result =
    $stmt->get_result();


$patterns = [];


while (
    $row = $result->fetch_assoc()
) {

    $nome = "";


    if (
        !empty($row['trip_short_name'])
    ) {

        $nome .=
            $row['trip_short_name'];
    }


    if (
        !empty($row['trip_headsign'])
    ) {

        if ($nome !== "") {
            $nome .= " - ";
        }

        $nome .=
            $row['trip_headsign'];
    }


    if ($nome === "") {

        $nome =
            "Pattern " .
            $row['pattern_id'];
    }


    $patterns[] = [

        "pattern_id" =>
            (int) $row['pattern_id'],

        "shape_id" =>
            $row['shape_id'],

        "nome" =>
            $nome
    ];
}


echo json_encode($patterns);