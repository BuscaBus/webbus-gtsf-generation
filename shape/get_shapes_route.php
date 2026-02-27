<?php
include("../connection.php");
header("Content-Type: application/json");

$route_id = $_GET['route_id'] ?? null;

if (!$route_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT DISTINCT mt.shape_id
    FROM maps_trips mt
    WHERE mt.route_id = '$route_id'
    ORDER BY mt.shape_id
";

$res = mysqli_query($conexao, $sql);

$shapes = [];

while ($row = mysqli_fetch_assoc($res)) {
    $shapes[] = $row['shape_id'];
}

echo json_encode($shapes);