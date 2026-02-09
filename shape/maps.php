<?php
include("../connection.php");

// =================== MARCADORES DO BANCO ===================
$sql = "SELECT 
            s.stop_code,
            s.stop_name,
            s.stop_lat AS latitude,
            s.stop_lon AS longitude,
            GROUP_CONCAT(
                DISTINCT CONCAT(r.route_short_name, ' - ', r.route_long_name)
                SEPARATOR '<br>'
            ) AS rotas
        FROM stops s
        LEFT JOIN stop_routes sr ON sr.stop_code = s.stop_code
        LEFT JOIN trips t ON sr.trip_id = t.trip_id
        LEFT JOIN routes r ON t.route_id = r.route_id
        WHERE s.stop_lat IS NOT NULL 
          AND s.stop_lon IS NOT NULL
        GROUP BY s.stop_code
        ORDER BY s.stop_name ASC";

$result = mysqli_query($conexao, $sql);

$marcadores = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['latitude']  = floatval($row['latitude']);
    $row['longitude'] = floatval($row['longitude']);
    $marcadores[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de pontos</title>

    <link rel="stylesheet" href="../css/shape.css?v=1.1">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
</head>

<body>
    <section>

        <form id="form-filtro">
            <label>Operadora: </label>
            <select name="operadora" id="selc-op" class="selc-op">
                <option value="">Selecione a operadora</option>
                <?php
                $sql_select = "SELECT agency_id, agency_name FROM agency ORDER BY agency_name ASC";
                $result_selec = mysqli_query($conexao, $sql_select);
                while ($dados = mysqli_fetch_array($result_selec)) {
                    echo "<option value='{$dados['agency_id']}'>{$dados['agency_name']}</option>";
                }
                ?>
            </select>

            <label>Linha: </label>
            <select name="linha" id="selc-linh" class="selc-linh">
                <option value="">Selecione a linha</option>
            </select>

            <label>Viagem: </label>
            <select name="viagem" id="selc-viag" class="selc-viag">
                <option value="">Selecione a viagem</option>
            </select>

            <button type="button" class="btn-selec">SELECIONAR</button>
        </form>

        <div id="div-map"></div>

        <script>
            const marcadoresExistentes = <?php echo json_encode($marcadores, JSON_UNESCAPED_UNICODE); ?>;

            // =================== MAPA ===================
            var map = L.map('div-map').setView([-27.595740, -48.568228], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // =================== ÍCONE DINÂMICO ===================
            function getIconSize(zoom) {
                const minSize = 10;
                const maxSize = 15;
                let size = zoom * 3;
                if (size < minSize) size = minSize;
                if (size > maxSize) size = maxSize;
                return [size, size];
            }

            function criarIcone(zoom) {
                const size = getIconSize(zoom);
                return L.icon({
                    iconUrl: '../img/icon-bus2.png',
                    iconSize: size,
                    iconAnchor: [size[0] / 2, size[1]],
                    popupAnchor: [0, -size[1]]
                });
            }

            // =================== MARCADORES EXISTENTES ===================
            var marcadoresBanco = L.layerGroup().addTo(map);
            var zoomMinimo = 17;

            function atualizarMarcadores() {
                marcadoresBanco.clearLayers();

                if (map.getZoom() >= zoomMinimo) {
                    var bounds = map.getBounds();

                    marcadoresExistentes.forEach(function(ponto) {
                        if (ponto.latitude && ponto.longitude) {
                            var latlng = L.latLng(ponto.latitude, ponto.longitude);
                            if (bounds.contains(latlng)) {
                                L.marker([ponto.latitude, ponto.longitude], {
                                        icon: criarIcone(map.getZoom())
                                    })
                                    .bindPopup(
                                        "<div class='popup-ponto'>" +
                                        "<b>Ponto:</b> " + ponto.stop_code + "<br>" + ponto.stop_name + (ponto.rotas ?
                                            "<br><br><b>Linhas:</b>" +
                                            "<div class='popup-linhas'>" + ponto.rotas + "</div>" :
                                            ""
                                        ) +
                                        "</div>"
                                    )

                                    .addTo(marcadoresBanco);
                            }
                        }
                    });
                }
            }

            map.on('zoomend', atualizarMarcadores);
            map.on('moveend', atualizarMarcadores);
            atualizarMarcadores();

            // =================== SHAPES ===================
            let shapeLayer = L.layerGroup().addTo(map);

            function carregarShapesDaLinha(route_id) {
                shapeLayer.clearLayers();

                fetch("get_shapes_linha.php?route_id=" + route_id)
                    .then(r => r.json())
                    .then(data => {
                        data.forEach(trip => {
                            if (trip.pontos.length > 0) {
                                let cor = (trip.direction_id === 'Ida') ? 'blue' : 'red';

                                let poly = L.polyline(trip.pontos, {
                                    color: cor,
                                    weight: 4
                                }).addTo(shapeLayer);

                                map.fitBounds(poly.getBounds());
                            }
                        });
                    });
            }

            // =================== SELECT OPERADORA ===================
            document.getElementById('selc-op').addEventListener('change', function() {
                let selc_linh = document.getElementById('selc-linh');
                let selc_viag = document.getElementById('selc-viag');

                selc_linh.innerHTML = "<option value=''>Selecione a linha</option>";
                selc_viag.innerHTML = "<option value=''>Selecione a viagem</option>";

                if (!this.value) return;

                fetch("get_linhas.php?agency_id=" + this.value)
                    .then(r => r.json())
                    .then(data => {
                        data.forEach(linha => {
                            selc_linh.innerHTML += `<option value="${linha.route_id}">${linha.linha_nome}</option>`;
                        });
                    });
            });

            // =================== SELECT LINHA ===================
            document.getElementById('selc-linh').addEventListener('change', function() {
                let route_id = this.value;
                let selc_viag = document.getElementById('selc-viag');

                selc_viag.innerHTML = "<option value=''>Selecione a viagem</option>";

                if (!route_id) return;

                fetch("get_viagens.php?route_id=" + route_id)
                    .then(r => r.json())
                    .then(data => {
                        data.forEach(v => {
                            selc_viag.innerHTML += `<option value="${v.trip_id}">${v.viagem_nome}</option>`;
                        });
                    });

                carregarShapesDaLinha(route_id);
            });

            // =================== BOTÃO SELECIONAR ===================
            document.querySelector(".btn-selec").addEventListener("click", function() {
                let trip_id = document.getElementById('selc-viag').value;

                if (!trip_id) {
                    alert("Selecione uma viagem!");
                    return;
                }

                shapeLayer.clearLayers();

                fetch("get_shape.php?trip_id=" + trip_id)
                    .then(r => r.json())
                    .then(data => {
                        let cor = (data.direction_id === 'Ida') ? 'blue' : 'red';

                        let poly = L.polyline(data.pontos, {
                            color: cor,
                            weight: 4
                        }).addTo(shapeLayer);

                        map.fitBounds(poly.getBounds());
                    });
            });
        </script>

        <p>
            <button class="btn-reg-cad">
                <a href="../index.html" class="a-btn-canc">VOLTAR</a>
            </button>
        </p>

    </section>
</body>

</html>