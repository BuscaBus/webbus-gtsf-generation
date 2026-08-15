<?php

/*
|--------------------------------------------------------------------------
| GERAR STOP_TIMES
|--------------------------------------------------------------------------
|
| Gera automaticamente os registros de stop_times utilizando:
|
| trips
| trip_departures
| shape_stops
|
| Fórmula:
|
| horário da parada =
| departure_time da trip + intervalo acumulado da parada
|
*/


function gerarStopTimes(
    mysqli $conexao,
    int $pattern_id,
    string $service_id
): int {


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if ($pattern_id <= 0) {

        throw new Exception(
            "Pattern inválido para geração de stop_times."
        );
    }


    $service_id =
        trim($service_id);


    if ($service_id === '') {

        throw new Exception(
            "Serviço inválido para geração de stop_times."
        );
    }



    /*
    |--------------------------------------------------------------------------
    | 1. VERIFICA SE O PATTERN POSSUI SHAPE
    |--------------------------------------------------------------------------
    */

    $stmtPattern =
        $conexao->prepare("
            SELECT shape_id

            FROM trip_patterns

            WHERE pattern_id = ?

            LIMIT 1
        ");


    $stmtPattern->bind_param(
        "i",
        $pattern_id
    );


    $stmtPattern->execute();


    $resultPattern =
        $stmtPattern->get_result();


    if (
        $resultPattern->num_rows === 0
    ) {

        throw new Exception(
            "Pattern não encontrado."
        );
    }


    $pattern =
        $resultPattern->fetch_assoc();


    $shape_id =
        trim(
            $pattern['shape_id'] ?? ''
        );


    if ($shape_id === '') {

        throw new Exception(
            "O pattern ainda não possui um trajeto cadastrado."
        );
    }



    /*
    |--------------------------------------------------------------------------
    | 2. VERIFICA SE EXISTEM PARADAS
    |--------------------------------------------------------------------------
    */

    $stmtStops =
        $conexao->prepare("
            SELECT COUNT(*) AS total

            FROM shape_stops

            WHERE shape_id = ?
        ");


    $stmtStops->bind_param(
        "s",
        $shape_id
    );


    $stmtStops->execute();


    $resultStops =
        $stmtStops->get_result();


    $totalStops =
        (int) $resultStops
            ->fetch_assoc()['total'];


    if ($totalStops === 0) {

        throw new Exception(
            "O trajeto não possui paradas cadastradas."
        );
    }



    /*
    |--------------------------------------------------------------------------
    | 3. EXCLUI STOP_TIMES ANTIGOS
    |--------------------------------------------------------------------------
    |
    | Apaga somente os registros das trips pertencentes
    | ao pattern + serviço informado.
    |
    */

    $stmtDelete =
        $conexao->prepare("
            DELETE st

            FROM stop_times st

            INNER JOIN trips t
                ON t.trip_id = st.trip_id

            WHERE t.pattern_id = ?
              AND t.service_id = ?
        ");


    $stmtDelete->bind_param(
        "is",
        $pattern_id,
        $service_id
    );


    $stmtDelete->execute();



    /*
    |--------------------------------------------------------------------------
    | 4. GERA OS STOP_TIMES
    |--------------------------------------------------------------------------
    |
    | shape_stops.intervalo é acumulado desde a
    | primeira parada.
    |
    | Portanto:
    |
    | 06:00 + 00:00 = 06:00
    | 06:00 + 00:05 = 06:05
    | 06:00 + 00:12 = 06:12
    |
    */

    $sqlInsert = "
        INSERT INTO stop_times
        (
            trip_id,
            arrival_time,
            departure_time,
            stop_id,
            stop_sequence,
            stop_headsign,
            timepoint
        )

        SELECT

            t.trip_id,

            ADDTIME(
                td.departure_time,
                ss.intervalo
            ) AS arrival_time,

            ADDTIME(
                td.departure_time,
                ss.intervalo
            ) AS departure_time,

            ss.stop_id,

            ss.seq,

            NULLIF(
                TRIM(ss.stop_headsign),
                ''
            ),

            ss.timepoint

        FROM trips t

        INNER JOIN trip_departures td
            ON td.trip_id = t.trip_id

        INNER JOIN shape_stops ss
            ON ss.shape_id = t.shape_id

        WHERE t.pattern_id = ?
          AND t.service_id = ?

        ORDER BY
            t.trip_id,
            ss.seq
    ";


    $stmtInsert =
        $conexao->prepare(
            $sqlInsert
        );


    $stmtInsert->bind_param(
        "is",
        $pattern_id,
        $service_id
    );


    $stmtInsert->execute();


    return
        $stmtInsert->affected_rows;

        
}


    /*
    |--------------------------------------------------------------------------
    | 4. Gerar todos os serviços de um pattern
    |--------------------------------------------------------------------------
    */
    function gerarStopTimesPattern(
        mysqli $conexao,
        int $pattern_id
    ): int {

        $stmt =
            $conexao->prepare("
                SELECT DISTINCT service_id

                FROM trips

                WHERE pattern_id = ?

                ORDER BY service_id
            ");


        $stmt->bind_param(
            "i",
            $pattern_id
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        $total = 0;


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $total +=
                gerarStopTimes(
                    $conexao,
                    $pattern_id,
                    $row['service_id']
                );
        }


        return $total;
    }
