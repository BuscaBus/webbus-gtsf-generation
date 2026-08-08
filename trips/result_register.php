<?php

require_once __DIR__ . "/../connection.php";

$route_id = (int) $_POST['id'];
$origem   = trim($_POST['origem']);
$destino  = trim($_POST['destino']);
$sentido  = (int) $_POST['sentido'];
$partida  = trim($_POST['partida']);

$sql = "
    INSERT INTO trip_patterns (
        route_id,
        trip_short_name,
        trip_headsign,
        direction_id,
        departure_location
    )
    VALUES (?, ?, ?, ?, ?)
";

$stmt = mysqli_prepare($conexao, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "issis",
    $route_id,
    $origem,
    $destino,
    $sentido,
    $partida
);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao cadastrar padrão: " . mysqli_stmt_error($stmt));
}

header("Location: register.php?id=" . $route_id);
exit;