<?php
include("../connection.php");

// Trás o route_id da lista de linhas
$route_id = $_GET['route_id'] ?? null;

if (!$route_id) {
    die("Route inválida");
}
$route_id = mysqli_real_escape_string($conexao, $route_id);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa das Trips</title>
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/shape.css?v=1.8">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <style>
        #div-map {
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body class="body-shst">
    <div>
        <header>
            <h1>Sequencia de paradas</h1>
        </header>
        <main class="main-shst">
            <!-- Section para tabela com o pontos do trajeto -->
            <section class="sect-tab-traj" id="scroll-area">
                <p>
                    <select id="trip-select" class="trip-select">
                        <option value="">Selecione um trajeto</option>
                        <?php
                        $sql_select = "SELECT DISTINCT mt.shape_id FROM maps_trips mt WHERE mt.route_id = '$route_id' ORDER BY mt.shape_id ASC";

                        $result_selec = mysqli_query($conexao, $sql_select);

                        while ($dados = mysqli_fetch_assoc($result_selec)) {
                            $tracado = $dados['shape_id'];
                            echo "<option value='$tracado'>$tracado</option>";
                        }
                        ?>
                    </select>
                </p>
                <br>
                <table>
                    <caption>Pontos do Trajeto</caption>
                    <thead>
                        <th class="th-seq">Seq.</th>
                        <th class="th-cod">Código</th>
                        <th class="th-ponto">Ponto</th>
                        <th class="th-inter">Intervalo</th>
                        <th class="th-acoes">Ação</th>
                    </thead>
                    <tbody id="tbodyStops"></tbody>
                </table>
                <br>
                <button type="button" id="btnCadastrar" class="btn-seq-cad">CADASTRAR</button>
                <button type="button" id="btnEditar" class="btn-seq-edt">EDITAR</button>                
                </p>
            </section>

            <!-- Section para o mapa com a sequencia de pontos do trajeto -->
            <section class="sect-map-seq">
                <div id="div-map"></div>
            </section>

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

                var busIcon = L.icon({
                    iconUrl: "../img/icon-bus2.png",
                    iconSize: [14, 14],
                    iconAnchor: [7, 14],
                    popupAnchor: [0, -15]
                });

                var busIconHover = L.icon({
                    iconUrl: "../img/icon-bus2.png",
                    iconSize: [24, 24],
                    iconAnchor: [12, 24],
                    popupAnchor: [0, -15],
                    className: "marker-hover"
                });

                var drawnItems = new L.FeatureGroup();
                map.addLayer(drawnItems);

                var stopsLayer = L.layerGroup().addTo(map);

                var drawControl = new L.Control.Draw({
                    edit: false,
                    draw: false
                });

                map.addControl(drawControl);

                // Cria função para carregar os stops
                function carregarStops() {

                    const bounds = map.getBounds();

                    const url = "get_stops.php?" +
                        "north=" + bounds.getNorth() +
                        "&south=" + bounds.getSouth() +
                        "&east=" + bounds.getEast() +
                        "&west=" + bounds.getWest();

                    fetch(url)
                        .then(res => res.json())
                        .then(stops => {

                            stopsLayer.clearLayers();

                            stops.forEach(stop => {

                                const marker = L.marker([stop.lat, stop.lon], {
                                        icon: busIcon
                                    })
                                    .bindPopup(
                                        "<b>" + stop.name + "</b><br>" +
                                        "Código: " + stop.code
                                    );

                                // ✅ HOVER
                                marker.on("mouseover", function() {
                                    this.setIcon(busIconHover);

                                    const linha = document.querySelector(`tr[data-code="${stop.code}"]`);
                                    if (linha) {
                                        linha.classList.add("highlight-row");
                                        linha.scrollIntoView({
                                            behavior: "smooth",
                                            block: "center"
                                        });
                                    }
                                });

                                marker.on("mouseout", function() {
                                    this.setIcon(busIcon);

                                    const linha = document.querySelector(`tr[data-code="${stop.code}"]`);
                                    if (linha) {
                                        linha.classList.remove("highlight-row");
                                    }
                                });

                                // ✅ BOTÃO DIREITO (FUNCIONANDO)
                                marker.on("contextmenu", function(e) {
                                    e.originalEvent.preventDefault(); // MUITO IMPORTANTE
                                    adicionarStopNaTabela(stop);
                                });

                                stopsLayer.addLayer(marker);

                            });

                        });
                }

                // Ativar carregamento por zoom                        
                map.on("zoomend", function() {

                    if (map.getZoom() >= 17) {
                        carregarStops();
                    } else {
                        stopsLayer.clearLayers();
                    }

                });

                map.on("moveend", function() {

                    if (map.getZoom() >= 17) {
                        carregarStops();
                    }

                });

                map.on("zoomend", function() {

                    if (map.getZoom() >= 17) {
                        carregarStops();
                    } else {
                        stopsLayer.clearLayers();
                    }

                });

                map.on("moveend", function() {

                    if (map.getZoom() >= 17) {
                        carregarStops();
                    }

                });

                // ===== FUNÇÃO PARA ADICIONAR STOP NA TABELA =====
                function adicionarStopNaTabela(stop) {

                    const tbody = document.getElementById("tbodyStops");

                    const seq = tbody.rows.length + 1;

                    atualizarSequencia();

                    const novaLinha = document.createElement("tr");
                    novaLinha.setAttribute("data-code", stop.code);

                    novaLinha.innerHTML = `
                        <td style="display:none">${stop.id}</td>
                        <td>${seq}</td>
                        <td>${stop.code}</td>
                        <td>${stop.name}</td>
                        <td><input type="time" name="interval[]"></td>
                        <td>
                            <button class="btn-excluir" onclick="removerLinha(this)">EXCLUIR</button>
                        </td>
                    `;

                    const existe = [...tbody.rows].some(row => row.cells[2].innerText == stop.code);

                    if (existe) {
                        alert("Este ponto já foi adicionado.");
                        return;
                    }

                    tbody.appendChild(novaLinha);
                }


                // Função para reorganizar sequência 
                function atualizarSequencia() {

                    const linhas = document.querySelectorAll("#tbodyStops tr");

                    linhas.forEach((row, index) => {
                        row.cells[1].innerText = index + 1;
                    });

                }

                // Função para ativar o arrastar linhas da tabela
                new Sortable(document.getElementById("tbodyStops"), {

                    animation: 150,

                    onEnd: function() {
                        atualizarSequencia();
                    }

                });

                // Função para remover linha atulizar sequencia
                function removerLinha(btn) {

                    const row = btn.closest("tr");

                    const id = row.cells[0].innerText;

                    if (!confirm("Deseja excluir este ponto?")) return;

                    fetch("delete_shape_stop.php", {

                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                id: id
                            })

                        })
                        .then(res => res.json())
                        .then(resp => {

                            if (resp.status === "ok") {

                                row.remove();
                                atualizarSequencia();

                            } else {

                                alert("Erro ao excluir.");

                            }

                        })
                        .catch(err => {

                            console.error(err);
                            alert("Erro no servidor.");

                        });

                }

                // Função para carregar os pontos na tabela vindos do banco de dados 
                function carregarStopsTabela(shapeId) {

                    const tr = document.createElement("tr");
                    tr.setAttribute("data-code", stop.codigo);

                    fetch("get_stops_sequence.php?shape_id=" + shapeId)
                        .then(res => res.json())
                        .then(stops => {

                            const tbody = document.getElementById("tbodyStops");

                            tbody.innerHTML = "";

                            stops.forEach(stop => {

                                const tr = document.createElement("tr");

                                tr.innerHTML = `
                <td style="display:none">${stop.id}</td>
                <td style="display:none">${stop.stop_id}</td>
                <td>${stop.seq}</td>
                <td>${stop.codigo}</td>
                <td>${stop.ponto}</td>
                <td>
                    <input type="time" value="${stop.intervalo ?? ''}">
                </td>
                <td>
                    <button class="btn-excluir" onclick="removerLinha(this)">EXCLUIR</button>
                </td>
            `;

                                tbody.appendChild(tr);

                            });

                            atualizarSequencia();

                        })
                        .catch(err => {
                            console.error("Erro ao carregar stops:", err);
                        });

                }

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

                    carregarStopsTabela(shapeId);

                    fetch("get_shape_by_id.php?shape_id=" + shapeId)
                        .then(res => res.json())
                        .then(coords => {

                            drawnItems.clearLayers();

                            const polyline = L.polyline(coords, {
                                color: "#0000ff",
                                weight: 5,
                                opacity: 0.5
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

    <!-- Script para o botão cadastrar -->
    <script>
        document.getElementById("btnCadastrar").addEventListener("click", function() {

            const tbody = document.getElementById("tbodyStops");
            const linhas = tbody.querySelectorAll("tr");

            if (linhas.length === 0) {
                alert("Adicione pelo menos um ponto.");
                return;
            }

            const shape_id = document.getElementById("trip-select").value;

            if (!shape_id) {
                alert("Selecione um trajeto (shape).");
                return;
            }

            let dados = [];

            linhas.forEach((row, index) => {

                const stop_id = row.cells[0].innerText;
                const seq = row.cells[1].innerText;
                const codigo = row.cells[2].innerText;
                const ponto = row.cells[3].innerText;
                const intervalo = row.querySelector("input").value;

                dados.push({
                    stop_id: stop_id,
                    seq: seq,
                    codigo: codigo,
                    ponto: ponto,
                    intervalo: intervalo,
                    shape_id: shape_id
                });

            });

            fetch("salvar_sequencia.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(dados)
                })
                .then(res => res.json())
                .then(resp => {

                    if (resp.status === "ok") {
                        alert("Sequência salva com sucesso!");
                        location.reload();
                    } else {
                        alert("Erro ao salvar.");
                    }

                })
                .catch(err => {
                    console.error(err);
                    alert("Erro no servidor.");
                });

        });

        <!-- Script para o botão editar -->
        document.getElementById("btnEditar").addEventListener("click", function() {

    const linhas = document.querySelectorAll("#tbodyStops tr");

    if (linhas.length === 0) {
        alert("Nenhum ponto na tabela.");
        return;
    }

    let dados = [];

    linhas.forEach(row => {

        const id = row.cells[0].innerText;
        const intervalo = row.querySelector("input").value;

        dados.push({
            id: id,
            intervalo: intervalo
        });

    });

    fetch("editar_intervalo.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(dados)
    })
    .then(res => res.json())
    .then(resp => {

        if (resp.status === "ok") {
            alert("Intervalos atualizados com sucesso!");
        } else {
            alert("Erro ao atualizar.");
        }

    })
    .catch(err => {
        console.error(err);
        alert("Erro no servidor.");
    });

});
    </script>
</body>

</html>

<!-- Script em JS para destaque do imput ao clicar no botão novo -->
<script>
    document.getElementById("btnNovo").addEventListener("click", function(e) {

        e.preventDefault();

        modoNovo = true;

        drawnItems.clearLayers();

        const input = document.getElementById("id-trip-traj");
        const select = document.getElementById("trip-select");

        input.value = "";
        select.value = "";

        // remove destaque do select
        select.classList.remove("input-destaque");

        // adiciona destaque no input
        input.classList.add("input-destaque");

        input.focus();

        carregarSelectShapes();
    });

    // Script em JS para destaque do trip-select ao clicar no botão copiar 
    document.getElementById("btnCopiar").addEventListener("click", function(e) {

        e.preventDefault();

        const input = document.getElementById("id-trip-traj");
        const select = document.getElementById("trip-select");

        // remove destaque do input
        input.classList.remove("input-destaque");

        // adiciona destaque no select
        select.classList.add("input-destaque");

        select.focus();

        carregarSelectShapes();
    });

    document.getElementById("id-trip-traj").addEventListener("input", function() {
        this.classList.remove("input-destaque");
    });
    document.getElementById("trip-select").addEventListener("change", function() {
        this.classList.remove("input-destaque");
    });
</script>