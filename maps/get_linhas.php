<?php
include("../connection.php");

if (isset($_GET['agency_id'])) {
    $agency_id = intval($_GET['agency_id']);

    $sql = "SELECT route_id, 
                   CONCAT(route_short_name, ' - ', route_long_name) AS linha_nome
            FROM routes 
            WHERE agency_id = $agency_id 
            AND route_status = 'A'
            ORDER BY route_short_name ASC";
    $result = mysqli_query($conexao, $sql);

    $linhas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $linhas[] = $row;
    }

    echo json_encode($linhas);
}
