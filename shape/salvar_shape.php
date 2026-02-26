<?php
include("../connection.php");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$shape_id     = $data['shape_id'] ?? null;
$route_id     = $data['route_id'] ?? null;
$coords       = $data['coords'] ?? [];

if (!$shape_id || !$route_id || empty($coords)) {
    echo json_encode([
        "status" => "error",
        "message" => "Dados inválidos"
    ]);
    exit;
}

mysqli_begin_transaction($conexao);

try {

    /* ===== SHAPES ===== */
    mysqli_query(
        $conexao,
        "DELETE FROM shapes WHERE shape_id = '$shape_id'"
    );

    $seq = 1;
    foreach ($coords as $pt) {
        $lon = $pt[0];
        $lat = $pt[1];

        mysqli_query(
            $conexao,
            "INSERT INTO shapes 
            (shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence)
            VALUES 
            ('$shape_id', '$lat', '$lon', $seq)"
        );

        $seq++;
    }

    /* ===== MAPS_TRIPS ===== */
    mysqli_query(
        $conexao,
        "DELETE FROM maps_trips 
         WHERE route_id = '$route_id' 
           AND shape_id = '$shape_id'"
          
    );

    mysqli_query(
        $conexao,
        "INSERT INTO maps_trips 
        (route_id, shape_id)
        VALUES 
        ('$route_id', '$shape_id')"
    );

    mysqli_commit($conexao);

    echo json_encode([
        "status" => "ok",
        "message" => "Shape salvo e vinculado à rota com sucesso"
    ]);

} catch (Exception $e) {

    mysqli_rollback($conexao);

    echo json_encode([
        "status" => "error",
        "message" => "Erro ao salvar",
        "detail" => $e->getMessage()
    ]);
}