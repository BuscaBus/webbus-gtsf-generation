<?php

header("Content-Type: application/json; charset=utf-8");


/*
|--------------------------------------------------------------------------
| CONFIGURAÇÕES
|--------------------------------------------------------------------------
*/

define(
    "CACERT_PATH",
    "C:\\wamp64\\bin\\php\\cacert-2025-02-25.pem"
);


/*
|--------------------------------------------------------------------------
| VALIDAR CERTIFICADO
|--------------------------------------------------------------------------
*/

if (!file_exists(CACERT_PATH)) {

    echo json_encode([
        "status" => "erro",
        "mensagem" =>
            "Certificado SSL não encontrado em: " .
            CACERT_PATH
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| FUNÇÃO CURL
|--------------------------------------------------------------------------
*/

function consultarUrl($url)
{
    $ch = curl_init();

    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL =>
                $url,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                10,

            CURLOPT_TIMEOUT =>
                30,

            CURLOPT_FOLLOWLOCATION =>
                true,

            CURLOPT_CAINFO =>
                CACERT_PATH,

            CURLOPT_SSL_VERIFYPEER =>
                true,

            CURLOPT_SSL_VERIFYHOST =>
                2,

            CURLOPT_HTTPHEADER => [
                "Accept: application/json"
            ],

            CURLOPT_USERAGENT =>
                "BuscaBus-GTFS/1.0"
        ]
    );


    $resposta =
        curl_exec($ch);


    if ($resposta === false) {

        $erro =
            curl_error($ch);

        curl_close($ch);

        throw new Exception(
            "Erro CURL: " .
            $erro
        );
    }


    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    $dados =
        json_decode(
            $resposta,
            true
        );


    if ($httpCode !== 200) {

        $codigo =
            $dados["code"]
            ?? "desconhecido";

        $mensagem =
            $dados["message"]
            ?? $resposta;


        throw new Exception(
            "OSRM HTTP " .
            $httpCode .
            " - " .
            $codigo .
            ": " .
            $mensagem
        );
    }


    if (
        !isset($dados["code"]) ||
        $dados["code"] !== "Ok"
    ) {

        throw new Exception(
            "Resposta inválida do OSRM."
        );
    }


    return $dados;
}


/*
|--------------------------------------------------------------------------
| AJUSTAR PONTO PARA VIA MAIS PRÓXIMA
|--------------------------------------------------------------------------
|
| Nearest retorna:
|
| location = [
|     longitude,
|     latitude
| ]
|--------------------------------------------------------------------------
*/

function ajustarPontoVia($lon, $lat)
{

    $url =
        "https://router.project-osrm.org/nearest/v1/driving/" .
        $lon .
        "," .
        $lat .
        "?number=1";


    $dados =
        consultarUrl(
            $url
        );


    if (
        !isset(
            $dados["waypoints"][0]["location"]
        )
    ) {

        throw new Exception(
            "Não foi encontrada uma via próxima ao ponto."
        );
    }


    $location =
        $dados["waypoints"][0]["location"];


    return [
        "lon" =>
            (float) $location[0],

        "lat" =>
            (float) $location[1],

        "distancia" =>
            isset(
                $dados["waypoints"][0]["distance"]
            )
                ? (float)
                    $dados["waypoints"][0]["distance"]
                : null
    ];
}


/*
|--------------------------------------------------------------------------
| ROTEAR ENTRE DOIS PONTOS
|--------------------------------------------------------------------------
*/

function calcularRota(
    $inicio,
    $fim
) {

    $coords =
        $inicio["lon"] .
        "," .
        $inicio["lat"] .
        ";" .
        $fim["lon"] .
        "," .
        $fim["lat"];


    $url =
        "https://router.project-osrm.org/route/v1/driving/" .
        $coords .
        "?geometries=geojson" .
        "&overview=full" .
        "&steps=false" .
        "&alternatives=false";


    $dados =
        consultarUrl(
            $url
        );


    if (
        !isset(
            $dados["routes"][0]["geometry"]["coordinates"]
        )
    ) {

        throw new Exception(
            "Não foi possível calcular a rota entre dois pontos."
        );
    }


    return
        $dados["routes"][0]["geometry"]["coordinates"];
}


/*
|--------------------------------------------------------------------------
| RECEBER JSON
|--------------------------------------------------------------------------
*/

$data =
    json_decode(
        file_get_contents("php://input"),
        true
    );


if (
    !$data ||
    !isset($data["coords"]) ||
    !is_array($data["coords"])
) {

    echo json_encode([
        "status" => "erro",
        "mensagem" =>
            "Nenhuma coordenada recebida."
    ]);

    exit;
}


$coordsOriginais =
    $data["coords"];


/*
|--------------------------------------------------------------------------
| VALIDAR QUANTIDADE
|--------------------------------------------------------------------------
*/

if (count($coordsOriginais) < 2) {

    echo json_encode([
        "status" => "erro",
        "mensagem" =>
            "O traçado precisa possuir pelo menos dois pontos."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR COORDENADAS
|--------------------------------------------------------------------------
|
| GeoJSON recebido:
|
| [longitude, latitude]
|--------------------------------------------------------------------------
*/

$coordenadas = [];


foreach (
    $coordsOriginais
    as $coord
) {

    if (
        !is_array($coord) ||
        count($coord) < 2
    ) {
        continue;
    }


    $lon =
        filter_var(
            $coord[0],
            FILTER_VALIDATE_FLOAT
        );


    $lat =
        filter_var(
            $coord[1],
            FILTER_VALIDATE_FLOAT
        );


    if (
        $lon === false ||
        $lat === false
    ) {
        continue;
    }


    if (
        $lat < -90 ||
        $lat > 90 ||
        $lon < -180 ||
        $lon > 180
    ) {
        continue;
    }


    /*
     * Evitar duplicados
     */
    if (!empty($coordenadas)) {

        $ultimo =
            $coordenadas[
                count($coordenadas) - 1
            ];


        if (
            abs(
                $ultimo["lat"] -
                $lat
            ) < 0.0000001
            &&
            abs(
                $ultimo["lon"] -
                $lon
            ) < 0.0000001
        ) {

            continue;
        }
    }


    $coordenadas[] = [
        "lon" => (float) $lon,
        "lat" => (float) $lat
    ];
}


if (count($coordenadas) < 2) {

    echo json_encode([
        "status" => "erro",
        "mensagem" =>
            "Não existem coordenadas válidas suficientes."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| REDUZIR PONTOS DE REFERÊNCIA
|--------------------------------------------------------------------------
|
| Não precisamos usar todos os pontos do shape
| para calcular a rota.
|
| Selecionamos aproximadamente até 25 pontos.
|--------------------------------------------------------------------------
*/

$maxPontosReferencia = 25;


$pontosReferencia = [];


$totalOriginais =
    count($coordenadas);


if (
    $totalOriginais <=
    $maxPontosReferencia
) {

    $pontosReferencia =
        $coordenadas;

} else {

    $ultimoIndice =
        $totalOriginais - 1;


    $intervalo =
        $ultimoIndice /
        ($maxPontosReferencia - 1);


    for (
        $i = 0;
        $i < $maxPontosReferencia;
        $i++
    ) {

        $indice =
            (int) round(
                $i * $intervalo
            );


        /*
         * Evitar repetir índice
         */
        if (
            !empty($pontosReferencia)
        ) {

            $ultimoPonto =
                $pontosReferencia[
                    count($pontosReferencia) - 1
                ];


            if (
                $ultimoPonto["lon"] ===
                    $coordenadas[$indice]["lon"]
                &&
                $ultimoPonto["lat"] ===
                    $coordenadas[$indice]["lat"]
            ) {

                continue;
            }
        }


        $pontosReferencia[] =
            $coordenadas[$indice];
    }
}


/*
|--------------------------------------------------------------------------
| AJUSTAR PONTOS À RUA
|--------------------------------------------------------------------------
*/

$pontosAjustados = [];

$distanciasSnap = [];


try {

    foreach (
        $pontosReferencia
        as $indice => $ponto
    ) {

        $ajustado =
            ajustarPontoVia(
                $ponto["lon"],
                $ponto["lat"]
            );


        $pontosAjustados[] =
            $ajustado;


        if (
            $ajustado["distancia"] !== null
        ) {

            $distanciasSnap[] =
                $ajustado["distancia"];
        }
    }


} catch (Exception $e) {

    echo json_encode(
        [
            "status" =>
                "erro",

            "mensagem" =>
                "Erro ao ajustar ponto " .
                (($indice ?? 0) + 1) .
                ": " .
                $e->getMessage()
        ],

        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CALCULAR ROTA ENTRE OS PONTOS
|--------------------------------------------------------------------------
*/

$coordsLeaflet = [];

$trechosProcessados = 0;


try {

    for (
        $i = 0;
        $i < count($pontosAjustados) - 1;
        $i++
    ) {

        $inicio =
            $pontosAjustados[$i];


        $fim =
            $pontosAjustados[
                $i + 1
            ];


        $geometria =
            calcularRota(
                $inicio,
                $fim
            );


        $trechosProcessados++;


        foreach (
            $geometria
            as $coord
        ) {

            if (
                !is_array($coord) ||
                count($coord) < 2
            ) {
                continue;
            }


            /*
             * OSRM GeoJSON:
             *
             * [longitude, latitude]
             */
            $lon =
                (float) $coord[0];

            $lat =
                (float) $coord[1];


            /*
             * Leaflet:
             *
             * [latitude, longitude]
             */
            $novoPonto = [
                $lat,
                $lon
            ];


            /*
             * Remover duplicado
             * na junção dos trechos
             */
            if (
                !empty($coordsLeaflet)
            ) {

                $ultimo =
                    $coordsLeaflet[
                        count($coordsLeaflet) - 1
                    ];


                if (
                    abs(
                        $ultimo[0] -
                        $novoPonto[0]
                    ) < 0.0000001
                    &&
                    abs(
                        $ultimo[1] -
                        $novoPonto[1]
                    ) < 0.0000001
                ) {

                    continue;
                }
            }


            $coordsLeaflet[] =
                $novoPonto;
        }
    }


} catch (Exception $e) {

    echo json_encode(
        [
            "status" =>
                "erro",

            "mensagem" =>
                "Erro ao calcular trecho " .
                ($i + 1) .
                ": " .
                $e->getMessage()
        ],

        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR RESULTADO
|--------------------------------------------------------------------------
*/

if (count($coordsLeaflet) < 2) {

    echo json_encode([
        "status" => "erro",

        "mensagem" =>
            "Não foi possível gerar o trajeto ajustado."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CALCULAR DISTÂNCIA MÉDIA DE SNAP
|--------------------------------------------------------------------------
*/

$distanciaMedia = null;


if (
    count($distanciasSnap) > 0
) {

    $distanciaMedia =
        array_sum(
            $distanciasSnap
        )
        /
        count(
            $distanciasSnap
        );
}


/*
|--------------------------------------------------------------------------
| RETORNO FINAL
|--------------------------------------------------------------------------
*/

echo json_encode(
    [
        "status" =>
            "ok",

        "mensagem" =>
            "Traçado ajustado às vias.",

        /*
         * Pronto para:
         *
         * L.polyline(data.coords)
         */
        "coords" =>
            $coordsLeaflet,

        "pontos_originais" =>
            count($coordenadas),

        "pontos_referencia" =>
            count($pontosReferencia),

        "pontos_ajustados_via" =>
            count($pontosAjustados),

        "pontos_resultado" =>
            count($coordsLeaflet),

        "trechos_processados" =>
            $trechosProcessados,

        "distancia_media_snap" =>
            $distanciaMedia

    ],

    JSON_UNESCAPED_UNICODE
);