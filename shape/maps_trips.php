<?php
include("../connection.php");

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
    <link rel="stylesheet" href="../css/shape.css?v=1.0">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

    <style>
        #div-map {
            height: 450px;
            width: 1000px;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div>
        <header>
            <h1>Trajetos</h1>
        </header>
        <main>
            <section>
                <p>
                    <label for="id-trip-traj" class="lb-reg-trip-traj">Código:</label>
                    <input type="text" name="trip-trajeto" class="inpt-reg-trip-traj" id="id-trip-traj" placeholder="insira o código do trajeto..." required>
                </p>
                <br>
                <p>
                    <label for="tripSelect" class="lb-select">Copiar trajeto:</label>
                    <select id="trip-select" class="trip-select">
                        <option value="">Selecione</option>
                        <!-- opções via PHP -->
                    </select>
                    <button type="button" id="btnSalvar" class="btn-salv">SALVAR</button>
                </p>
                <br>

                <div id="div-map"></div>

                <script>                 
                    // ===== MAPA =====
                    var map = L.map('div-map').setView([-27.595740, -48.568228], 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);

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

                    // ===== SALVAR SHAPE =====
                    function salvarShape(layer) {

                        var shapeId = document.getElementById("id-trip-traj").value.trim();

                        if (!shapeId) {
                            alert("Informe o código do trajeto antes de desenhar.");
                            return;
                        }

                        var geojson = layer.toGeoJSON();

                        if (geojson.geometry.type !== "LineString") {
                            alert("Somente linhas são permitidas.");
                            return;
                        }

                        var coords = geojson.geometry.coordinates;                       

                        fetch("salvar_shape.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    shape_id: shapeId,
                                    coords: coords
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                alert(data.message);
                            });
                    }

                    // ===== EVENTOS DO DRAW =====
                    map.on(L.Draw.Event.CREATED, function(e) {
                        drawnItems.clearLayers();
                        drawnItems.addLayer(e.layer);
                        salvarShape(e.layer);
                    });

                    map.on(L.Draw.Event.EDITED, function(e) {
                        e.layers.eachLayer(function(layer) {
                            salvarShape(layer);
                        });
                    });

                    map.on(L.Draw.Event.DELETED, function() {
                        drawnItems.clearLayers();
                    });

                    // ===== BOTÃO SALVAR (regrava shape desenhado) =====
                    document.getElementById("btnSalvar").addEventListener("click", function() {
                        if (drawnItems.getLayers().length === 0) {
                            alert("Desenhe um trajeto no mapa antes de salvar.");
                            return;
                        }
                        salvarShape(drawnItems.getLayers()[0]);
                    });

                    // correção visual quando layout carrega
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 300);
                </script>

            </section>
        </main>

        <footer>
            <p><a href="../route/list.php">Voltar</a></p>
        </footer>
    </div>
</body>

</html>