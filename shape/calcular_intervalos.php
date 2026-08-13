<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


/*
|--------------------------------------------------------------------------
| FUNÇÕES
|--------------------------------------------------------------------------
*/


function responderErro(
    string $mensagem,
    int $httpCode = 400
): void {

    http_response_code($httpCode);

    echo json_encode(
        [
            "status" => "erro",
            "message" => $mensagem
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DISTÂNCIA ENTRE DUAS COORDENADAS - HAVERSINE
|--------------------------------------------------------------------------
|
| Retorna metros.
|
*/

function distanciaHaversine(
    float $lat1,
    float $lon1,
    float $lat2,
    float $lon2
): float {

    $raioTerra = 6371000.0;

    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);

    $deltaLat =
        deg2rad($lat2 - $lat1);

    $deltaLon =
        deg2rad($lon2 - $lon1);


    $a =
        sin($deltaLat / 2) ** 2
        +
        cos($lat1Rad)
        *
        cos($lat2Rad)
        *
        sin($deltaLon / 2) ** 2;


    $c =
        2
        *
        atan2(
            sqrt($a),
            sqrt(1 - $a)
        );


    return $raioTerra * $c;
}


/*
|--------------------------------------------------------------------------
| PROJETA UMA PARADA EM UM SEGMENTO DO SHAPE
|--------------------------------------------------------------------------
|
| Para descobrir em que posição do segmento a parada está.
|
| t = 0 -> início do segmento
| t = 1 -> fim do segmento
|
*/

function projetarNoSegmento(
    float $stopLat,
    float $stopLon,
    float $lat1,
    float $lon1,
    float $lat2,
    float $lon2
): array {

    /*
     * Conversão local aproximada de graus para metros.
     *
     * É suficiente para projeção em pequenos segmentos
     * de um shape urbano.
     */

    $latReferencia =
        deg2rad(
            ($stopLat + $lat1 + $lat2) / 3
        );


    $metrosPorGrauLat =
        111132.92;

    $metrosPorGrauLon =
        111412.84
        *
        cos($latReferencia);


    /*
     * Stop como origem (0,0).
     */

    $x1 =
        ($lon1 - $stopLon)
        *
        $metrosPorGrauLon;

    $y1 =
        ($lat1 - $stopLat)
        *
        $metrosPorGrauLat;


    $x2 =
        ($lon2 - $stopLon)
        *
        $metrosPorGrauLon;

    $y2 =
        ($lat2 - $stopLat)
        *
        $metrosPorGrauLat;


    $dx = $x2 - $x1;
    $dy = $y2 - $y1;


    $comprimentoQuadrado =
        ($dx * $dx)
        +
        ($dy * $dy);


    if ($comprimentoQuadrado <= 0.000001) {

        $t = 0.0;

    } else {

        /*
         * Projeção do ponto (0,0)
         * sobre A -> B.
         */

        $t =
            -(
                ($x1 * $dx)
                +
                ($y1 * $dy)
            )
            /
            $comprimentoQuadrado;


        $t =
            max(
                0.0,
                min(1.0, $t)
            );
    }


    $projX =
        $x1 + ($t * $dx);

    $projY =
        $y1 + ($t * $dy);


    $distanciaAoShape =
        sqrt(
            ($projX * $projX)
            +
            ($projY * $projY)
        );


    return [
        "t" => $t,
        "distancia_ao_shape" =>
            $distanciaAoShape
    ];
}


/*
|--------------------------------------------------------------------------
| LOCALIZA A PARADA AO LONGO DO SHAPE
|--------------------------------------------------------------------------
|
| $distanciaMinimaPermitida permite respeitar a ordem das paradas.
| Assim, em trajetos que passam duas vezes perto do mesmo local,
| evitamos voltar para um trecho anterior do shape.
|
*/

function localizarParadaNoShape(
    array $shape,
    array $acumulado,
    float $stopLat,
    float $stopLon,
    float $distanciaMinimaPermitida
): array {

    $melhor = null;

    $total =
        count($shape);


    for (
        $i = 0;
        $i < $total - 1;
        $i++
    ) {

        $inicio =
            $shape[$i];

        $fim =
            $shape[$i + 1];


        $projecao =
            projetarNoSegmento(
                $stopLat,
                $stopLon,
                $inicio["lat"],
                $inicio["lon"],
                $fim["lat"],
                $fim["lon"]
            );


        $comprimentoSegmento =
            $acumulado[$i + 1]
            -
            $acumulado[$i];


        $distanciaNoShape =
            $acumulado[$i]
            +
            (
                $projecao["t"]
                *
                $comprimentoSegmento
            );


        /*
         * Mantém a progressão no sentido do shape.
         *
         * Tolerância de 10 m permite paradas muito próximas.
         */

        if (
            $distanciaNoShape
            <
            ($distanciaMinimaPermitida - 10.0)
        ) {
            continue;
        }


        if (
            $melhor === null
            ||
            $projecao["distancia_ao_shape"]
            <
            $melhor["distancia_ao_shape"]
        ) {

            $melhor = [

                "distancia_no_shape" =>
                    $distanciaNoShape,

                "distancia_ao_shape" =>
                    $projecao[
                        "distancia_ao_shape"
                    ],

                "segmento" =>
                    $i,

                "t" =>
                    $projecao["t"]

            ];
        }
    }


    return $melhor ?? [];
}


/*
|--------------------------------------------------------------------------
| FORMATAR MINUTOS EM HH:MM
|--------------------------------------------------------------------------
*/

function formatarIntervalo(
    int $minutos
): string {

    $minutos =
        max(0, $minutos);

    $horas =
        intdiv(
            $minutos,
            60
        );

    $restante =
        $minutos % 60;


    return sprintf(
        "%02d:%02d",
        $horas,
        $restante
    );
}


/*
|--------------------------------------------------------------------------
| RECEBER JSON
|--------------------------------------------------------------------------
*/

$data =
    json_decode(
        file_get_contents(
            "php://input"
        ),
        true
    );


if (!is_array($data)) {

    responderErro(
        "Dados inválidos."
    );
}


$patternId =
    isset($data["pattern_id"])
        ? (int) $data["pattern_id"]
        : 0;


$velocidadeMedia =
    isset($data["velocidade_media"])
        ? (float) $data["velocidade_media"]
        : 0.0;


$paradas =
    $data["paradas"] ?? [];


/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if ($patternId <= 0) {

    responderErro(
        "Padrão de viagem inválido."
    );
}


if (
    $velocidadeMedia <= 0
    ||
    $velocidadeMedia > 120
) {

    responderErro(
        "Velocidade média inválida."
    );
}


if (
    !is_array($paradas)
    ||
    count($paradas) < 2
) {

    responderErro(
        "Informe pelo menos duas paradas."
    );
}


/*
|--------------------------------------------------------------------------
| BUSCAR SHAPE DO PATTERN
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $conexao->prepare("
            SELECT shape_id

            FROM trip_patterns

            WHERE pattern_id = ?

            LIMIT 1
        ");


    $stmt->bind_param(
        "i",
        $patternId
    );


    $stmt->execute();


    $pattern =
        $stmt
            ->get_result()
            ->fetch_assoc();


    if (
        !$pattern
        ||
        empty($pattern["shape_id"])
    ) {

        responderErro(
            "O padrão selecionado não possui trajeto salvo."
        );
    }


    $shapeId =
        $pattern["shape_id"];


    /*
    |--------------------------------------------------------------------------
    | BUSCAR PONTOS DO SHAPE
    |--------------------------------------------------------------------------
    */

    $stmt =
        $conexao->prepare("
            SELECT
                shape_pt_lat,
                shape_pt_lon,
                shape_pt_sequence

            FROM shapes

            WHERE shape_id = ?

            ORDER BY
                shape_pt_sequence ASC
        ");


    $stmt->bind_param(
        "s",
        $shapeId
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    $shape = [];


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $shape[] = [

            "lat" =>
                (float)
                $row["shape_pt_lat"],

            "lon" =>
                (float)
                $row["shape_pt_lon"]

        ];
    }


    if (count($shape) < 2) {

        responderErro(
            "O trajeto não possui pontos suficientes para o cálculo."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DISTÂNCIA ACUMULADA DO SHAPE
    |--------------------------------------------------------------------------
    */

    $acumulado = [0.0];


    for (
        $i = 1;
        $i < count($shape);
        $i++
    ) {

        $anterior =
            $shape[$i - 1];

        $atual =
            $shape[$i];


        $distancia =
            distanciaHaversine(
                $anterior["lat"],
                $anterior["lon"],
                $atual["lat"],
                $atual["lon"]
            );


        $acumulado[$i] =
            $acumulado[$i - 1]
            +
            $distancia;
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARA CONSULTA DAS PARADAS
    |--------------------------------------------------------------------------
    */

    $stmtStop =
        $conexao->prepare("
            SELECT
                stop_id,
                stop_lat,
                stop_lon

            FROM stops

            WHERE stop_id = ?

            LIMIT 1
        ");


    $resultadoParadas = [];

    $distanciaAnterior =
        0.0;

    $distanciaPrimeira =
        null;


    foreach (
        $paradas as $index => $item
    ) {

        $stopId =
            isset($item["stop_id"])
                ? (int) $item["stop_id"]
                : 0;


        $seq =
            isset($item["seq"])
                ? (int) $item["seq"]
                : ($index + 1);


        if ($stopId <= 0) {

            responderErro(
                "Stop inválido na sequência " .
                ($index + 1) .
                "."
            );
        }


        $stmtStop->bind_param(
            "i",
            $stopId
        );


        $stmtStop->execute();


        $stop =
            $stmtStop
                ->get_result()
                ->fetch_assoc();


        if (!$stop) {

            responderErro(
                "Parada " .
                $stopId .
                " não encontrada."
            );
        }


        $posicao =
            localizarParadaNoShape(
                $shape,
                $acumulado,
                (float) $stop["stop_lat"],
                (float) $stop["stop_lon"],
                $distanciaAnterior
            );


        if (!$posicao) {

            responderErro(
                "Não foi possível localizar a parada " .
                $stopId .
                " no trajeto respeitando a sequência."
            );
        }


        /*
         * Opcionalmente rejeitamos uma parada
         * extremamente distante do shape.
         */

        if (
            $posicao["distancia_ao_shape"]
            > 1000
        ) {

            responderErro(
                "A parada " .
                $stopId .
                " está a mais de 1 km do trajeto."
            );
        }


        $distanciaAtual =
            (float)
            $posicao["distancia_no_shape"];


        if ($distanciaPrimeira === null) {

            $distanciaPrimeira =
                $distanciaAtual;
        }


        $distanciaDesdePrimeira =
            max(
                0.0,
                $distanciaAtual
                -
                $distanciaPrimeira
            );


        /*
         * tempo(h) = distância(km) / velocidade(km/h)
         *
         * minutos = tempo(h) * 60
         */

        $minutos =
            (int)
            round(
                (
                    ($distanciaDesdePrimeira / 1000)
                    /
                    $velocidadeMedia
                )
                *
                60
            );


        $resultadoParadas[] = [

            "stop_id" =>
                $stopId,

            "seq" =>
                $seq,

            "intervalo" =>
                formatarIntervalo(
                    $minutos
                ),

            "minutos" =>
                $minutos,

            "distancia_metros" =>
                round(
                    $distanciaDesdePrimeira,
                    1
                ),

            "distancia_ao_shape_metros" =>
                round(
                    $posicao[
                        "distancia_ao_shape"
                    ],
                    1
                )

        ];


        $distanciaAnterior =
            $distanciaAtual;
    }


    $ultima =
        $resultadoParadas[
            count($resultadoParadas) - 1
        ];


    echo json_encode(
        [
            "status" =>
                "ok",

            "pattern_id" =>
                $patternId,

            "shape_id" =>
                $shapeId,

            "velocidade_media" =>
                $velocidadeMedia,

            "distancia_total_km" =>
                round(
                    $ultima[
                        "distancia_metros"
                    ] / 1000,
                    2
                ),

            "paradas" =>
                $resultadoParadas

        ],
        JSON_UNESCAPED_UNICODE
    );


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode(
        [
            "status" => "erro",

            "message" =>
                "Erro ao calcular intervalos.",

            "detail" =>
                $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
}