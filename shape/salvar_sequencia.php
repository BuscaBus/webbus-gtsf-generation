<?php
require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json");

// Faz o mysqli lançar exceções em caso de erro
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !is_array($data) || count($data) == 0) {
        throw new Exception("Nenhum dado recebido.");
    }

    // Recebe a Trip
    $trip_id = (int)$data[0]['trip_id'];

    if ($trip_id <= 0) {
        throw new Exception("Trip inválida.");
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
        throw new Exception("Trip não encontrada.");
    }

    $trip = $result->fetch_assoc();

    $shape_id = $trip['shape_id'];

    if (empty($shape_id)) {
        throw new Exception("A Trip ainda não possui um Shape.");
    }

    /*
    |--------------------------------------------------------------------------
    | Inicia transação
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction($conexao);

    /*
    |--------------------------------------------------------------------------
    | Remove sequência antiga
    |--------------------------------------------------------------------------
    */

    $stmtDelete = $conexao->prepare("
        DELETE FROM shape_stops
        WHERE shape_id = ?
    ");

    $stmtDelete->bind_param("s", $shape_id);
    $stmtDelete->execute();

    /*
    |--------------------------------------------------------------------------
    | Prepara INSERT
    |--------------------------------------------------------------------------
    */

    $stmtInsert = $conexao->prepare("
        INSERT INTO shape_stops
        (
            shape_id,
            stop_id,
            seq,
            codigo,
            ponto,
            intervalo
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?
        )
    ");

    foreach ($data as $item) {

        $stop_id   = (int)$item['stop_id'];
        $seq       = (int)$item['seq'];
        $codigo    = trim($item['codigo']);
        $ponto     = trim($item['ponto']);
        $intervalo = trim($item['intervalo']);

        $stmtInsert->bind_param(
            "siisss",
            $shape_id,
            $stop_id,
            $seq,
            $codigo,
            $ponto,
            $intervalo
        );

        $stmtInsert->execute();
    }

    mysqli_commit($conexao);

    echo json_encode([
        "status" => "ok",
        "message" => "Sequência salva com sucesso."
    ]);

} catch (Exception $e) {

    if ($conexao) {
        mysqli_rollback($conexao);
    }

    echo json_encode([
        "status" => "erro",
        "message" => $e->getMessage()
    ]);
}