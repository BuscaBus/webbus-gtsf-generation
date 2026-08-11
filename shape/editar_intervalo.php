<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


$data = json_decode(
    file_get_contents("php://input"),
    true
);


if (
    !$data ||
    !is_array($data)
) {

    echo json_encode([
        "status" => "erro",
        "message" => "Dados inválidos."
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | PREPARA UPDATE
    |--------------------------------------------------------------------------
    */

    $sql = "
        UPDATE shape_stops

        SET
            intervalo = ?,
            timepoint = ?,
            stop_headsign = ?

        WHERE id = ?
    ";


    $stmt =
        $conexao->prepare(
            $sql
        );


    /*
    |--------------------------------------------------------------------------
    | ATUALIZA CADA PONTO
    |--------------------------------------------------------------------------
    */

    foreach ($data as $item) {

        $id =
            isset($item['id'])
                ? (int) $item['id']
                : 0;


        $intervalo =
            isset($item['intervalo'])
                ? trim($item['intervalo'])
                : '';


        $timepoint =
            isset($item['timepoint'])
                ? (int) $item['timepoint']
                : 0;


        $destino =
            isset($item['destino'])
                ? trim($item['destino'])
                : '';


        /*
        |--------------------------------------------------------------------------
        | VALIDAÇÕES
        |--------------------------------------------------------------------------
        */

        if ($id <= 0) {
            continue;
        }


        if (
            $timepoint !== 0 &&
            $timepoint !== 1
        ) {

            $timepoint = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | EXECUTA UPDATE
        |--------------------------------------------------------------------------
        */

        $stmt->bind_param(
            "sisi",
            $intervalo,
            $timepoint,
            $destino,
            $id
        );


        $stmt->execute();
    }


    echo json_encode([
        "status" => "ok",
        "message" => "Dados atualizados com sucesso."
    ]);


}
catch (Throwable $e) {

    echo json_encode([
        "status" => "erro",
        "message" => $e->getMessage()
    ]);
}