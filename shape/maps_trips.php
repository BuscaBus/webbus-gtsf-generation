<?php
require_once __DIR__ . "/../connection.php";

$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if ($trip_id <= 0) {
    die("Trip inválida.");
}

// Buscar Trip
$sql = "SELECT
    t.trip_id,
    t.route_id,
    t.shape_id,
    r.route_short_name,
    r.route_long_name
FROM trips t
INNER JOIN routes r
    ON r.route_id = t.route_id
WHERE t.trip_id = ?
";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $trip_id);
$stmt->execute();

$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    die("Trip não encontrada.");
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa da Trip</title>

    <link rel="shortcut icon" href="../img/logo-icon2.png">
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/shape.css?v=1.8">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

    <style>
        #div-map {
            height: 100%;
            width: 100%;
            margin: auto;
        }
    </style>

</head>

<body class="body-mptp">
    <div>
        <header>
            <h1>Trajeto da Viagem</h1>
        </header>
        <main class="main-mptp">
            <section class="sect-reg-traj">
                <h2 class="h2-rota">
                    <?= htmlspecialchars($trip['route_short_name']) ?> - <?= htmlspecialchars($trip['route_long_name']) ?>
                </h2>
                <br>
                <label class="lb-copy-select"> Copiar trajeto: </label>
                <select id="trip-copy-select" class="trip-copy-select">
                    <option value=""> Selecione uma Viagem</option>
                </select>
                <br><br><br>
                <p>
                    <input type="file" id="arquivoKmz" accept=".kmz,.kml" style="display:none;">
                    <button type="button" id="btnImportarKmz" class="btn-importar"> IMPORTAR KMZ </button>
                </p>
                <br><br>
                <button type="button" id="btnSalvar" class="btn-salv"> SALVAR </button>
                <button 
                       type="button" class="btn-reg-canc" onclick="window.location='../trips/register.php?id=<?= urlencode($trip['route_id']) ?>'"> CANCELAR  
                </button>
                <br><br>
                <p>
                    <button class="btn-seq-par">
                        <a href="shape_stops.php?trip_id=<?= $trip['trip_id'] ?>" class="a-btn-seq-par"> SEQUÊNCIA DE PARADAS </a>
                    </button>
                </p>
            </section>

            <section class="sect-reg-map">
                <div id="div-map"></div>
            </section>

            <script>
                const TRIP_ID = <?= $trip['trip_id'] ?>;
                const ROUTE_ID = <?= $trip['route_id'] ?>;
                let SHAPE_ID = "<?= $trip['shape_id'] ?>";
            </script>

            <script>
                // MAPA
                let map = L.map('div-map').setView([-27.595740, -48.568228], 13);

                L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }
                ).addTo(map);

                // CAMADA DOS SHAPES
                let drawnItems = new L.FeatureGroup();
                map.addLayer(drawnItems);

                // CONTROLE DE DESENHO
                let drawControl = new L.Control.Draw({
                    edit: {
                        featureGroup: drawnItems,
                        remove: false
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

                // CARREGAR SHAPE EXISTENTE
                function carregarShapeExistente() {
                    if (!SHAPE_ID) {
                        console.log(
                            "Trip sem shape. Aguardando desenho."
                        );
                        return;
                    }

                    fetch(
                            "get_shape_by_id.php?shape_id=" +
                            SHAPE_ID
                        )

                        .then(res => res.json())
                        .then(coords => {

                            if (!coords || coords.length === 0) {
                                return;
                            }

                            let linha = L.polyline(
                                coords, {
                                    color: '#0000ff',
                                    weight: 3,
                                    opacity: 0.8
                                }
                            );

                            drawnItems.addLayer(linha);

                            map.fitBounds(
                                linha.getBounds()
                            );

                        })

                        .catch(err => {
                            console.error(
                                "Erro ao carregar shape:",
                                err
                            );
                        });
                }

                // QUANDO ABRE A PÁGINA
                window.onload = function() {
                    carregarShapeExistente();
                    carregarTripsComShape();
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 300);
                };

                // CRIOU UMA LINHA NOVA
                map.on(
                    L.Draw.Event.CREATED,
                    function(e) {
                        // permite apenas uma linha
                        drawnItems.clearLayers();
                        drawnItems.addLayer(
                            e.layer
                        );

                        console.log(
                            "Novo shape desenhado"
                        );
                    }

                );

                // EDITOU A LINHA
                map.on(
                    L.Draw.Event.EDITED,
                    function(e) {
                        e.layers.eachLayer(
                            function(layer) {
                                console.log(
                                    "Shape alterado"
                                );
                            }
                        );
                    }
                );

                // FUNÇÃO GERAR DADOS DO SHAPE
                function obterCoordenadasShape() {
                    let layer =
                        drawnItems.getLayers()[0];
                    if (!layer) {
                        return null;
                    }

                    let geojson = layer.toGeoJSON();
                    if (
                        geojson.geometry.type !==
                        "LineString"
                    ) {
                        return null;
                    }

                    return geojson.geometry.coordinates;

                }
            </script>

            <script>
                // BOTÃO SALVAR
                document
                    .getElementById("btnSalvar")
                    .addEventListener(
                        "click",
                        function() {
                            let coords =
                                obterCoordenadasShape();
                            if (!coords) {
                                alert(
                                    "Nenhum trajeto desenhado."
                                );
                                return;
                            }
                            let dados = {
                                trip_id: TRIP_ID,
                                route_id: ROUTE_ID,
                                shape_id: SHAPE_ID,
                                direction_id: 0,
                                coords: coords
                            };

                            fetch(
                                    "salvar_shape.php", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json"
                                        },

                                        body: JSON.stringify(dados)
                                    }
                                )

                                .then(res => res.json())
                                .then(data => {
                                    console.log(data);

                                    if (data.status === "ok") {
                                        alert(
                                            "Trajeto salvo com sucesso!"
                                        );

                                        // caso tenha criado novo shape
                                        if (!SHAPE_ID && data.shape_id) {
                                            SHAPE_ID = data.shape_id;
                                            document.getElementById("shapeAtual").innerHTML = SHAPE_ID;

                                            // atualiza link sequência
                                            document.querySelector(".a-btn-seq-par").href = "shape_stops.php?shape_id=" + SHAPE_ID;
                                        }
                                    } else {
                                        alert(
                                            data.mensagem ||
                                            "Erro ao salvar trajeto."
                                        );
                                    }
                                })

                                .catch(err => {
                                    console.error(err);
                                    alert(
                                        "Erro de comunicação."
                                    );
                                });
                        }

                    );
                // Carregar as Trips automaticamente
                function carregarTripsComShape() {
                    fetch("get_trips_com_shape.php")
                        .then(res => res.json())
                        .then(trips => {
                            let select =
                                document.getElementById(
                                    "trip-copy-select"
                                );
                            trips.forEach(trip => {
                                let option =
                                    document.createElement("option");
                                option.value =
                                    trip.shape_id;
                                option.textContent =
                                    trip.nome;
                                select.appendChild(option);
                            });
                        });
                }

                // Ao escolher uma Trip, carregar o trajeto
                document.getElementById("trip-copy-select").addEventListener(
                    "change",
                    function() {
                        let shape_id = this.value;
                        if (!shape_id) {
                            return;
                        }

                        fetch(
                                "get_shape_by_id.php?shape_id=" +
                                shape_id
                            )
                            .then(res => res.json())
                            .then(coords => {
                                drawnItems.clearLayers();
                                let linha =
                                    L.polyline(
                                        coords, {
                                            color: "#0000ff",
                                            weight: 5
                                        }
                                    );

                                drawnItems.addLayer(linha);

                                map.fitBounds(
                                    linha.getBounds()
                                );
                            });
                    });

                // Abrir o seletor de arquivos
                document.getElementById("btnImportarKmz")
                    .addEventListener("click", function() {

                        document.getElementById("arquivoKmz").click();

                    });

                // Ler o arquivo
                document.getElementById("arquivoKmz")
                    .addEventListener("change", function() {

                        let arquivo = this.files[0];

                        if (!arquivo) {
                            return;
                        }

                        let formData = new FormData();

                        formData.append("arquivo", arquivo);

                        fetch("importar_kmz.php", {

                                method: "POST",

                                body: formData

                            })
                            .then(r => r.json())
                            .then(dados => {

                                carregarShapeImportado(dados);

                            });

                    });

                // Exibir no mapa
                function carregarShapeImportado(coords) {

                    drawnItems.clearLayers();

                    let linha = L.polyline(coords, {

                        color: "#0000ff",

                        weight: 5

                    });

                    drawnItems.addLayer(linha);

                    map.fitBounds(linha.getBounds());

                }
            </script>
        </main>
        <footer>
            <p>
                <a href="../route/list.php"> Voltar </a>
            </p>
        </footer>
    </div>
</body>

</html>