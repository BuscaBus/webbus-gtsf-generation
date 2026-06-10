<?php
require_once __DIR__ . "/../connection.php";

$route_id = (int)$_GET['route_id'];

$sql = "
SELECT
    shape_id,
    MIN(trip_headsign) AS trip_headsign,
    MIN(trip_short_name) AS trip_short_name
FROM trips
WHERE route_id = $route_id
GROUP BY shape_id
ORDER BY trip_headsign;
";

$result = mysqli_query($conexao, $sql);

$dados = [];

while($row = mysqli_fetch_assoc($result)){
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);