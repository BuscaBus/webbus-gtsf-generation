<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!$data) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Nenhum dado recebido."
    ]);
    exit;
}


$pattern_id = isset($data["pattern_id"])
    ? (int) $data["pattern_id"]
    : 0;

$shape_id = isset($data["shape_id"])
    ? trim($data["shape_id"])
    : "";


if ($pattern_id <= 0 || $shape_id === "") {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Pattern ou Shape inválido."
    ]);
    exit;
}


mysqli_begin_transaction($conexao);

try {

    /*
     * 1. Confirma o shape do pattern atual
     */
    $sql = "
        SELECT shape_id
        FROM trip_patterns
        WHERE pattern_id = ?
        FOR UPDATE
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $pattern_id
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $pattern = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmt);


    if (!$pattern) {
        throw new Exception(
            "Pattern não encontrado."
        );
    }


    if ($pattern["shape_id"] !== $shape_id) {
        throw new Exception(
            "O shape informado não pertence a este pattern."
        );
    }


    /*
     * 2. Remove o shape do pattern atual
     */
    $sql = "
        UPDATE trip_patterns
        SET shape_id = NULL
        WHERE pattern_id = ?
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $pattern_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);


    /*
     * 3. Verifica outros trip_patterns
     * utilizando o shape
     */
    $sql = "
        SELECT COUNT(*) AS total
        FROM trip_patterns
        WHERE shape_id = ?
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $shape_id
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($resultado);

    $usoPatterns = (int) $row["total"];

    mysqli_stmt_close($stmt);


    /*
     * 4. Verifica viagens em trips
     * utilizando o shape
     */
    $sql = "
        SELECT COUNT(*) AS total
        FROM trips
        WHERE shape_id = ?
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $shape_id
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($resultado);

    $usoTrips = (int) $row["total"];

    mysqli_stmt_close($stmt);


    /*
     * 5. Só exclui fisicamente o shape
     * se ninguém mais estiver usando.
     */
    $shapeExcluido = false;

    if (
        $usoPatterns === 0 &&
        $usoTrips === 0
    ) {

        $sql = "
            DELETE FROM shape_master
            WHERE shape_id = ?
        ";

        $stmt = mysqli_prepare($conexao, $sql);

        if (!$stmt) {
            throw new Exception(
                mysqli_error($conexao)
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $shape_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }

        $shapeExcluido =
            mysqli_stmt_affected_rows($stmt) > 0;

        mysqli_stmt_close($stmt);
    }


    mysqli_commit($conexao);


    /*
     * 6. Retorno
     */
    echo json_encode([
        "status" => "ok",
        "mensagem" => "Traçado removido deste padrão.",
        "shape_excluido" => $shapeExcluido,
        "uso_patterns" => $usoPatterns,
        "uso_trips" => $usoTrips
    ]);


} catch (Exception $e) {

    mysqli_rollback($conexao);

    echo json_encode([
        "status" => "erro",
        "mensagem" => $e->getMessage()
    ]);

}