<?php
include("../connection.php");
header("Content-Type: application/json");

$route_id = $_GET['route_id'] ?? null;

if (!$route_id) {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT 
    s.shape_id,
    s.shape_pt_lat,
    s.shape_pt_lon,
    s.shape_pt_sequence
FROM maps_trips mt
JOIN shapes s ON s.shape_id = mt.shape_id
WHERE mt.route_id = ?
ORDER BY mt.id ASC, s.shape_pt_sequence ASC
";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("s", $route_id);
$stmt->execute();
$result = $stmt->get_result();

$shapes = [];

while ($row = $result->fetch_assoc()) {
    $shapes[$row['shape_id']][] = [
        (float)$row['shape_pt_lat'],
        (float)$row['shape_pt_lon']
    ];
}

echo json_encode($shapes);