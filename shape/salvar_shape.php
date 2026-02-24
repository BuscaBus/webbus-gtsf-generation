<?php

include("../connection.php");

header("Content-Type: application/json");

// Lê o JSON enviado pelo fetch
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

$shape_id = $data['shape_id'] ?? null;
$coords   = $data['coords'] ?? [];

// Validação básica
if (!$shape_id || empty($coords)) {
    echo json_encode([
        "status" => "error",
        "message" => "Shape inválido"
    ]);
    exit;
}

// Garante autocommit (evita problemas silenciosos)
mysqli_autocommit($conexao, true);

// Remove pontos antigos do shape (edição)
$stmt = mysqli_prepare(
    $conexao,
    "DELETE FROM shapes WHERE shape_id = ?"
);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Erro ao preparar DELETE",
        "detail" => mysqli_error($conexao)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $shape_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Insere novos pontos
$sql = "INSERT INTO shapes
        (shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence)
        VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Erro ao preparar INSERT",
        "detail" => mysqli_error($conexao)
    ]);
    exit;
}

$seq = 1;
foreach ($coords as $pt) {
    $lon = $pt[0];
    $lat = $pt[1];

    mysqli_stmt_bind_param(
        $stmt,
        "sddi",
        $shape_id,
        $lat,
        $lon,
        $seq
    );

    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao inserir ponto",
            "detail" => mysqli_stmt_error($stmt)
        ]);
        exit;
    }

    $seq++;
}

mysqli_stmt_close($stmt);

// Resposta final
echo json_encode([
    "status" => "ok",
    "message" => "Shape salvo com sucesso",
    "shape_id" => $shape_id
]);