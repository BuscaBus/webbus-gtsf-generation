<?php
include("../connection.php");
header("Content-Type: application/json");

$shape_id = $_GET['shape_id'] ?? null;

if (!$shape_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT shape_pt_lat, shape_pt_lon
    FROM shapes
    WHERE shape_id = '$shape_id'
    ORDER BY shape_pt_sequence
";

$res = mysqli_query($conexao, $sql);

$coords = [];

while ($row = mysqli_fetch_assoc($res)) {
    $coords[] = [(float)$row['shape_pt_lat'], (float)$row['shape_pt_lon']];
}

echo json_encode($coords);