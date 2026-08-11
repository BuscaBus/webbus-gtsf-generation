<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

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
| DADOS PRINCIPAIS
|--------------------------------------------------------------------------
|
| Todos os horários enviados pela tela pertencem ao mesmo:
|
| pattern_id
| service_id
|
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
        "message" => "Pattern ou serviço inválido."
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
    |
    | Esses dados serão copiados para cada nova trip.
    |
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
    | 2. REMOVE AS TRIPS ANTIGAS DESSE PATTERN + SERVIÇO
    |--------------------------------------------------------------------------
    |
    | Como trips -> trip_departures possui ON DELETE CASCADE,
    | ao excluir as trips os horários correspondentes também são removidos.
    |
    */

    $sqlDeleteTrips = "
        DELETE FROM trips

        WHERE pattern_id = ?
        AND service_id = ?
    ";


    $stmtDeleteTrips =
        $conexao->prepare(
            $sqlDeleteTrips
        );


    $stmtDeleteTrips->bind_param(
        "is",
        $pattern_id,
        $service_id
    );


    $stmtDeleteTrips->execute();



    /*
    |--------------------------------------------------------------------------
    | 3. PREPARA INSERT EM TRIPS
    |--------------------------------------------------------------------------
    */

    $sqlTrip = "
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


    $stmtTrip =
        $conexao->prepare(
            $sqlTrip
        );



    /*
    |--------------------------------------------------------------------------
    | 4. PREPARA INSERT EM TRIP_DEPARTURES
    |--------------------------------------------------------------------------
    */

    $sqlDeparture = "
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


    $stmtDeparture =
        $conexao->prepare(
            $sqlDeparture
        );



    /*
    |--------------------------------------------------------------------------
    | 5. CRIA UMA TRIP PARA CADA HORÁRIO
    |--------------------------------------------------------------------------
    */

    $tripsCriadas = 0;


    foreach ($data as $item) {


        /*
        |--------------------------------------------------------------------------
        | VALIDA O PATTERN E SERVIÇO RECEBIDOS
        |--------------------------------------------------------------------------
        |
        | Evita que um único payload misture horários de outros patterns.
        |
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
                ? trim($item['departure_time'])
                : '';


        if ($departure_time === '') {

            throw new Exception(
                "Horário de partida inválido."
            );
        }



        /*
        |--------------------------------------------------------------------------
        | WHEELCHAIR_ACCESSIBLE
        |--------------------------------------------------------------------------
        |
        | GTFS:
        |
        | 0 = sem informação
        | 1 = acessível
        | 2 = não acessível
        |
        */

        $wheelchair_accessible =
            isset($item['wheelchair_accessible'])
                ? (int) $item['wheelchair_accessible']
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
        | CRIA A TRIP
        |--------------------------------------------------------------------------
        */

        $stmtTrip->bind_param(
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


        $stmtTrip->execute();



        /*
        |--------------------------------------------------------------------------
        | PEGA O TRIP_ID GERADO
        |--------------------------------------------------------------------------
        */

        $trip_id =
            mysqli_insert_id(
                $conexao
            );


        if ($trip_id <= 0) {

            throw new Exception(
                "Não foi possível gerar a Trip."
            );
        }



        /*
        |--------------------------------------------------------------------------
        | CRIA O HORÁRIO DA TRIP
        |--------------------------------------------------------------------------
        */

        $stmtDeparture->bind_param(
            "is",
            $trip_id,
            $departure_time
        );


        $stmtDeparture->execute();


        $tripsCriadas++;
    }



    /*
    |--------------------------------------------------------------------------
    | FINALIZA TRANSAÇÃO
    |--------------------------------------------------------------------------
    */

    mysqli_commit(
        $conexao
    );


    echo json_encode([
        "status" => "ok",
        "message" =>
            $tripsCriadas
            . " horário(s) salvo(s) com sucesso.",
        "trips_criadas" =>
            $tripsCriadas
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