<?php
require_once __DIR__ . "/../connection.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Trás a trip_id 
$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if ($trip_id <= 0) {
    die("Trip inválida.");
}

$sql = "
    SELECT
        t.trip_id,
        t.route_id,
        t.shape_id,
        t.trip_headsign,
        t.trip_short_name,
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

// <<< AQUI >>>
$route_id = $trip['route_id'];
$shape_id = $trip['shape_id'];

?>

<script>
    const TRIP_ID = <?= (int)$trip['trip_id'] ?>;
    let tripAtual = TRIP_ID;
</script>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa das Trips</title>
    <link rel="shortcut icon" href="../img/logo-icon2.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/shape.css?v=2.5">

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
                        // Consulta no banco de dados e traz as viagens cadastradas
                        <?php
                        $sqlTrips = "
                                    SELECT
                                        trip_id,
                                        trip_short_name,
                                        trip_headsign,
                                        shape_id
                                    FROM trips
                                    WHERE route_id = ?
                                    ORDER BY trip_short_name, trip_headsign
                                ";

                        $stmtTrips = $conexao->prepare($sqlTrips);
                        $stmtTrips->bind_param("i", $route_id);
                        $stmtTrips->execute();
                        $resultTrips = $stmtTrips->get_result();

                        while ($t = $resultTrips->fetch_assoc()) {

                            $selected = ($t['trip_id'] == $trip_id) ? 'selected' : '';

                            $nome = trim($t['trip_short_name']);

                            if (!empty($t['trip_headsign'])) {
                                $nome .= ' - ' . $t['trip_headsign'];
                            }

                            echo "<option value='{$t['trip_id']}' data-shape='{$t['shape_id']}' {$selected}>{$nome}</option>";
                        }
                        ?>
                    </select>
                </p>
                <br>
                <table>
                    <caption>Pontos do Trajeto</caption>
                    <thead>
                        <th class="th-seq">Seq.</th>
                        <th class="th-cod">Cod</th>
                        <th class="th-ponto">Ponto</th>
                        <th class="th-inter">Intervalo</th>
                        <th class="th-destino">Destino</th>
                        <th class="th-acoes">Ação</th>
                    </thead>
                    <tbody id="tbodyStops"></tbody>
                </table>
                <br>
                <button type="button" id="btnCadastrar" class="btn-seq-cad">SALVAR</button>
                <button type="button" id="btnEditar" class="btn-seq-edt">EDITAR</button>
                </p>
            </section>

            <!-- Section para o mapa com a sequencia de pontos do trajeto -->
            <section class="sect-map-seq">
                <div id="div-map"></div>
            </section>

            <script>
                const SHAPE_ID = "<?= $shape_id ?>";
                const ROUTE_ID = "<?= $trip_id ?>";
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

                // ===== FUNÇÃO PARA ADICIONAR STOP NA TABELA =====
                function adicionarStopNaTabela(stop) {

                    const tbody = document.getElementById("tbodyStops");

                    const seq = tbody.rows.length + 1;

                    atualizarSequencia();

                    const novaLinha = document.createElement("tr");
                    novaLinha.setAttribute("data-code", stop.code);

                    novaLinha.innerHTML = `
                        <td style="display:none">0</td>
                        <td style="display:none">${stop.id}</td>

                        <td>
                            <input
                                type="number"
                                class="seq-input"
                                value="${seq}"
                                min="1"
                                onchange="reposicionarLinha(this)">
                        </td>
                        <td>${stop.code}</td>
                        <td>${stop.name}</td>
                        <td>
                            <input type="time" name="interval[]">
                        </td>
                        <td>
                            <input
                                type="text"
                                name="stop_headsign[]"
                                value="${stop.stop_headsign ?? ''}">                        
                        </td>
                        <td>
                            <button class="btn-excluir" onclick="removerLinha(this)">EXCLUIR</button>
                        </td>
                    `;

                    const existe = [...tbody.rows].some(row => row.cells[3].innerText.trim() === stop.code);
                    if (existe) {
                        const confirmar = confirm("Este ponto já foi adicionado.\nDeseja cadastrar novamente mesmo assim?");

                        if (!confirmar) {
                            return; // cancela inclusão
                        }
                    }

                    tbody.appendChild(novaLinha);
                }


                // Função para reorganizar sequência 
                function atualizarSequencia() {

                    const linhas = document.querySelectorAll("#tbodyStops tr");

                    linhas.forEach((row, index) => {

                        const campoSeq = row.querySelector(".seq-input");

                        if (campoSeq) {
                            campoSeq.value = index + 1;
                        }

                    });

                }

                // Função para reposicionar a sequencia na tabela de pontos 
                function reposicionarLinha(input) {

                    const row = input.closest("tr");
                    const tbody = document.getElementById("tbodyStops");

                    let novaPosicao = parseInt(input.value);

                    const totalLinhas = tbody.rows.length;

                    if (isNaN(novaPosicao) || novaPosicao < 1) {
                        novaPosicao = 1;
                    }

                    if (novaPosicao > totalLinhas) {
                        novaPosicao = totalLinhas;
                    }

                    row.remove();

                    const linhas = tbody.querySelectorAll("tr");

                    if (novaPosicao > linhas.length) {

                        tbody.appendChild(row);

                    } else {

                        tbody.insertBefore(
                            row,
                            linhas[novaPosicao - 1]
                        );

                    }

                    atualizarSequencia();
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
                function carregarStopsTabela() {

                    fetch("get_stops_sequence.php?trip_id=" + tripAtual)

                        .then(res => res.json())
                        .then(stops => {

                            const tbody = document.getElementById("tbodyStops");
                            tbody.innerHTML = "";

                            stops.forEach(stop => {

                                const tr = document.createElement("tr");

                                tr.setAttribute("data-code", stop.codigo);

                                tr.innerHTML = `
                    <td style="display:none">${stop.id}</td>
                    <td style="display:none">${stop.stop_id}</td>

                    <td>
                        <input
                            type="number"
                            class="seq-input"
                            value="${stop.seq}"
                            min="1"
                            onchange="reposicionarLinha(this)">
                    </td>

                    <td>${stop.codigo}</td>
                    <td>${stop.ponto}</td>

                    <td>
                        <input
                            type="time"
                            value="${stop.intervalo ?? ''}">
                    </td>

                    <td>
                        <input
                            type="text"
                            name="stop_headsign[]"
                            class="input-destino"
                            value="${stop.stop_headsign ?? ''}">                        
                    </td>

                    <td>
                        <button class="btn-excluir"
                            onclick="removerLinha(this)">
                            EXCLUIR
                        </button>
                    </td>
                `;

                                tbody.appendChild(tr);
                            });

                            atualizarSequencia();

                        });
                }

                // Carregar shape salvo
                function carregarShape() {

                    fetch("get_shape_by_trip.php?trip_id=" + tripAtual)

                        .then(res => res.json())
                        .then(coords => {

                            drawnItems.clearLayers();

                            if (!coords.length)
                                return;

                            const polyline = L.polyline(coords, {
                                color: "#0066ff",
                                weight: 3,
                                opacity: 0.85
                            });

                            drawnItems.addLayer(polyline);

                            map.fitBounds(polyline.getBounds());

                        });

                }

                const selectTrip = document.getElementById("trip-select");

                selectTrip.addEventListener("change", function() {

                    if (!this.value)
                        return;

                    tripAtual = this.value;

                    carregarShape();
                    carregarStopsTabela();

                });

                // Função para Salvar shape 
                function salvarShape(layer) {

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
                                trip_id: tripAtual,
                                shape_id: SHAPE_ID,
                                route_id: ROUTE_ID,
                                coords: coords
                            })
                        })

                        .then(res => res.json())
                        .then(data => {
                            console.log(data);
                            if (data.status == "ok") {
                                alert("Trajeto salvo com sucesso!");
                            } else {
                                alert(data.message);
                            }
                        });
                }

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

    <!-- Script para o botão cadastrar -->
    <script>
        document.getElementById("btnCadastrar").addEventListener("click", function() {

            const tbody = document.getElementById("tbodyStops");
            const linhas = tbody.querySelectorAll("tr");

            if (linhas.length === 0) {
                alert("Adicione pelo menos um ponto.");
                return;
            }

            let dados = [];

            linhas.forEach(function(row) {

                const stop_id = row.cells[1].innerText.trim();
                const seq = row.querySelector(".seq-input").value;
                const codigo = row.cells[3].innerText.trim();
                const ponto = row.cells[4].innerText.trim();
                const intervalo = row.querySelector('input[type="time"]').value;
                const destino = row.querySelector('input[name="stop_headsign[]"]').value;

                dados.push({

                    trip_id: tripAtual,
                    stop_id: stop_id,
                    seq: seq,
                    codigo: codigo,
                    ponto: ponto,
                    intervalo: intervalo,
                    destino: destino

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

                    console.log(resp);

                    if (resp.status === "ok") {

                        alert(resp.message);

                        location.reload();

                    } else {

                        alert(resp.message);

                    }

                })

                .catch(err => {

                    console.error(err);

                    alert("Erro de comunicação com o servidor.");

                });

        });

        // Script para o botão editar
        document.getElementById("btnEditar").addEventListener("click", function() {

            const linhas = document.querySelectorAll("#tbodyStops tr");

            if (linhas.length === 0) {
                alert("Nenhum ponto na tabela.");
                return;
            }

            let dados = [];

            linhas.forEach(row => {

                const id = row.cells[0].innerText;
                const intervalo = row.querySelector('input[type="time"]').value;
                const destino = row.querySelector('input[name="stop_headsign[]"]').value;

                dados.push({
                    id: id,
                    intervalo: intervalo,
                    destino: destino
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

    <script>
        // ===== CARREGA AUTOMATICAMENTE O PRIMEIRO TRAJETO =====
        window.addEventListener("DOMContentLoaded", function() {

            carregarShape();
            carregarStopsTabela();

        });
    </script>

    <script>
         // Script para prenchimento automático dos destinos na tabela de pontos
        function configurarDestinoAutomatico() {

            document.addEventListener("blur", function(e) {

                if (!e.target.matches('input[name="stop_headsign[]"]')) {
                    return;
                }

                const campos = document.querySelectorAll('input[name="stop_headsign[]"]');

                // Apenas o primeiro campo dispara a pergunta
                if (e.target !== campos[0]) {
                    return;
                }

                const destino = e.target.value.trim();

                if (destino === "") {
                    return;
                }

                // Verifica se existe algum campo vazio
                const existeVazio = [...campos].slice(1).some(c => c.value.trim() === "");

                if (!existeVazio) {
                    return;
                }

                if (confirm(`Deseja preencher todos os destinos com "${destino}"?`)) {

                    [...campos].slice(1).forEach(campo => {
                        if (campo.value.trim() === "") {
                            campo.value = destino;
                        }
                    });

                }

            }, true);

        }

        window.addEventListener("DOMContentLoaded", function() {

            carregarShape();
            carregarStopsTabela();

            configurarDestinoAutomatico();

        });
    </script>

</body>

</html>