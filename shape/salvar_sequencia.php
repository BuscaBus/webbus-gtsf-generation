<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


try {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );


    if (
        !$data ||
        !is_array($data) ||
        count($data) === 0
    ) {

        throw new Exception(
            "Nenhum dado recebido."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Recebe o Pattern
    |--------------------------------------------------------------------------
    */

    $pattern_id =
        (int) ($data[0]['pattern_id'] ?? 0);


    if ($pattern_id <= 0) {

        throw new Exception(
            "Padrão de viagem inválido."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Busca o Shape do Pattern
    |--------------------------------------------------------------------------
    */

    $stmt = $conexao->prepare("
        SELECT shape_id

        FROM trip_patterns

        WHERE pattern_id = ?

        LIMIT 1
    ");


    $stmt->bind_param(
        "i",
        $pattern_id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    if ($result->num_rows === 0) {

        throw new Exception(
            "Padrão de viagem não encontrado."
        );
    }


    $pattern =
        $result->fetch_assoc();


    $shape_id =
        $pattern['shape_id'];


    if (empty($shape_id)) {

        throw new Exception(
            "O padrão de viagem ainda não possui um Shape."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Inicia transação
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction(
        $conexao
    );


    /*
    |--------------------------------------------------------------------------
    | Remove sequência antiga
    |--------------------------------------------------------------------------
    */

    $stmtDelete =
        $conexao->prepare("
            DELETE FROM shape_stops
            WHERE shape_id = ?
        ");


    $stmtDelete->bind_param(
        "s",
        $shape_id
    );


    $stmtDelete->execute();


    /*
    |--------------------------------------------------------------------------
    | Prepara INSERT
    |--------------------------------------------------------------------------
    */

    $stmtInsert =
        $conexao->prepare("
            INSERT INTO shape_stops
            (
                shape_id,
                stop_id,
                seq,
                codigo,
                ponto,
                intervalo,
                stop_headsign
            )

            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?
            )
        ");


    /*
    |--------------------------------------------------------------------------
    | Salva sequência
    |--------------------------------------------------------------------------
    */

    foreach ($data as $item) {

        $stop_id =
            (int) $item['stop_id'];

        $seq =
            (int) $item['seq'];

        $codigo =
            trim($item['codigo']);

        $ponto =
            trim($item['ponto']);

        $intervalo =
            trim($item['intervalo']);

        $destino =
            trim($item['destino']);


        $stmtInsert->bind_param(
            "siissss",
            $shape_id,
            $stop_id,
            $seq,
            $codigo,
            $ponto,
            $intervalo,
            $destino
        );


        $stmtInsert->execute();
    }


    mysqli_commit(
        $conexao
    );


    echo json_encode([
        "status" => "ok",
        "message" =>
            "Sequência salva com sucesso."
    ]);

}
catch (Throwable $e) {

    mysqli_rollback(
        $conexao
    );


    echo json_encode([
        "status" => "erro",
        "message" =>
            $e->getMessage()
    ]);
}