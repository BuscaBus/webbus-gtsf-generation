<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json");

$sql = "SELECT
    t.trip_id,
    t.shape_id,
    t.trip_short_name,
    t.trip_headsign
FROM trips t
WHERE t.shape_id IS NOT NULL AND t.shape_id <> ''
ORDER BY
    t.trip_id ASC
";

$result = mysqli_query($conexao, $sql);

$trips = [];

while ($row = mysqli_fetch_assoc($result)) {
    $nome = "";

    if (!empty($row['trip_short_name'])) {
        $nome .= $row['trip_short_name'];
    }

    if (!empty($row['trip_headsign'])) {
        if ($nome != "") {
            $nome .= " - ";
        }

        $nome .= $row['trip_headsign'];
    }

    // caso os campos estejam vazios
    if ($nome == "") {
        $nome = "Trip " . $row['trip_id'];
    }

    $trips[] = [
        "trip_id" => $row['trip_id'],
        "shape_id" => $row['shape_id'],
        "nome" => $nome
    ];
}

echo json_encode($trips);
