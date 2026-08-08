<?php

require_once __DIR__ . "/../connection.php";

if (
    !isset(
        $_POST['id_route'],
        $_POST['pattern_id'],
        $_POST['origem'],
        $_POST['destino'],
        $_POST['partida']
    )
) {
    die("Dados inválidos.");
}

$id_route       = (int) $_POST['id_route'];
$pattern_id     = (int) $_POST['pattern_id'];
$origem         = trim($_POST['origem']);
$destino        = trim($_POST['destino']);
$local_partida  = trim($_POST['partida']);

$sql = "
    UPDATE trip_patterns

    SET
        trip_short_name = ?,
        trip_headsign = ?,
        departure_location = ?

    WHERE pattern_id = ?
";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die("Erro ao preparar edição: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param(
    $stmt,
    "sssi",
    $origem,
    $destino,
    $local_partida,
    $pattern_id
);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao editar padrão: " . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);

header("Location: register.php?id=" . $id_route);
exit;