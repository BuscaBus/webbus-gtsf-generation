<?php

require_once __DIR__ . "/../connection.php";

$stop_id = $_POST['stop_id'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';

if (!$stop_id || !$latitude || !$longitude) {
    exit('erro');
}

$stop_id = (int)$stop_id;

$sql = "UPDATE stops
        SET stop_lat = '$latitude',
            stop_lon = '$longitude'
        WHERE stop_id = $stop_id";

if (mysqli_query($conexao, $sql)) {
    echo "ok";
} else {
    echo "erro";
}

mysqli_close($conexao);