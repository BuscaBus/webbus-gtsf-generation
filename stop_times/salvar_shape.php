<?php
include("../connection.php");

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$trip_id = (int)($data["trip_id"] ?? 0);
$coords = $data["coords"] ?? [];

if (!$trip_id) {
    echo json_encode(["success" => false, "message" => "Trip inválida."]);
    exit;
}

// Buscar shape_id da trip
$res = mysqli_query($conexao, "SELECT shape_id FROM trips WHERE trip_id = $trip_id");
$row = mysqli_fetch_assoc($res);
$shape_id = $row['shape_id'] ?? null;

// Se coords vazio → remover shape
if (empty($coords)) {
    if ($shape_id) {
        mysqli_query($conexao, "DELETE FROM shapes WHERE shape_id = $shape_id");
        mysqli_query($conexao, "UPDATE trips SET shape_id = NULL WHERE trip_id = $trip_id");
    }
    echo json_encode(["success" => true, "message" => "Traçado removido.", "shape_id" => null]);
    exit;
}

// Se não tiver shape, criar novo
if (!$shape_id) {
    $shape_id = time();
    mysqli_query($conexao, "UPDATE trips SET shape_id = $shape_id WHERE trip_id = $trip_id");
} else {
    // Deleta shape antigo antes de salvar novo
    mysqli_query($conexao, "DELETE FROM shapes WHERE shape_id = $shape_id");
}

// Inserir pontos no banco
$stmt = $conexao->prepare("INSERT INTO shapes (shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence) VALUES (?, ?, ?, ?)");
$seq = 1;
foreach ($coords as $c) {
    $lon = $c[0];
    $lat = $c[1];
    $stmt->bind_param("iddi", $shape_id, $lat, $lon, $seq);
    $stmt->execute();
    $seq++;
}

echo json_encode(["success" => true, "message" => "Traçado salvo com sucesso!", "shape_id" => $shape_id]);
