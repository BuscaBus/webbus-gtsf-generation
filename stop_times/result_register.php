<?php
include("../connection.php");

$shape_id = $_POST['shape_id'];
$route_id = $_POST['route_id'];
$trip_id = $_POST['trip_id'];

$stop_sequence = $_POST['stop_sequence'];
$stop_id = $_POST['stop_id'];
$arrival_time = $_POST['arrival_time'];
$departure_time = $_POST['departure_time'];
$stop_headsign = $_POST['stop_headsign'];
$timepoints = $_POST['timepoint'] ?? [];

for ($i = 0; $i < count($stop_id); $i++) {

    $seq = $stop_sequence[$i];
    $stop = $stop_id[$i];
    $arrival = $arrival_time[$i];
    $departure = $departure_time[$i];
    $headsign = $stop_headsign[$i];

    // 🔥 lógica correta do checkbox
    $timepoint = isset($timepoints[$seq]) ? 0 : 1;

    $sql = "
    INSERT INTO stop_times
    (trip_id, arrival_time, departure_time, stop_id, stop_sequence, stop_headsign, timepoint)
    VALUES
    ('$trip_id','$arrival','$departure','$stop','$seq','$headsign','$timepoint')

    ON DUPLICATE KEY UPDATE
        arrival_time = VALUES(arrival_time),
        departure_time = VALUES(departure_time),
        stop_id = VALUES(stop_id),
        stop_headsign = VALUES(stop_headsign),
        timepoint = VALUES(timepoint)
    ";

    mysqli_query($conexao, $sql);
}

header("Location: register.php?id=".$trip_id."&shape_id=".$shape_id."&success=1");
exit;
?>