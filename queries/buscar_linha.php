<?php
include("../connection.php");

if (isset($_GET['operadora'])) {
    $operadora = (int) $_GET['operadora']; // força inteiro (agency_id)

    $sql = "SELECT r.route_id,
                   CONCAT(r.route_short_name, ' - ', r.route_long_name) AS route_name
            FROM routes r 
            INNER JOIN agency a ON r.agency_id = a.agency_id
            WHERE a.agency_id = $operadora 
            ORDER BY r.route_long_name ASC";

    $result = mysqli_query($conexao, $sql);

    $linhas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $linhas[] = [
            'id'   => $row['route_id'],   // manda o id
            'nome' => $row['route_name']  // manda o nome formatado
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($linhas);
}
?>
