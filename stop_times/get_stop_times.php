<?php
include("../connection.php");

$trip_id = $_GET['trip_id'] ?? null;

if (!$trip_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT stop_id, arrival_time, departure_time, stop_headsign, timepoint 
        FROM stop_times 
        WHERE trip_id = $trip_id";

$result = mysqli_query($conexao, $sql);

$dados = [];

while ($row = mysqli_fetch_assoc($result)) {
    $dados[$row['stop_id']] = $row;
}

echo json_encode($dados);