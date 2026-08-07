<?php
require_once __DIR__ . "/../connection.php";

$trip_id = (int)$_GET['trip_id'];
$service_id = $_GET['service_id'];

$sql = "SELECT
            departure_time,
            wheelchair_accessible,
            timepoint
        FROM trip_departures
        WHERE trip_id = ?
          AND service_id = ?
        ORDER BY departure_time";

$stmt = mysqli_prepare($conexao, $sql);

mysqli_stmt_bind_param($stmt, "is", $trip_id, $service_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$dados = [];

while ($row = mysqli_fetch_assoc($result)) {
    $dados[] = $row;
}

echo json_encode($dados);