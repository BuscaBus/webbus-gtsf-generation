<?php

require_once __DIR__ . "/../connection.php";

if (
    !isset($_POST['pattern_id']) ||
    !isset($_POST['id-route'])
) {
    die("Padrão de viagem inválido.");
}

$pattern_id = (int) $_POST['pattern_id'];
$route_id   = (int) $_POST['id-route'];

$sql = "
    DELETE FROM trip_patterns
    WHERE pattern_id = ?
";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die("Erro ao preparar exclusão: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pattern_id
);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao excluir: " . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);

header("Location: register.php?id=" . $route_id);
exit;