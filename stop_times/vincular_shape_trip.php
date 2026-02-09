<?php
include("../connection.php");
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$trip_origem_id = (int)($data["trip_origem_id"] ?? 0);
$trip_destino_id = (int)($data["trip_destino_id"] ?? 0);

if (!$trip_origem_id || !$trip_destino_id) {
    echo json_encode(["success" => false, "message" => "Trip de origem ou destino inválida."]);
    exit;
}

// Pegar shape da trip de origem
$res = mysqli_query($conexao, "SELECT shape_id FROM trips WHERE trip_id = $trip_origem_id");
$row = mysqli_fetch_assoc($res);
$shape_id_origem = $row['shape_id'] ?? null;

if (!$shape_id_origem) {
    echo json_encode(["success" => false, "message" => "A trip de origem não possui shape."]);
    exit;
}

// Buscar pontos do shape de origem
$res2 = mysqli_query($conexao, "SELECT shape_pt_lat, shape_pt_lon, shape_pt_sequence FROM shapes WHERE shape_id = $shape_id_origem ORDER BY shape_pt_sequence ASC");
$points = [];
while ($r = mysqli_fetch_assoc($res2)) {
    $points[] = $r;
}

// Criar novo shape_id para a trip destino
$new_shape_id = time();

// Deletar shape antigo da trip destino, se existir
$res3 = mysqli_query($conexao, "SELECT shape_id FROM trips WHERE trip_id = $trip_destino_id");
$row3 = mysqli_fetch_assoc($res3);
if ($row3 && $row3['shape_id']) {
    mysqli_query($conexao, "DELETE FROM shapes WHERE shape_id = " . $row3['shape_id']);
}

// Inserir pontos com novo shape_id
$stmt = $conexao->prepare("INSERT INTO shapes (shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence) VALUES (?, ?, ?, ?)");
$seq = 1;
foreach ($points as $p) {
    $lat = $p['shape_pt_lat'];
    $lon = $p['shape_pt_lon'];
    $stmt->bind_param("iddi", $new_shape_id, $lat, $lon, $seq);
    $stmt->execute();
    $seq++;
}

// Atualizar trip destino para usar o novo shape_id
mysqli_query($conexao, "UPDATE trips SET shape_id = $new_shape_id WHERE trip_id = $trip_destino_id");

echo json_encode(["success" => true, "message" => "Shape reaproveitado e salvo na trip inicial com sucesso!"]);
