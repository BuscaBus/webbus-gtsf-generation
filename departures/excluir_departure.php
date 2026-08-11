<?php

require_once __DIR__ . "/../connection.php";

header(
    "Content-Type: application/json; charset=utf-8"
);

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


$data = json_decode(
    file_get_contents("php://input"),
    true
);


$trip_id =
    isset($data['trip_id'])
        ? (int) $data['trip_id']
        : 0;


if ($trip_id <= 0) {

    echo json_encode([
        "status" => "erro",
        "message" => "Trip inválida."
    ]);

    exit;
}


mysqli_begin_transaction(
    $conexao
);


try {

    /*
    |--------------------------------------------------------------------------
    | EXCLUI A TRIP
    |--------------------------------------------------------------------------
    |
    | trip_departures será excluído automaticamente
    | pela FK ON DELETE CASCADE.
    |
    */

    $stmt =
        $conexao->prepare("
            DELETE FROM trips
            WHERE trip_id = ?
        ");


    $stmt->bind_param(
        "i",
        $trip_id
    );


    $stmt->execute();


    if (
        $stmt->affected_rows === 0
    ) {

        throw new Exception(
            "Horário não encontrado."
        );
    }


    mysqli_commit(
        $conexao
    );


    echo json_encode([
        "status" => "ok",
        "message" =>
            "Horário excluído com sucesso."
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