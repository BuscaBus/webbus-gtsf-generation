<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json");

$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;
$trip_id  = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

$sql = "
SELECT
    t.trip_id,
    t.shape_id,
    t.trip_short_name,
    t.trip_headsign
FROM trips t
WHERE t.route_id = ?
  AND t.trip_id <> ?
  AND t.shape_id IS NOT NULL
  AND t.shape_id <> ''
ORDER BY t.trip_id ASC
";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("ii", $route_id, $trip_id);
$stmt->execute();

$result = $stmt->get_result();

$trips = [];

while ($row = $result->fetch_assoc()) {

    $nome = "";

    if (!empty($row['trip_short_name'])) {
        $nome .= $row['trip_short_name'];
    }

    if (!empty($row['trip_headsign'])) {
        if ($nome != "") {
            $nome .= " - ";
        }
        $nome .= $row['trip_headsign'];
    }

    if ($nome == "") {
        $nome = "Trip " . $row['trip_id'];
    }

    $trips[] = [
        "trip_id"  => $row['trip_id'],
        "shape_id" => $row['shape_id'],
        "nome"     => $nome
    ];
}

echo json_encode($trips);