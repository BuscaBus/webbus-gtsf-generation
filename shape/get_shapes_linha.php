<?php
include("../connection.php");

$route_id = intval($_GET['route_id']);

$sql = "
    SELECT 
        t.trip_id,
        t.direction_id,
        s.shape_pt_lat,
        s.shape_pt_lon,
        s.shape_pt_sequence
    FROM trips t
    JOIN shapes s ON s.shape_id = t.shape_id
    WHERE t.route_id = $route_id
    ORDER BY t.trip_id, s.shape_pt_sequence
";

$result = mysqli_query($conexao, $sql);

$viagens = [];

while ($row = mysqli_fetch_assoc($result)) {
    $trip_id = $row['trip_id'];

    if (!isset($viagens[$trip_id])) {
        $viagens[$trip_id] = [
            "direction_id" => $row['direction_id'],
            "pontos" => []
        ];
    }

    $viagens[$trip_id]["pontos"][] = [
        floatval($row['shape_pt_lat']),
        floatval($row['shape_pt_lon'])
    ];
}

echo json_encode(array_values($viagens));
