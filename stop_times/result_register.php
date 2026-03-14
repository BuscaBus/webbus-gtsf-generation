<?php
include("../connection.php");

$trip_id = $_POST['trip_id'];
$shape_id = $_POST['shape_id'];

$stop_ids = $_POST['stop_id'];
$sequencias = $_POST['stop_sequence'];
$arrivals = $_POST['arrival_time'];
$departures = $_POST['departure_time'];
$headsigns = $_POST['stop_headsign'];

$total = count($stop_ids);

for ($i = 0; $i < $total; $i++) {

    $sql = "INSERT INTO stop_times
    (trip_id, arrival_time, departure_time, stop_id, stop_sequence, stop_headsign)
    VALUES (
        '{$trip_id}',
        '{$arrivals[$i]}',
        '{$departures[$i]}',
        '{$stop_ids[$i]}',
        '{$sequencias[$i]}',
        '{$headsigns[$i]}'
    )";

    mysqli_query($conexao, $sql);
}

header("Location: register.php?id=".$trip_id."&shape_id=".$shape_id."&success=1");
exit;