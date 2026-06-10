<?php
require_once __DIR__ . "/../connection.php";

if (!isset($_GET['shape_id']) || empty($_GET['shape_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$shape_id = mysqli_real_escape_string(
    $conexao,
    $_GET['shape_id']
);

$sql = "
SELECT
    shape_pt_lat,
    shape_pt_lon
FROM shapes
WHERE shape_id = '$shape_id'
ORDER BY shape_pt_sequence
";

$result = mysqli_query($conexao, $sql);

$coords = [];

while ($row = mysqli_fetch_assoc($result)) {

    $coords[] = [
        (float)$row['shape_pt_lat'],
        (float)$row['shape_pt_lon']
    ];
}

header('Content-Type: application/json');
echo json_encode($coords);