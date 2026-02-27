<?php
include("../connection.php");

if (isset($_GET['route_id'])) {
    $route_id = intval($_GET['route_id']);

    $sql = "SELECT MIN(trip_id) AS trip_id, 
                   CONCAT(TRIM(COALESCE(trip_short_name, '')), 
                          ' - ', 
                          TRIM(COALESCE(trip_headsign, ''))) AS viagem_nome
            FROM trips 
            WHERE route_id = $route_id 
            GROUP BY trip_short_name, trip_headsign
            ORDER BY trip_short_name, trip_headsign";

    $result = mysqli_query($conexao, $sql);

    $viagens = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // remove traço sobrando se short_name ou headsign estiver vazio
        $row['viagem_nome'] = trim($row['viagem_nome'], " -");
        $viagens[] = $row;
    }

    echo json_encode($viagens);
}
