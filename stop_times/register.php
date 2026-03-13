<?php
include("../connection.php");

// Declaração da variavel para receber o ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Erro: ID não informado ou inválido.");
}

$id = (int) $_GET['id'];

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
    <link rel="stylesheet" href="../css/stop_times.css?v=1.6">
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
                    <input type="hidden" name="id" class="inpt1" id="id-nome" value="<?= $result_id['trip_id'] ?>">
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
                    <nav class="nav-reg-btn">
                        <p>
                            <button class="btn-reg-cad">CADASTRAR</button>
                        </p>
                        <p>
                            <button class="btn-reg-canc">
                                <a href="../trips/register.php?id=<?= $result_id['route_id'] ?>" class="a-btn-canc">CANCELAR</a>
                            </button>
                        </p>
                    </nav>
                </form>
            </section>

            <!-- Section para listar os horários -->
            <section class="sect-list-hor">
                <br>
                <table>
                    <h3>Viagem: <?= $result_id['trip_short_name'] ?> - <?= $result_id['trip_headsign'] ?> </h3>
                    <br>
                    <button type="button" onclick="gerarHorarios()">GERAR HORÁRIOS</button>
                    <br> <br>
                    <hr>
                    <br>
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
                    ?>
                        <tbody>
                            <tr>
                                <td><?php echo $seq ?></td>
                                <td><?php echo $stop_name ?></td>
                                <td><input type="time" name="" class="chegada"></td>
                                <td><input type="time" name="" class="partida"></td>
                                <td><input type="time" name="intervalo[]" class="intervalo" value="<?= $intervalo ?>"></td>
                                <td><input type="input" name=""></td>
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
            </section>

        </main>
        <footer>
            <p><a href="../trips/register.php?id=<?= $result_id['route_id'] ?>">
                    < Voltar</a>
            </p>
        </footer>
    </div>
</body>

</html>