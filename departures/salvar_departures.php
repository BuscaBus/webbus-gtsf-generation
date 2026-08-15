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
        "message" => "Nenhum horário recebido."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| PATTERN E SERVIÇO
|--------------------------------------------------------------------------
*/

$pattern_id =
    isset($data[0]['pattern_id'])
        ? (int) $data[0]['pattern_id']
        : 0;


$service_id =
    isset($data[0]['service_id'])
        ? trim($data[0]['service_id'])
        : '';


if (
    $pattern_id <= 0 ||
    $service_id === ''
) {

    echo json_encode([
        "status" => "erro",
        "message" =>
            "Pattern ou serviço inválido."
    ]);

    exit;
}


mysqli_begin_transaction(
    $conexao
);


try {


    /*
    |--------------------------------------------------------------------------
    | 1. BUSCA O TRIP_PATTERN
    |--------------------------------------------------------------------------
    */

    $sqlPattern = "
        SELECT
            pattern_id,
            route_id,
            trip_headsign,
            trip_short_name,
            direction_id,
            shape_id

        FROM trip_patterns

        WHERE pattern_id = ?

        LIMIT 1
    ";


    $stmtPattern =
        $conexao->prepare(
            $sqlPattern
        );


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
            "Padrão de viagem não encontrado."
        );
    }


    $pattern =
        $resultPattern->fetch_assoc();


    $route_id =
        (int) $pattern['route_id'];


    $trip_headsign =
        $pattern['trip_headsign'];


    $trip_short_name =
        $pattern['trip_short_name'];


    $direction_id =
        $pattern['direction_id'] !== null
            ? (int) $pattern['direction_id']
            : null;


    $shape_id =
        !empty($pattern['shape_id'])
            ? $pattern['shape_id']
            : null;



    /*
    |--------------------------------------------------------------------------
    | 2. BUSCA AS TRIPS QUE JÁ EXISTEM
    |--------------------------------------------------------------------------
    */

    $sqlExistentes = "
        SELECT
            t.trip_id,
            td.departure_time,
            t.wheelchair_accessible

        FROM trips t

        INNER JOIN trip_departures td
            ON td.trip_id = t.trip_id

        WHERE t.pattern_id = ?
          AND t.service_id = ?
    ";


    $stmtExistentes =
        $conexao->prepare(
            $sqlExistentes
        );


    $stmtExistentes->bind_param(
        "is",
        $pattern_id,
        $service_id
    );


    $stmtExistentes->execute();


    $resultExistentes =
        $stmtExistentes->get_result();


    /*
     * Guarda todos os trip_id existentes.
     *
     * No final, os que não forem encontrados
     * no payload serão excluídos.
     */

    $tripsExistentes = [];


    /*
     * Também criamos um índice pelo horário.
     *
     * Serve como segurança caso alguma linha
     * antiga da interface não envie trip_id.
     */

    $tripPorHorario = [];


    while (
        $row =
        $resultExistentes->fetch_assoc()
    ) {

        $id =
            (int) $row['trip_id'];


        $hora =
            $row['departure_time'];


        $tripsExistentes[$id] = true;

        $tripPorHorario[$hora] = $id;
    }



    /*
    |--------------------------------------------------------------------------
    | 3. PREPARA UPDATE DA TRIP
    |--------------------------------------------------------------------------
    */

    $sqlUpdateTrip = "
        UPDATE trips

        SET
            route_id = ?,
            service_id = ?,
            trip_headsign = ?,
            trip_short_name = ?,
            direction_id = ?,
            shape_id = ?,
            wheelchair_accessible = ?

        WHERE trip_id = ?
          AND pattern_id = ?
    ";


    $stmtUpdateTrip =
        $conexao->prepare(
            $sqlUpdateTrip
        );



    /*
    |--------------------------------------------------------------------------
    | 4. PREPARA UPDATE DO HORÁRIO
    |--------------------------------------------------------------------------
    */

    $sqlUpdateDeparture = "
        UPDATE trip_departures

        SET departure_time = ?

        WHERE trip_id = ?
    ";


    $stmtUpdateDeparture =
        $conexao->prepare(
            $sqlUpdateDeparture
        );



    /*
    |--------------------------------------------------------------------------
    | 5. PREPARA INSERT EM TRIPS
    |--------------------------------------------------------------------------
    */

    $sqlInsertTrip = "
        INSERT INTO trips
        (
            pattern_id,
            route_id,
            service_id,
            trip_headsign,
            trip_short_name,
            direction_id,
            shape_id,
            wheelchair_accessible
        )

        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";


    $stmtInsertTrip =
        $conexao->prepare(
            $sqlInsertTrip
        );



    /*
    |--------------------------------------------------------------------------
    | 6. PREPARA INSERT DO HORÁRIO
    |--------------------------------------------------------------------------
    */

    $sqlInsertDeparture = "
        INSERT INTO trip_departures
        (
            trip_id,
            departure_time
        )

        VALUES
        (
            ?, ?
        )
    ";


    $stmtInsertDeparture =
        $conexao->prepare(
            $sqlInsertDeparture
        );



    /*
    |--------------------------------------------------------------------------
    | 7. CONTROLES
    |--------------------------------------------------------------------------
    */

    $tripsRecebidas = [];

    $horariosRecebidos = [];

    $criadas = 0;
    $atualizadas = 0;
    $excluidas = 0;



    /*
    |--------------------------------------------------------------------------
    | 8. PROCESSA OS HORÁRIOS RECEBIDOS
    |--------------------------------------------------------------------------
    */

    foreach (
        $data as $item
    ) {


        /*
        |--------------------------------------------------------------------------
        | VALIDA PATTERN / SERVIÇO
        |--------------------------------------------------------------------------
        */

        $itemPatternId =
            isset($item['pattern_id'])
                ? (int) $item['pattern_id']
                : 0;


        $itemServiceId =
            isset($item['service_id'])
                ? trim($item['service_id'])
                : '';


        if (
            $itemPatternId !== $pattern_id ||
            $itemServiceId !== $service_id
        ) {

            throw new Exception(
                "Os horários enviados pertencem a patterns ou serviços diferentes."
            );
        }



        /*
        |--------------------------------------------------------------------------
        | HORÁRIO
        |--------------------------------------------------------------------------
        */

        $departure_time =
            isset($item['departure_time'])
                ? trim(
                    $item['departure_time']
                )
                : '';


        if (
            $departure_time === ''
        ) {

            throw new Exception(
                "Horário de partida inválido."
            );
        }


        /*
         * Normaliza HH:MM para HH:MM:SS
         */

        if (
            preg_match(
                '/^\d{1,3}:\d{2}$/',
                $departure_time
            )
        ) {

            $departure_time .= ":00";
        }


        if (
            !preg_match(
                '/^\d{1,3}:[0-5]\d:[0-5]\d$/',
                $departure_time
            )
        ) {

            throw new Exception(
                "Formato de horário inválido: "
                . $departure_time
            );
        }


        /*
         * Evita duplicidade no mesmo envio.
         */

        if (
            isset(
                $horariosRecebidos[
                    $departure_time
                ]
            )
        ) {

            throw new Exception(
                "Horário duplicado: "
                . $departure_time
            );
        }


        $horariosRecebidos[
            $departure_time
        ] = true;



        /*
        |--------------------------------------------------------------------------
        | WHEELCHAIR_ACCESSIBLE
        |--------------------------------------------------------------------------
        */

        $wheelchair_accessible =
            isset(
                $item[
                    'wheelchair_accessible'
                ]
            )
                ? (int) $item[
                    'wheelchair_accessible'
                ]
                : 0;


        if (
            !in_array(
                $wheelchair_accessible,
                [0, 1, 2],
                true
            )
        ) {

            $wheelchair_accessible = 0;
        }



        /*
        |--------------------------------------------------------------------------
        | TRIP_ID RECEBIDO
        |--------------------------------------------------------------------------
        */

        $trip_id =
            isset($item['trip_id'])
                ? (int) $item['trip_id']
                : 0;



        /*
        |--------------------------------------------------------------------------
        | FALLBACK PELO HORÁRIO
        |--------------------------------------------------------------------------
        |
        | Se a tela não enviou trip_id mas o horário
        | já existe, preservamos o trip_id existente.
        |
        */

        if (
            $trip_id <= 0 &&
            isset(
                $tripPorHorario[
                    $departure_time
                ]
            )
        ) {

            $trip_id =
                (int) $tripPorHorario[
                    $departure_time
                ];
        }



        /*
        |--------------------------------------------------------------------------
        | TRIP JÁ EXISTE
        |--------------------------------------------------------------------------
        */

        if (
            $trip_id > 0 &&
            isset(
                $tripsExistentes[
                    $trip_id
                ]
            )
        ) {


            /*
             * Atualiza os dados derivados
             * do pattern.
             */

            $stmtUpdateTrip->bind_param(
                "isssisiii",
                $route_id,
                $service_id,
                $trip_headsign,
                $trip_short_name,
                $direction_id,
                $shape_id,
                $wheelchair_accessible,
                $trip_id,
                $pattern_id
            );


            $stmtUpdateTrip->execute();



            /*
             * Atualiza o horário.
             *
             * Hoje sua tela não altera horário,
             * mas deixamos preparado.
             */

            $stmtUpdateDeparture->bind_param(
                "si",
                $departure_time,
                $trip_id
            );


            $stmtUpdateDeparture->execute();


            $tripsRecebidas[
                $trip_id
            ] = true;


            $atualizadas++;


            continue;
        }



        /*
        |--------------------------------------------------------------------------
        | NOVO HORÁRIO = NOVA TRIP
        |--------------------------------------------------------------------------
        */

        $stmtInsertTrip->bind_param(
            "iisssisi",
            $pattern_id,
            $route_id,
            $service_id,
            $trip_headsign,
            $trip_short_name,
            $direction_id,
            $shape_id,
            $wheelchair_accessible
        );


        $stmtInsertTrip->execute();


        $novoTripId =
            mysqli_insert_id(
                $conexao
            );


        if (
            $novoTripId <= 0
        ) {

            throw new Exception(
                "Não foi possível gerar a Trip."
            );
        }



        /*
         * Cria o horário.
         */

        $stmtInsertDeparture->bind_param(
            "is",
            $novoTripId,
            $departure_time
        );


        $stmtInsertDeparture->execute();


        $tripsRecebidas[
            $novoTripId
        ] = true;


        $criadas++;
    }



    /*
    |--------------------------------------------------------------------------
    | 9. EXCLUI SOMENTE AS TRIPS QUE SAÍRAM DA LISTA
    |--------------------------------------------------------------------------
    */

    $stmtDeleteTrip =
        $conexao->prepare("
            DELETE FROM trips
            WHERE trip_id = ?
              AND pattern_id = ?
              AND service_id = ?
        ");


    foreach (
        $tripsExistentes as
        $tripExistenteId => $_
    ) {

        if (
            isset(
                $tripsRecebidas[
                    $tripExistenteId
                ]
            )
        ) {

            continue;
        }


        $stmtDeleteTrip->bind_param(
            "iis",
            $tripExistenteId,
            $pattern_id,
            $service_id
        );


        $stmtDeleteTrip->execute();


        if (
            $stmtDeleteTrip->affected_rows > 0
        ) {

            $excluidas++;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 10. GERA STOP_TIMES AUTOMATICAMENTE
    |--------------------------------------------------------------------------
    */

    $totalStopTimes =
        gerarStopTimes(
            $conexao,
            $pattern_id,
            $service_id
        );


    /*
    |--------------------------------------------------------------------------
    | 11. COMMIT
    |--------------------------------------------------------------------------
    */

        mysqli_commit(
        $conexao
    );


    echo json_encode([

        "status" =>
            "ok",

        "message" =>
            "Horários salvos e stop_times gerados com sucesso.",

        "criadas" =>
            $criadas,

        "atualizadas" =>
            $atualizadas,

        "excluidas" =>
            $excluidas,

        "stop_times_gerados" =>
            $totalStopTimes

    ]);


    }
    catch (
        Throwable $e
    ) {

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