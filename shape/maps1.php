<?php
include("../connection.php");

// ===============================
// BUSCA HORÁRIOS DE TODAS AS VIAGENS (ponto inicial da viagem)
// ===============================
$viagem_horarios = [];
$sql_horarios = "
SELECT
    t.trip_id,
    sr.stop_code AS ponto_inicial,
    TIME_FORMAT(st.arrival_time, '%H:%i') AS arrival_time,
    r.route_short_name,
    t.trip_headsign,
    CASE
        WHEN t.service_id LIKE '%Segunda a Sexta%' THEN 'Segunda a Sexta'
        WHEN t.service_id LIKE '%Sábado%' THEN 'Sábado'
        WHEN t.service_id LIKE '%Domingo%' OR t.service_id LIKE '%Feriado%' THEN 'Domingos e Feriados'
        ELSE 'Outros'
    END AS servico
FROM trips t
INNER JOIN stop_routes sr ON sr.trip_id = t.trip_id
INNER JOIN stop_times st ON st.trip_id = t.trip_id AND sr.stop_sequence = 1
INNER JOIN routes r ON r.route_id = t.route_id
ORDER BY st.arrival_time
";

$res_horarios = mysqli_query($conexao, $sql_horarios);
$trip_horarios = []; // guarda os horários por trip_id
while ($row = mysqli_fetch_assoc($res_horarios)) {
    $trip_horarios[$row['trip_id']][$row['servico']][] = $row;
}

// ===============================
// BUSCA DOS PONTOS
// ===============================
$sql_pontos = "
SELECT 
    s.stop_code,
    s.stop_name,
    s.stop_lat,
    s.stop_lon,
    sr.trip_id
FROM stops s
LEFT JOIN stop_routes sr ON sr.stop_code = s.stop_code
WHERE s.stop_lat IS NOT NULL AND s.stop_lon IS NOT NULL
ORDER BY s.stop_name
";

$res_pontos = mysqli_query($conexao, $sql_pontos);

$pontos = [];
while ($row = mysqli_fetch_assoc($res_pontos)) {
    $code = $row['stop_code'];
    $lat = floatval($row['stop_lat']);
    $lon = floatval($row['stop_lon']);
    $trip_id = $row['trip_id'];

    if (!isset($pontos[$code])) {
        $pontos[$code] = [
            'stop_code' => $code,
            'stop_name' => $row['stop_name'],
            'latitude' => $lat,
            'longitude' => $lon,
            'servicos' => [],
            'total_horarios' => 0
        ];
    }

    if ($trip_id && isset($trip_horarios[$trip_id])) {
        foreach ($trip_horarios[$trip_id] as $servico => $horarios) {
            foreach ($horarios as $h) {
                // Inicializa o serviço no ponto se não existir
                if (!isset($pontos[$code]['servicos'][$servico])) {
                    $pontos[$code]['servicos'][$servico] = [
                        'servico' => $servico,
                        'horarios_array' => []
                    ];
                }
                // Adiciona cada horário ao array do serviço
                $pontos[$code]['servicos'][$servico]['horarios_array'][] = $h;
                $pontos[$code]['total_horarios']++;
            }
        }
    }
}

// Ordena os horários de cada serviço de forma ascendente, independente da viagem
foreach ($pontos as $code => &$ponto) {
    foreach ($ponto['servicos'] as $servico => &$s) {
        usort($s['horarios_array'], function ($a, $b) {
            return strcmp($a['arrival_time'], $b['arrival_time']);
        });
        // Concatena todos os horários em HTML
        $horarios_html = '';
        foreach ($s['horarios_array'] as $h) {
            $horarios_html .= '<li><span class="hora">' . $h['arrival_time'] . '</span> - ' . $h['route_short_name'] . ' - ' . $h['trip_headsign'] . '</li>';
        }
        $s['horarios'] = $horarios_html;
        unset($s['horarios_array']);
    }
}
unset($ponto);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Mapa de Pontos - WebBus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/shape.css?v=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        #div-map {
            width: 100%;
            height: 85vh;
        }

        .popup-container {
            max-height: 420px;
            overflow-y: auto;
        }

        .popup-title {
            font-weight: bold;
            margin-bottom: 6px;
        }

        .servico {
            margin-top: 6px;
        }

        .servico-title {
            font-weight: bold;
        }

        .horarios {
            list-style: none;
            padding-left: 0;
            margin-top: 4px;
        }

        .horarios li {
            font-size: 13px;
            line-height: 1.4;
        }

        .hora {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <section>
        <div id="div-map"></div>

        <script>
            var map = L.map('div-map', {
                center: [-27.595740, -48.568228],
                zoom: 13,
                maxZoom: 18
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var meuIcone = L.icon({
                iconUrl: '../img/icon-bus2.png',
                iconSize: [15, 15],
                iconAnchor: [8, 8],
                popupAnchor: [0, -32]
            });

            var pontos = Object.values(<?php echo json_encode($pontos, JSON_UNESCAPED_UNICODE); ?>);
            var zoomMinimo = 17;
            var layerMarcadores = L.layerGroup().addTo(map);

            function atualizarMarcadores() {
                layerMarcadores.clearLayers();
                if (map.getZoom() < zoomMinimo) return;

                pontos.forEach(function(ponto) {
                    let popupClass = ponto.total_horarios > 20 ? 'popup-container' : '';
                    let popup = `<div class="${popupClass}"><div class="popup-title">📍 ${ponto.stop_code} - ${ponto.stop_name}</div>`;

                    if (Object.keys(ponto.servicos).length === 0) {
                        popup += '<em>Sem horários cadastrados</em>';
                    } else {
                        for (let key in ponto.servicos) {
                            let s = ponto.servicos[key];
                            popup += `<div class="servico"><div class="servico-title">${s.servico}</div><ul class="horarios">${s.horarios}</ul></div>`;
                        }
                    }
                    popup += '</div>';

                    L.marker([ponto.latitude, ponto.longitude], {
                            icon: meuIcone
                        })
                        .addTo(layerMarcadores)
                        .bindPopup(popup, {
                            maxWidth: 360
                        });
                });
            }

            map.on('zoomend', atualizarMarcadores);
            map.on('moveend', atualizarMarcadores);
            atualizarMarcadores();
        </script>

        <p><button class="btn-reg-cad"><a href="../index.html" class="a-btn-canc">VOLTAR</a></button></p>
    </section>
</body>

</html>