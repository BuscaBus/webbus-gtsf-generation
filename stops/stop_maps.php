<?php
require_once __DIR__ . "/../connection.php";

// Pega todos os pontos cadastrados que tenham coordenadas
$sql = "SELECT stop_id, stop_lat AS latitude, stop_lon AS longitude, stop_code FROM stops WHERE stop_lat <> '' AND stop_lon <> ''";
$result = mysqli_query($conexao, $sql);

$marcadores = [];
while ($row = mysqli_fetch_assoc($result)) {
    $marcadores[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de pontos</title>
    <link rel="shortcut icon" href="../img/logo-icon2.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/stops.css?v=1.4">

    <!-- CSS do Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />

    <!-- JS do Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

    <style>
        .form-coords {
            margin-top: 15px;
        }

        .form-coords label {
            display: inline-block;
            width: 80px;
            font-weight: bold;
        }

        .marker-blink {
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0% {
                opacity: 10;
            }

            50% {
                opacity: 0.2;
            }

            100% {
                opacity: 3;
            }
        }
    </style>
</head>

<body>
    <section>
        <!-- MAPA -->
        <div id="div-map"></div>

        <!-- Inputs para coordenadas -->
        <div class="form-coords">
            <form action="register.php" method="GET">
                <input type="hidden" id="stop_id" name="stop_id">
                <label>Latitude:</label>
                <input type="text" id="lat" name="latitude" readonly>

                <label>Longitude:</label>
                <input type="text" id="lng" name="longitude" readonly>

                <button type="submit" class="btn-reg-cad">SALVAR</button>
                <button type="button" id="btnEditar" class="btn-edt-cad">EDITAR</button>
                <button class="btn-reg-canc">
                    <a href="list.php" class="a-btn-canc">CANCELAR</a>
                </button>
            </form>
        </div>

        <script>
            // =================== BASEMAPS ===================
            var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            });

            var satelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles © Esri'
            });

            var map = L.map('div-map', {
                center: [-27.595740, -48.568228],
                zoom: 13,
                maxZoom: 18,
                layers: [osm]
            });

            var baseMaps = {
                "OpenStreetMap": osm,
                "Satélite": satelite
            };
            L.control.layers(baseMaps).addTo(map);

            // =================== DESENHO ===================
            var drawnItems = new L.FeatureGroup();
            map.addLayer(drawnItems);

            var drawControl = new L.Control.Draw({
                edit: {
                    featureGroup: drawnItems
                },
                draw: {
                    marker: true,
                    polyline: false,
                    polygon: false,
                    rectangle: false,
                    circle: false
                }
            });
            map.addControl(drawControl);


            // =================== ÍCONE DINÂMICO ===================
            function getIconSize(zoom) {
                const minSize = 10;
                const maxSize = 15;
                let size = zoom * 3; // ajuste conforme necessário
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
            var marcadoresExistentes = <?php echo json_encode($marcadores); ?>;
            var zoomMinimo = 17; // Nível mínimo de zoom para mostrar ícones

            let marcadorEditando = null;

            function atualizarMarcadores() {

                if (marcadorEditando) {
                    return;
                }

                marcadoresBanco.clearLayers();

                if (map.getZoom() >= zoomMinimo) {

                    var bounds = map.getBounds();

                    marcadoresExistentes.forEach(function(ponto) {

                        if (ponto.latitude && ponto.longitude) {

                            var latlng = L.latLng(
                                ponto.latitude,
                                ponto.longitude
                            );

                            if (bounds.contains(latlng)) {

                                var marker = L.marker(
                                    [ponto.latitude, ponto.longitude], {
                                        icon: criarIcone(map.getZoom()),
                                        draggable: false
                                    }
                                );

                                marker.on('contextmenu', function() {

                                    marcadorEditando = marker;

                                    marker.dragging.enable();

                                    document.getElementById('stop_id').value =
                                        ponto.stop_id;

                                    document.getElementById('lat').value =
                                        marker.getLatLng().lat;

                                    document.getElementById('lng').value =
                                        marker.getLatLng().lng;

                                    let el = marker.getElement();

                                    if (el) {
                                        el.classList.add('marker-blink');
                                    }                                    

                                });

                                marker.on('drag', function(e) {

                                    let pos = e.target.getLatLng();

                                    document.getElementById('lat').value =
                                        pos.lat.toFixed(8);

                                    document.getElementById('lng').value =
                                        pos.lng.toFixed(8);

                                });

                                marker.bindPopup(
                                    "<b>Ponto:</b> " +
                                    ponto.stop_code
                                );

                                marker.addTo(marcadoresBanco);

                            }
                        }
                    });
                }
            }

            // Atualiza ao iniciar, ao mudar zoom e ao mover o mapa
            map.on('zoomend', atualizarMarcadores);
            map.on('moveend', atualizarMarcadores);
            atualizarMarcadores();

            // =================== NOVO MARCADOR ===================
            map.on(L.Draw.Event.CREATED, function(event) {
                var layer = event.layer;

                if (event.layerType === 'marker') {
                    var coords = layer.getLatLng();

                    if (document.getElementById('lat')) document.getElementById('lat').value = coords.lat;
                    if (document.getElementById('lng')) document.getElementById('lng').value = coords.lng;

                    var marcador = L.marker([coords.lat, coords.lng], {
                            icon: criarIcone(map.getZoom())
                        })
                        .bindPopup("Lat: " + coords.lat + "<br>Lng: " + coords.lng)
                        .openPopup();

                    drawnItems.clearLayers();
                    drawnItems.addLayer(marcador);
                }
            });

            // Script para o botão editar 
            document.getElementById('btnEditar').addEventListener('click', function() {

                let stopId = document.getElementById('stop_id').value;

                if (!stopId) {

                    alert('Selecione um ponto com o botão direito.');

                    return;
                }

                let formData = new FormData();

                formData.append(
                    'stop_id',
                    stopId
                );

                formData.append(
                    'latitude',
                    document.getElementById('lat').value
                );

                formData.append(
                    'longitude',
                    document.getElementById('lng').value
                );

                fetch('editar_coordenadas.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.text())
                    .then(r => {

                        if (r.trim() === 'ok') {

                            alert(
                                'Coordenadas atualizadas com sucesso!'
                            );

                            location.reload();
                        } else {

                            alert(
                                'Erro ao atualizar coordenadas.'
                            );
                        }

                    })
                    .catch(error => {

                        console.error(error);

                        alert(
                            'Erro de comunicação com o servidor.'
                        );

                    });

            });
        </script>
    </section>
</body>

</html>