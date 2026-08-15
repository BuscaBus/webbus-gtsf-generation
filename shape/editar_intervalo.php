<?php

require_once __DIR__ . "/../connection.php";

/*
|--------------------------------------------------------------------------
| FUNÇÕES DE GERAÇÃO DO STOP_TIMES
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . "/../stop_times/gerar_stop_times.php";


header(
    "Content-Type: application/json; charset=utf-8"
);


mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


/*
|--------------------------------------------------------------------------
| RECEBE OS DADOS
|--------------------------------------------------------------------------
*/

$data = json_decode(
    file_get_contents("php://input"),
    true
);


if (
    !$data ||
    !is_array($data) ||
    count($data) === 0
) {

    echo json_encode([
        "status" => "erro",
        "message" => "Dados inválidos."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| RECEBE O PATTERN
|--------------------------------------------------------------------------
|
| O pattern_id será utilizado para descobrir todas
| as trips afetadas e regenerar seus stop_times.
|
*/

$pattern_id =
    isset($data[0]['pattern_id'])
        ? (int) $data[0]['pattern_id']
        : 0;


if ($pattern_id <= 0) {

    echo json_encode([
        "status" => "erro",
        "message" => "Pattern inválido."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| INICIA TRANSAÇÃO
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction(
    $conexao
);


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

        /*
        |--------------------------------------------------------------------------
        | CONFERE PATTERN
        |--------------------------------------------------------------------------
        |
        | Impede misturar dados de patterns diferentes
        | no mesmo envio.
        |
        */

        $itemPatternId =
            isset($item['pattern_id'])
                ? (int) $item['pattern_id']
                : 0;


        if (
            $itemPatternId !== $pattern_id
        ) {

            throw new Exception(
                "Foram recebidos dados de patterns diferentes."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DADOS DA PARADA
        |--------------------------------------------------------------------------
        */

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


    /*
    |--------------------------------------------------------------------------
    | REGERA AUTOMATICAMENTE OS STOP_TIMES
    |--------------------------------------------------------------------------
    |
    | Como intervalo, timepoint ou stop_headsign podem ter mudado,
    | todas as trips deste pattern precisam ser recalculadas.
    |
    */

    $totalStopTimes =
        gerarStopTimesPattern(
            $conexao,
            $pattern_id
        );


    /*
    |--------------------------------------------------------------------------
    | FINALIZA TRANSAÇÃO
    |--------------------------------------------------------------------------
    */

    mysqli_commit(
        $conexao
    );


    /*
    |--------------------------------------------------------------------------
    | RETORNO
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "status" =>
            "ok",

        "message" =>
            "Dados atualizados e stop_times regenerados com sucesso.",

        "stop_times_gerados" =>
            $totalStopTimes
    ]);


}
catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DESFAZ TODAS AS ALTERAÇÕES
    |--------------------------------------------------------------------------
    */

    mysqli_rollback(
        $conexao
    );


    echo json_encode([
        "status" =>
            "erro",

        "message" =>
            $e->getMessage()
    ]);
}