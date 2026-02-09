<?php
include("../connection.php");

if (!isset($_GET['trip_id'])) {
    echo json_encode([]);
    exit;
}

$trip_id = intval($_GET['trip_id']);

// Busca direction_id e shape_id da viagem
$sql_trip = "
    SELECT t.direction_id, t.shape_id
    FROM trips t
    WHERE t.trip_id = $trip_id
";

$res_trip = mysqli_query($conexao, $sql_trip);
$trip = mysqli_fetch_assoc($res_trip);

if (!$trip || !$trip['shape_id']) {
    echo json_encode([]);
    exit;
}

$shape_id = $trip['shape_id'];
$direction_id = $trip['direction_id'];

// Busca os pontos do shape
$sql_shape = "
    SELECT shape_pt_lat, shape_pt_lon
    FROM shapes
    WHERE shape_id = $shape_id
    ORDER BY shape_pt_sequence ASC
";

$res_shape = mysqli_query($conexao, $sql_shape);

$pontos = [];
while ($row = mysqli_fetch_assoc($res_shape)) {
    $pontos[] = [
        floatval($row['shape_pt_lat']),
        floatval($row['shape_pt_lon'])
    ];
}

// Retorno no formato esperado pelo JS
echo json_encode([
    "direction_id" => $direction_id,
    "pontos" => $pontos
]);
