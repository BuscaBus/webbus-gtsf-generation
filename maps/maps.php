<?php
require_once __DIR__ . "/../connection.php";
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Linhas</title>

    <link rel="stylesheet" href="../css/shape.css?v=1.2">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
</head>

<body>

    <section>

        <form id="form-filtro">

            <label>Operadora:</label>
            <select name="operadora" id="selc-op" class="selc-op">
                <option value="">Selecione a operadora</option>
            </select>

            <label>Linha:</label>
            <select name="linha" id="selc-linh" class="selc-linh">
                <option value="">Selecione a linha</option>
            </select>

            <label>Viagem:</label>
            <select name="viagem" id="selc-viag" class="selc-viag">
                <option value="">Selecione a viagem</option>
            </select>

        </form>
        <br>
        <div id="div-map"></div>

        <p>
            <button class="btn-reg-cad">
                <a href="../index.html" class="a-btn-maps-voltar">
                    VOLTAR
                </a>
            </button>
        </p>

    </section>

    <script>
        // ==========================
        // MAPA
        // ==========================

        const map = L.map('div-map').setView(
            [-27.595740, -48.568228],
            12
        );

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }
        ).addTo(map);

        let polyline = null;

        const operadora = document.getElementById("selc-op");
        const linha = document.getElementById("selc-linh");
        const viagem = document.getElementById("selc-viag");

        // ==========================
        // CARREGA OPERADORAS
        // ==========================

        fetch("carregar_operadoras.php")

            .then(response => response.json())

            .then(dados => {

                dados.forEach(op => {

                    operadora.innerHTML += `
                    <option value="${op.agency_id}">
                        ${op.agency_name}
                    </option>
                `;

                });

            })

            .catch(error => {

                console.error(
                    "Erro ao carregar operadoras:",
                    error
                );

            });

        // ==========================
        // AO TROCAR OPERADORA
        // ==========================

        operadora.addEventListener("change", () => {

            linha.innerHTML =
                '<option value="">Selecione a linha</option>';

            viagem.innerHTML =
                '<option value="">Selecione a viagem</option>';

            if (polyline) {
                map.removeLayer(polyline);
                polyline = null;
            }

            if (!operadora.value) {
                return;
            }

            fetch(
                    "carregar_linhas.php?agency_id=" +
                    operadora.value
                )

                .then(response => response.json())

                .then(dados => {

                    dados.forEach(item => {

                        linha.innerHTML += `
                    <option value="${item.route_id}">
                        ${item.route_short_name}
                        - ${item.route_long_name}
                    </option>
                `;

                    });

                })

                .catch(error => {

                    console.error(
                        "Erro ao carregar linhas:",
                        error
                    );

                });

        });

        // ==========================
        // AO TROCAR LINHA
        // ==========================

        linha.addEventListener("change", () => {

            viagem.innerHTML =
                '<option value="">Selecione a viagem</option>';

            if (polyline) {
                map.removeLayer(polyline);
                polyline = null;
            }

            if (!linha.value) {
                return;
            }

            fetch(
                    "carregar_viagens.php?route_id=" +
                    linha.value
                )

                .then(response => response.json())

                .then(dados => {

                    dados.forEach(item => {

                        let texto = item.trip_headsign;

                        if (
                            item.trip_short_name &&
                            item.trip_short_name.trim() !== ''
                        ) {
                            texto =
                                item.trip_short_name +
                                ' - ' +
                                item.trip_headsign;
                        }

                        viagem.innerHTML += `
                <option value="${item.shape_id}">
                    ${texto}
                </option>
            `;

                    });

                })

                .catch(error => {

                    console.error(
                        "Erro ao carregar viagens:",
                        error
                    );

                });

        });

        // ==========================
        // AO TROCAR VIAGEM
        // ==========================

        viagem.addEventListener("change", () => {

            if (!viagem.value) {
                return;
            }

            fetch(
                    "carregar_shape.php?shape_id=" +
                    encodeURIComponent(viagem.value)
                )

                .then(response => response.json())

                .then(coords => {

                    if (!coords.length) {

                        alert(
                            "Nenhum trajeto encontrado para esta viagem."
                        );

                        return;
                    }

                    if (polyline) {
                        map.removeLayer(polyline);
                    }

                    polyline = L.polyline(coords, {

                        color: '#0066ff',
                        weight: 5,
                        opacity: 0.9

                    }).addTo(map);

                    map.fitBounds(
                        polyline.getBounds()
                    );

                })

                .catch(error => {

                    console.error(
                        "Erro ao carregar shape:",
                        error
                    );

                });

        });
    </script>

</body>

</html>