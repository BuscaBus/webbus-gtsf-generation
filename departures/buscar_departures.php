<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


$pattern_id =
    isset($_GET['pattern_id'])
        ? (int) $_GET['pattern_id']
        : 0;


$service_id =
    isset($_GET['service_id'])
        ? trim($_GET['service_id'])
        : '';


if (
    $pattern_id <= 0 ||
    $service_id === ''
) {

    echo json_encode([]);
    exit;
}


/*
|--------------------------------------------------------------------------
| BUSCA AS TRIPS/HORÁRIOS DO PATTERN + SERVIÇO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        t.trip_id,
        td.departure_time,
        t.wheelchair_accessible

    FROM trips t

    INNER JOIN trip_departures td
        ON td.trip_id = t.trip_id

    WHERE t.pattern_id = ?
      AND t.service_id = ?

    ORDER BY td.departure_time ASC
";


$stmt =
    mysqli_prepare(
        $conexao,
        $sql
    );


mysqli_stmt_bind_param(
    $stmt,
    "is",
    $pattern_id,
    $service_id
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


$dados = [];


while (
    $row = mysqli_fetch_assoc(
        $result
    )
) {

    $row['trip_id'] =
        (int) $row['trip_id'];

    $row['wheelchair_accessible'] =
        (int) $row['wheelchair_accessible'];

    $dados[] =
        $row;
}


echo json_encode($dados);