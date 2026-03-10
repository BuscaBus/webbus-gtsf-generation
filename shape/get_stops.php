<?php
include("../connection.php");

header("Content-Type: application/json");

$north = $_GET['north'];
$south = $_GET['south'];
$east = $_GET['east'];
$west = $_GET['west'];

$sql = "
SELECT stop_id, stop_code, stop_name, stop_lat, stop_lon
FROM stops
WHERE 
    stop_lat BETWEEN $south AND $north
AND stop_lon BETWEEN $west AND $east
";

$result = mysqli_query($conexao, $sql);

$stops = [];

while($row = mysqli_fetch_assoc($result)){

    $stops[] = [
        "id" => $row["stop_id"],
        "code" => $row["stop_code"],
        "name" => $row["stop_name"],
        "lat" => (float)$row["stop_lat"],
        "lon" => (float)$row["stop_lon"]
    ];

}

echo json_encode($stops);