<?php
include("../connection.php");

$trip_inicial_id = (int)($_GET['id'] ?? 0);

// Dados da trip inicial
$viagem = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT route_id, service_id, trip_headsign, trip_short_name, shape_id FROM trips WHERE trip_id = $trip_inicial_id"));
$route_id = $viagem['route_id'] ?? 0;

// Todas trips da mesma rota para o select (excluindo os duplicados)
$res_trips = mysqli_query($conexao, "
    SELECT MIN(trip_id) AS trip_id, trip_short_name, trip_headsign
    FROM trips
    WHERE route_id = $route_id
    GROUP BY trip_short_name, trip_headsign
    ORDER BY trip_short_name, trip_headsign
");

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa das Trips</title>
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/stop_times.css?v=1.7">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
</head>

<body>
    <div>
        <header>
            <h1>Mapa</h1>
        </header>
        <main>
            <section>
                 <h3>Viagem: <?= htmlspecialchars($viagem['trip_short_name']) ?> - <?= htmlspecialchars($viagem['trip_headsign']) ?> - <?= htmlspecialchars($viagem['service_id']) ?></h3>
                <br>                
                <label for="tripSelect">Viagem:</label>
                <select id="tripSelect" class="selc-viag">
                    <option value="" selected disabled>Selecione uma viagem</option>
                    <?php while ($t = mysqli_fetch_assoc($res_trips)) { ?>
                        <option value="<?= $t['trip_id'] ?>">
                            <?= $t['trip_short_name'] ?> - <?= $t['trip_headsign'] ?> 
                        </option>
                    <?php } ?>
                </select>
                <button type="button" id="btnSalvar" class="btn-salv">SALVAR</button>
                <br>
                
                <div id="div-map"></div>

                <script>
                    var map = L.map('div-map').setView([-27.595740, -48.568228], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                    var drawnItems = new L.FeatureGroup();
                    map.addLayer(drawnItems);

                    var drawControl = new L.Control.Draw({
                        edit: {
                            featureGroup: drawnItems,
                            remove: true
                        },
                        draw: {
                            polyline: {
                                shapeOptions: {
                                    color: '#0000ff',
                                    weight: 5,
                                    opacity: 0.8
                                }
                            },
                            polygon: false,
                            marker: false,
                            rectangle: false,
                            circle: false,
                            circlemarker: false
                        }
                    });
                    map.addControl(drawControl);

                    var tripInicialId = <?= $trip_inicial_id ?>;
                    var currentShapeId = <?= $viagem['shape_id'] ?? 'null' ?>;

                    // Função para carregar shape de qualquer trip
                    function carregarShape(tripId) {
                        drawnItems.clearLayers();
                        if (!tripId) return;

                        fetch("get_shape.php?trip_id=" + tripId)
                            .then(res => res.json())
                            .then(data => {
                                if (!data || !data.coords) return;
                                currentShapeId = data.shape_id;
                                if (data.coords.length > 0) {
                                    var polyline = L.polyline(data.coords, {
                                            color: "#0000ff",
                                            weight: 5,
                                            opacity: 0.8
                                        })
                                        .addTo(drawnItems);
                                    map.fitBounds(polyline.getBounds());
                                }
                            });
                    }

                    // Carrega shape da trip inicial automaticamente
                    carregarShape(tripInicialId);

                    // Desenho/edição do mapa
                    function salvarShapeNaTripInicial(layer) {
                        var geojson = layer.toGeoJSON();
                        if (geojson.geometry.type === "LineString") {
                            var coords = geojson.geometry.coordinates;
                            fetch("salvar_shape.php", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json"
                                    },
                                    body: JSON.stringify({
                                        trip_id: tripInicialId,
                                        coords: coords
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    alert(data.message);
                                    currentShapeId = data.shape_id;
                                });
                        }
                    }

                    map.on(L.Draw.Event.CREATED, function(e) {
                        drawnItems.clearLayers();
                        var layer = e.layer;
                        drawnItems.addLayer(layer);
                        salvarShapeNaTripInicial(layer);
                    });
                    map.on(L.Draw.Event.EDITED, function(e) {
                        e.layers.eachLayer(function(layer) {
                            salvarShapeNaTripInicial(layer);
                        });
                    });
                    map.on(L.Draw.Event.DELETED, function(e) {
                        fetch("salvar_shape.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    trip_id: tripInicialId,
                                    coords: []
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                alert(data.message);
                                currentShapeId = null;
                            });
                    });

                    // Select de trips: exibir shape selecionado imediatamente
                    document.getElementById("tripSelect").addEventListener("change", function() {
                        var tripSelecionada = this.value;
                        if (!tripSelecionada) return;
                        // Mostrar shape da trip selecionada (apenas visual)
                        carregarShape(tripSelecionada);
                    });

                    // Botão "Reaproveitar shape" → copia shape para a trip inicial
                    document.getElementById("btnSalvar").addEventListener("click", function() {
                        var select = document.getElementById("tripSelect");
                        var selectedTrip = select.value;
                        if (!selectedTrip) {
                            alert("Selecione uma viagem para reaproveitar o shape.");
                            return;
                        }

                        fetch("vincular_shape_trip.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    trip_origem_id: selectedTrip,
                                    trip_destino_id: tripInicialId
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                alert(data.message);
                                // Atualiza o mapa com o shape recém vinculado à trip inicial
                                carregarShape(tripInicialId);
                            });
                    });
                </script>

            </section>
        </main>
        <footer>
            <p><a href="../trips/register.php?id=<?= $route_id ?>">&lt; Voltar</a></p>
        </footer>
    </div>
</body>

</html>