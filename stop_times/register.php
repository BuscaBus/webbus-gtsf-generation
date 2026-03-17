<?php
include("../connection.php");

// Declaração da variavel para receber o ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Erro: ID não informado ou inválido.");
}

$id = (int) $_GET['id'];

// Buscar horários já cadastrados
$sql_stop_times = "SELECT stop_id, arrival_time, departure_time, stop_headsign 
                   FROM stop_times 
                   WHERE trip_id = $id";

$result_stop_times = mysqli_query($conexao, $sql_stop_times);

$horarios = [];

while ($row = mysqli_fetch_assoc($result_stop_times)) {
    $horarios[$row['stop_id']] = $row;
}

$shape_id = $_GET['shape_id'] ?? null;

if (!$shape_id) {
    die("Erro: shape_id não informado.");
}

// Consulta o ID no banco de dados
$sql = "SELECT route_id, trip_id, service_id, trip_headsign, trip_short_name, departure_time FROM trips WHERE trip_id = $id";
$result = mysqli_query($conexao, $sql);

// Variavel que recebe o ID do banco de dados    
$result_id = mysqli_fetch_assoc($result);

?>

<?php
if (isset($_GET['success'])) {
    echo "<script>alert('Horários cadastrados com sucesso!');</script>";
}
?>

<!--Script para confirmar a exclusão-->
<script>
    function deletar() {
        if (confirm("Deseja exluir esse item?"))
            document.forms[0].submit();
        else
            return false
    }
</script>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema WebBus</title>
    <link rel="shortcut icon" href="../img/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/stop_times.css?v=1.9">
</head>

<body>
    <div>
        <header>
            <h1>Horários</h1>
        </header>
        <main class="main-cont">
            <!-- Section para cadastrar horários -->
            <section class="sect-reg-hor">
                <h1 class="h1-cad-hor">Cadastrar Horários</h1>
                <br>
                <form action="result_register.php" method="POST" autocomplete="off" class="form-cad-vig">
                    <input type="hidden" name="route_id" class="inpt1" id="id-nome" value="<?= $result_id['route_id'] ?>">
                    <input type="hidden" name="trip_id" class="inpt1" id="id-nome" value="<?= $result_id['trip_id'] ?>">
                    <input type="hidden" name="shape_id" value="<?= $shape_id ?>">
                    <p class="p-estilo">
                        <label for="id-viag" class="lb-reg-viag">Viagem:</label>
                        <input type="text" name="viagem" class="inpt-reg-viag" id="id-viag" value="<?= $result_id['trip_short_name'] ?> - <?= $result_id['trip_headsign'] ?>" disabled>
                    </p>
                    <p class="p-estilo">
                        <label for="id-serv" class="lb-reg-serv">Serviço:</label>
                        <input type="text" name="servico" class="inpt-reg-serv" id="id-serv" value="<?= $result_id['service_id'] ?>" disabled>
                    </p>
                    <p class="p-estilo">
                        <label for="id-hrpart" class="lb-reg-hrpart">Partida:</label>
                        <input type="time" name="hora_partida" class="inpt-reg-hrpart" id="id-part" value="<?= $result_id['departure_time'] ?>" disabled>
                    </p>

                    <br>
                    

            </section>

            <!-- Section para listar os horários -->
            <section class="sect-list-hor">
                <br>
                <button type="button" class="btn-gerHor" onclick="gerarHorarios()">GERAR HORÁRIOS</button>
                <table>
                    <br><br>
                    <thead>
                        <th class="th-seq">Seq.</th>
                        <th class="th-ponto">Ponto</th>
                        <th class="th-cheg">Chegada</th>
                        <th class="th-part">Partida</th>
                        <th class="th-inter">Intervalo</th>
                        <th class="th-dest">Destino</th>
                    </thead>
                    <?php
                    // Consulta no banco de dados para exibir na tabela de viagens 
                    $sql = "SELECT ss.seq, ss.stop_id, ss.intervalo, s.stop_name
                            FROM shape_stops ss
                            JOIN stops s ON s.stop_id = ss.stop_id
                            WHERE ss.shape_id = '$shape_id'
                            ORDER BY ss.seq ASC";
                    $result = mysqli_query($conexao, $sql);

                    while ($sql_result = mysqli_fetch_array($result)) {
                        $seq = $sql_result['seq'];
                        $ponto = $sql_result['stop_id'];
                        $stop_name = $sql_result['stop_name'];
                        $intervalo = $sql_result['intervalo'];

                        $stop_id = $sql_result['stop_id'];

                        $arrival = $horarios[$stop_id]['arrival_time'] ?? '';
                        $departure = $horarios[$stop_id]['departure_time'] ?? '';
                        $headsign = $horarios[$stop_id]['stop_headsign'] ?? '';
                    ?>
                        <tbody>                            
                            <tr>
                                <td>
                                    <?= $sql_result['seq'] ?>
                                    <input type="hidden" name="stop_sequence[]" value="<?= $sql_result['seq'] ?>">
                                </td>

                                <td>
                                    <?= $sql_result['stop_name'] ?>
                                    <input type="hidden" name="stop_id[]" value="<?= $sql_result['stop_id'] ?>">
                                </td>

                                <td>
                                    <input type="time" name="arrival_time[]" class="chegada" value="<?= $arrival ?>">
                                </td>

                                <td>
                                    <input type="time" name="departure_time[]" class="partida" value="<?= $departure ?>">
                                </td>

                                <td>
                                    <input type="time" name="intervalo[]" class="intervalo" value="<?= $sql_result['intervalo'] ?>" disabled>
                                </td>

                                <td>
                                    <input type="text" name="stop_headsign[]" class="headsign" value="<?= $headsign ?>">
                                </td>
                            </tr>
                        <?php }; ?>

                       

                        <!-- Script para calcular horários automaticamente -->
                        <script>
                            function gerarHorarios() {

                                let partidaInicial = document.getElementById("id-part").value;

                                if (!partidaInicial) {
                                    alert("Informe o horário de partida da viagem.");
                                    return;
                                }

                                let linhas = document.querySelectorAll("tbody tr");

                                let horaAtual = partidaInicial;

                                linhas.forEach((linha, index) => {

                                    let chegada = linha.querySelector(".chegada");
                                    let partida = linha.querySelector(".partida");
                                    let intervalo = linha.querySelector(".intervalo");

                                    if (index == 0) {

                                        chegada.value = horaAtual;
                                        partida.value = horaAtual;

                                    } else {

                                        let partes = horaAtual.split(":");
                                        let minutos = (parseInt(partes[0]) * 60) + parseInt(partes[1]);

                                        let intPart = intervalo.value.split(":");
                                        let intervaloMin = (parseInt(intPart[0]) * 60) + parseInt(intPart[1]);

                                        minutos += intervaloMin;

                                        let novaHora = Math.floor(minutos / 60).toString().padStart(2, "0");
                                        let novoMin = (minutos % 60).toString().padStart(2, "0");

                                        horaAtual = novaHora + ":" + novoMin;

                                        chegada.value = horaAtual;
                                        partida.value = horaAtual;

                                    }

                                });

                            }
                        </script>
                        </tbody>
                </table>
                <br>    
                 <nav class="nav-reg-btn">
                    <p>
                        <button class="btn-reg-cad">SALVAR</button>
                    </p>                    
                </nav>                
                </form>
            </section>

        </main>
        <footer>
            <p><a href="../trips/register.php?id=<?= $result_id['route_id'] ?>">
                    < Voltar</a>
            </p>
        </footer>
    </div>
    <!-- Script para preenchimento dos campos automaticamente -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const campos = document.querySelectorAll(".headsign");

            campos.forEach((campo, index) => {

                campo.addEventListener("change", function() {

                    // Só pergunta se for o primeiro campo
                    if (index !== 0) return;

                    let destino = campo.value.trim();

                    if (destino === "") return;

                    if (confirm("Deseja preencher os demais destinos como \"" + destino + "\" ?")) {

                        campos.forEach((c, i) => {

                            if (i > 0) {
                                c.value = destino;
                            }

                        });

                    }

                });

            });

        });
    </script>
    
</body>

</html>