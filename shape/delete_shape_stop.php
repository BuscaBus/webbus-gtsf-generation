<?php

require_once __DIR__ . "/../connection.php";

require_once __DIR__
    . "/../stop_times/gerar_stop_times.php";


header(
    "Content-Type: application/json; charset=utf-8"
);


mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


try {

    /*
    |--------------------------------------------------------------------------
    | RECEBER JSON
    |--------------------------------------------------------------------------
    */

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );


    if (!is_array($data)) {

        throw new Exception(
            "Dados inválidos."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR ID
    |--------------------------------------------------------------------------
    */

    $id =
        isset($data["id"])
            ? (int) $data["id"]
            : 0;


    if ($id <= 0) {

        throw new Exception(
            "ID da parada inválido."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOCALIZAR A PARADA
    |--------------------------------------------------------------------------
    |
    | Precisamos descobrir o shape_id antes da exclusão.
    |
    */

    $stmtStop =
        $conexao->prepare("
            SELECT
                Id,
                shape_id

            FROM shape_stops

            WHERE Id = ?

            LIMIT 1
        ");


    $stmtStop->bind_param(
        "i",
        $id
    );


    $stmtStop->execute();


    $registro =
        $stmtStop
            ->get_result()
            ->fetch_assoc();


    if (!$registro) {

        throw new Exception(
            "Ponto não encontrado no banco de dados."
        );
    }


    $shape_id =
        trim(
            $registro["shape_id"] ?? ""
        );


    if ($shape_id === "") {

        throw new Exception(
            "A parada não possui Shape associado."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCAR TODOS OS PATTERNS DO SHAPE
    |--------------------------------------------------------------------------
    */

    $stmtPatterns =
        $conexao->prepare("
            SELECT
                pattern_id

            FROM trip_patterns

            WHERE shape_id = ?

            ORDER BY pattern_id
        ");


    $stmtPatterns->bind_param(
        "s",
        $shape_id
    );


    $stmtPatterns->execute();


    $resultadoPatterns =
        $stmtPatterns->get_result();


    $patterns = [];


    while (
        $row =
            $resultadoPatterns->fetch_assoc()
    ) {

        $patterns[] =
            (int) $row["pattern_id"];
    }


    /*
    |--------------------------------------------------------------------------
    | INICIAR TRANSAÇÃO
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction(
        $conexao
    );


    /*
    |--------------------------------------------------------------------------
    | EXCLUIR PARADA
    |--------------------------------------------------------------------------
    */

    $stmtDelete =
        $conexao->prepare("
            DELETE FROM shape_stops

            WHERE Id = ?
        ");


    $stmtDelete->bind_param(
        "i",
        $id
    );


    $stmtDelete->execute();


    if (
        $stmtDelete->affected_rows <= 0
    ) {

        throw new Exception(
            "Nenhum ponto foi excluído."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REORGANIZAR SEQUÊNCIA
    |--------------------------------------------------------------------------
    |
    | Exemplo:
    |
    | Antes:
    | 1
    | 2
    | 3
    | 4
    |
    | Exclui 2
    |
    | Banco temporariamente:
    | 1
    | 3
    | 4
    |
    | Depois deste processo:
    | 1
    | 2
    | 3
    |
    |--------------------------------------------------------------------------
    */

    $stmtSequencia =
        $conexao->prepare("
            SELECT
                Id

            FROM shape_stops

            WHERE shape_id = ?

            ORDER BY
                seq ASC,
                Id ASC
        ");


    $stmtSequencia->bind_param(
        "s",
        $shape_id
    );


    $stmtSequencia->execute();


    $resultadoSequencia =
        $stmtSequencia->get_result();


    /*
     * Prepara UPDATE uma única vez.
     */

    $stmtUpdateSeq =
        $conexao->prepare("
            UPDATE shape_stops

            SET seq = ?

            WHERE Id = ?
        ");


    $novaSeq = 1;


    while (
        $row =
            $resultadoSequencia->fetch_assoc()
    ) {

        $idShapeStop =
            (int) $row["Id"];


        $stmtUpdateSeq->bind_param(
            "ii",
            $novaSeq,
            $idShapeStop
        );


        $stmtUpdateSeq->execute();


        $novaSeq++;
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR QUANTAS PARADAS RESTARAM
    |--------------------------------------------------------------------------
    */

    $stmtCount =
        $conexao->prepare("
            SELECT
                COUNT(*) AS total

            FROM shape_stops

            WHERE shape_id = ?
        ");


    $stmtCount->bind_param(
        "s",
        $shape_id
    );


    $stmtCount->execute();


    $resultadoCount =
        $stmtCount
            ->get_result()
            ->fetch_assoc();


    $totalParadas =
        (int) (
            $resultadoCount["total"] ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | ATUALIZAR STOP_TIMES
    |--------------------------------------------------------------------------
    */

    $totalStopTimes = 0;


    if ($totalParadas > 0) {

        /*
        |--------------------------------------------------------------------------
        | AINDA EXISTEM PARADAS
        |--------------------------------------------------------------------------
        |
        | Como a sequência já foi reorganizada,
        | os novos stop_times serão gerados com:
        |
        | stop_sequence = 1, 2, 3, 4...
        |
        */

        foreach (
            $patterns as $pattern_id
        ) {

            $totalStopTimes +=
                gerarStopTimesPattern(
                    $conexao,
                    $pattern_id
                );
        }

    } else {

        /*
        |--------------------------------------------------------------------------
        | NÃO EXISTEM MAIS PARADAS
        |--------------------------------------------------------------------------
        |
        | Não existe stop_times para gerar.
        |
        | Portanto removemos os stop_times das trips
        | relacionadas aos patterns desse shape.
        |
        */

        $stmtDeleteStopTimes =
            $conexao->prepare("
                DELETE st

                FROM stop_times st

                INNER JOIN trips t
                    ON t.trip_id = st.trip_id

                INNER JOIN trip_patterns tp
                    ON tp.pattern_id = t.pattern_id

                WHERE tp.shape_id = ?
            ");


        $stmtDeleteStopTimes->bind_param(
            "s",
            $shape_id
        );


        $stmtDeleteStopTimes->execute();
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
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

    echo json_encode(
        [
            "status" =>
                "ok",

            "message" =>
                "Ponto excluído e sequência reorganizada com sucesso.",

            "paradas_restantes" =>
                $totalParadas,

            "stop_times_gerados" =>
                $totalStopTimes
        ],
        JSON_UNESCAPED_UNICODE
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    try {

        mysqli_rollback(
            $conexao
        );

    } catch (Throwable $rollbackError) {

        /*
         * Pode ocorrer caso o erro aconteça
         * antes do início da transação.
         */

    }


    /*
    |--------------------------------------------------------------------------
    | RETORNO DE ERRO
    |--------------------------------------------------------------------------
    */

    http_response_code(500);


    echo json_encode(
        [
            "status" =>
                "erro",

            "message" =>
                $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
}