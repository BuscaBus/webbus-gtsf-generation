<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

$trip_id = isset($_GET["trip_id"]) ? (int)$_GET["trip_id"] : 0;

if ($trip_id <= 0) {
    echo json_encode([]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Busca o Shape da Trip
|--------------------------------------------------------------------------
*/

$stmt = $conexao->prepare("
    SELECT shape_id
    FROM trips
    WHERE trip_id = ?
");

$stmt->bind_param("i", $trip_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    echo json_encode([]);
    exit;

}

$trip = $result->fetch_assoc();

if (empty($trip["shape_id"])) {

    echo json_encode([]);
    exit;

}

$shape_id = $trip["shape_id"];

/*
|--------------------------------------------------------------------------
| Busca os pontos
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

$stmt->bind_param("s", $shape_id);
$stmt->execute();

$result = $stmt->get_result();

$coords = [];

while ($row = $result->fetch_assoc()) {

    $coords[] = [
        (float)$row["shape_pt_lat"],
        (float)$row["shape_pt_lon"]
    ];

}

echo json_encode($coords);