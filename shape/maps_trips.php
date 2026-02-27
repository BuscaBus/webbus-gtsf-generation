<?php
include("../connection.php");

// Trás o route_id da lista de linhas
$route_id = $_GET['route_id'] ?? null;

if (!$route_id) {
    die("Route inválida");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa das Trips</title>
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/shape.css?v=1.1">

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
                    <button type="button" id="btnNovo" class="btn-novo">NOVO</button>
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
                    const ROUTE_ID = "<?= $route_id ?>";
                </script>

                <script>
                    let modoNovo = false;
                </script>

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

                    // Definição de palheta para as cores do traçado
                    const SHAPE_COLORS = [
                        "#0066ff", // azul
                        "#ff0000", // vermelho
                        "#00aa00", // verde
                        "#800080", // roxo
                        "#ff9900", // laranja
                        "#ffff00", // amarelo
                        "#8a2be2" // violeta
                    ];

                    // Carregar shape salvo
                    function carregarShape() {

                        fetch("get_shape.php?route_id=" + ROUTE_ID)
                            .then(res => res.json())
                            .then(shapes => {

                                if (!shapes || Object.keys(shapes).length === 0) return;

                                drawnItems.clearLayers();

                                let bounds = [];
                                let colorIndex = 0;

                                Object.keys(shapes).forEach(shapeId => {

                                    const color = SHAPE_COLORS[colorIndex % SHAPE_COLORS.length];

                                    const polyline = L.polyline(shapes[shapeId], {
                                        color: color,
                                        weight: 5,
                                        opacity: 0.85
                                    });

                                    polyline.bindTooltip(
                                        "Trajeto: " + shapeId, {
                                            sticky: true
                                        }
                                    );

                                    drawnItems.addLayer(polyline);
                                    bounds.push(...polyline.getLatLngs());

                                    colorIndex++;
                                });

                                if (bounds.length > 0) {
                                    map.fitBounds(bounds);
                                }
                            })
                            .catch(err => {
                                console.error("Erro ao carregar shapes:", err);
                            });
                    }

                    if (!modoNovo) {
                        carregarShape();
                    }

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
                                    route_id: ROUTE_ID,
                                    direction_id: 0,
                                    coords: coords
                                })
                            })
                            .then(res => res.json())
                            .then(data => {

                                if (data.status === "ok") {
                                    alert("Trajeto salvo com sucesso!");
                                } else {
                                    alert("Erro ao salvar o trajeto");
                                    console.error(data);
                                }

                            })
                            .catch(err => {
                                alert("Erro de comunicação com o servidor");
                                console.error(err);
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

                <!-- Script para carregar select Shape -->
                <script>
                    function carregarSelectShapes() {

                        fetch("get_shapes_route.php?route_id=" + ROUTE_ID)
                            .then(res => res.json())
                            .then(shapes => {

                                const select = document.getElementById("trip-select");
                                select.innerHTML = '<option value="">Selecione</option>';

                                shapes.forEach(shapeId => {
                                    const opt = document.createElement("option");
                                    opt.value = shapeId;
                                    opt.textContent = shapeId;
                                    select.appendChild(opt);
                                });

                            })
                            .catch(err => {
                                console.error("Erro ao carregar shapes:", err);
                            });
                    }
                </script>

                <!-- Script para o botão novo -->
                <script>
                    document.getElementById("btnNovo").addEventListener("click", function(e) {

                        e.preventDefault(); // evita abrir nova aba

                        modoNovo = true;

                        // limpa mapa
                        drawnItems.clearLayers();

                        // limpa código
                        document.getElementById("id-trip-traj").value = "";

                        // carrega shapes para copiar
                        carregarSelectShapes();

                        alert("Modo novo trajeto ativado. Selecione um trajeto para copiar ou desenhe um novo.");

                    });
                </script>

                <!-- Script para selecionar um shape e carregar no mapa -->
                <script>
                    document.getElementById("trip-select").addEventListener("change", function() {

                        const shapeId = this.value;

                        if (!shapeId) return;

                        fetch("get_shape_by_id.php?shape_id=" + shapeId)
                            .then(res => res.json())
                            .then(coords => {

                                drawnItems.clearLayers();

                                const polyline = L.polyline(coords, {
                                    color: "#0000ff", // mantém padrão
                                    weight: 5,
                                    opacity: 0.8
                                });

                                drawnItems.addLayer(polyline);
                                map.fitBounds(polyline.getBounds());

                            })
                            .catch(err => {
                                console.error("Erro ao carregar shape:", err);
                            });

                    });
                </script>

            </section>
        </main>

        <footer>
            <p><a href="../route/list.php">Voltar</a></p>
        </footer>
    </div>
</body>

</html>