<?php
include("../connection.php");

// Declaração da variavel para receber o ID
$id = $_GET['id'];

// Consulta no banco de dados para exibir no nome da viagem 
$sql_viag = "SELECT route_id, trip_headsign, trip_short_name FROM trips WHERE trip_id = $id";
$result_viag = mysqli_query($conexao, $sql_viag);

// Laço de repetição para trazer no nome da viagem do banco
while ($sql_result_viag = mysqli_fetch_array($result_viag)) {
    $route_id = $sql_result_viag['route_id'];
    $destino = $sql_result_viag['trip_headsign'];
    $origem = $sql_result_viag['trip_short_name'];
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema WebBus</title>
    <link rel="shortcut icon" href="../img/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/stop_times.css?v=1.6">

    <!-- CSS do Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- CSS do Leaflet.draw -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />

    <!-- JS do Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- JS do Leaflet.draw -->
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

</head>

<body>
    <div>
        <header>
            <h1>Trajeto</h1>
        </header>
        <main>
            <section class="scroll-area">
                <h3>Viagem: <?php echo $origem; ?> - <?php echo $destino; ?> </h3>
                <br>
                <hr>
                <br>
                <table>
                    <caption>Relação de pontos do trajeto</caption>
                    <thead>
                        <th class="th-seq">Sequencia</th>
                        <th class="th-pont">Ponto</th>
                        <th class="th-bair">Bairro</th>
                        <th class="th-cid">Cidade</th>
                    </thead>
                    <?php
                    // Consulta no banco de dados para exibir na tabela
                    $sql = "SELECT DISTINCT stop_times.stop_sequence, 
                                       stop_times.stop_code, 
                                       stop_times.stop_headsign,
                                       stops.stop_name,
                                       stops.stop_district,
                                       stops.stop_city
                                FROM stop_times 
                                JOIN stops ON stops.stop_code = stop_times.stop_code
                                WHERE trip_id = $id 
                                ORDER BY stop_sequence ASC";
                    $result = mysqli_query($conexao, $sql);

                    // Laço de repetição para trazer dados do banco
                    while ($sql_result = mysqli_fetch_array($result)) {
                        $sequencia = $sql_result['stop_sequence'];
                        $ponto = $sql_result['stop_name'];
                        $bairro = $sql_result['stop_district'];
                        $cidade = $sql_result['stop_city'];
                    ?>
                        <tbody>
                            <tr>
                                <td><?php echo $sequencia ?></td>
                                <td><?php echo $ponto ?></td>
                                <td><?php echo $bairro ?></td>
                                <td><?php echo $cidade ?></td>
                            </tr>
                        <?php }; ?>
                        </tbody>
                </table>
                <br>

                <!-- MAPA -->
                <div id="div-map">i</div>
                <script>
                    // Cria o mapa
                    var map = L.map('div-map').setView([-27.595740, -48.568228], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {}).addTo(map);

                    // Grupo de layers desenhados
                    var drawnItems = new L.FeatureGroup();
                    map.addLayer(drawnItems);

                    // Controle de desenho
                    var drawControl = new L.Control.Draw({
                        edit: {
                            featureGroup: drawnItems
                        },
                        draw: {
                            polyline: {
                                shapeOptions: {
                                    color: '#000000', // cor da linha
                                    weight: 5, // espessura (px)
                                    opacity: 0.8
                                }
                            },
                        }
                    });
                    map.addControl(drawControl);

                    // Evento ao criar um desenho
                    map.on(L.Draw.Event.CREATED, function(event) {
                        var layer = event.layer;
                        drawnItems.addLayer(layer);
                        console.log(layer.toGeoJSON());

                    });

                    map.addControl(drawControl);
                </script>
            </section>
        </main>
        <footer>
            <p><a href="../trips/register.php?id=<?= $route_id ?>"> < Voltar</a>
            </p>
        </footer>
    </div>
</body>

</html>