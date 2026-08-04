<?php
require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if ($trip_id <= 0) {
    echo json_encode([]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Busca o Shape da Trip
|--------------------------------------------------------------------------
*/

$stmt = $conexao->prepare("
    SELECT shape_id
    FROM trips
    WHERE trip_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $trip_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode([]);
    exit;
}

$trip = $result->fetch_assoc();

$shape_id = $trip['shape_id'];

if (empty($shape_id)) {
    echo json_encode([]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Busca a sequência de paradas
|--------------------------------------------------------------------------
*/

$stmt = $conexao->prepare("
    SELECT
        Id,
        seq,
        stop_id,
        codigo,
        ponto,
        intervalo,
        stop_headsign
    FROM shape_stops
    WHERE shape_id = ?
    ORDER BY seq ASC
");

$stmt->bind_param("s", $shape_id);
$stmt->execute();

$result = $stmt->get_result();

$dados = [];

while ($row = $result->fetch_assoc()) {

    $row['id'] = (int)$row['Id'];
    $row['seq'] = (int)$row['seq'];

    $dados[] = $row;
}

echo json_encode($dados);