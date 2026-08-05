<?php
require_once __DIR__ . "/../connection.php";

// Declaração da variavel para receber o ID
if (!isset($_GET['trip_id']) || !is_numeric($_GET['trip_id'])) {
    die("Erro: ID não informado ou inválido.");
}

$id = (int) $_GET['trip_id'];

if (isset($_GET['success'])) {
    echo "<script>alert('Horários cadastrados com sucesso!');</script>";
}

// Consulta as trips (viagens) no banco de dados
$sql = "SELECT route_id, trip_id, trip_headsign, trip_short_name FROM trips WHERE trip_id = $id";
$result = mysqli_query($conexao, $sql);

// Variavel que recebe o ID do banco de dados    
$result_id = mysqli_fetch_assoc($result);

$route_id = $result_id['route_id'];

// Consulta no banco para trazer as trips (viagens)
$sql_trips = "SELECT
                trip_id,
                service_id,
                trip_short_name,
                trip_headsign
              FROM trips
              WHERE route_id = '$route_id'
              ORDER BY trip_short_name, trip_headsign";

$result_trips = mysqli_query($conexao, $sql_trips);

// Consulta no banco para trazer os serviços (dias da semana)
$sql_calendar = "SELECT
                service_id
              FROM calendar              
              ORDER BY service_id DESC";

$result_calendar = mysqli_query($conexao, $sql_calendar);

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
    <link rel="shortcut icon" href="../img/logo-icon2.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.3">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/departures.css?v=1.1">
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
                    <input type="hidden" name="route_id" id="id-route" value="<?= $route_id ?>">
                    <input type="hidden" name="trip_id" id="id-trip" value="<?= $id ?>">
                    <p class="p-estilo">
                        <label for="id-viag" class="lb-reg-viag">Viagem:</label>
                        <select id="id-viag" name="trip_id" class="selec-reg-viag">
                            <?php while ($trip = mysqli_fetch_assoc($result_trips)) { ?>
                                <option value="<?= $trip['trip_id'] ?>"
                                    <?= ($trip['trip_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($trip['trip_short_name']) ?> -
                                    <?= htmlspecialchars($trip['trip_headsign']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </p>
                    <p class="p-estilo">
                        <label class="lb-reg-serv">Serviço:</label>
                        <select id="id-serv" name="service_id" class="selec-reg-serv">
                            <option>Selecione um serviço</option>
                            <?php while ($service = mysqli_fetch_assoc($result_calendar)) { ?>
                                <option value="<?= $service['service_id'] ?>">
                                    <?= htmlspecialchars($service['service_id']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </p>
                    <p class="p-estilo">
                        <label class="lb-reg-hrpart">Partida:</label>
                        <input type="time" name="horario" class="inpt-reg-hrpart">
                    </p>

                    <p class="p-estilo">
                        <label class="lb-reg-lista">Adicionar lista de horários:</label><br>
                        <textarea id="lista-horarios" class="txt-lista-horarios" rows="30"></textarea>
                    </p>
                    <p>
                        <button type="button" id="btn-importar" class="btn-reg-importar">IMPORTAR LISTA </button>
                    </p>
                </form>
                <br>
                <P>
                    <button type="button" id="btn-salvar" class="btn-reg-salvar">SALVAR</button>
                </P>
            </section>

            <!-- Section para listar os horários -->
            <section class="sect-list-hor">
                <br>
                <table>
                    <br><br>
                    <thead>
                        <th class="th-hor">Horário</th>
                        <th class="th-acoes">Ação</th>
                    </thead>
                    <tbody id="tbodyHorarios">
                        <tr>
                            <td>

                            </td>
                            <td>
                                <button class="btn-excluir" onclick="removerLinha(this)">EXCLUIR</button>
                            </td>
                        </tr>

                    </tbody>
                </table>
                <br>

            </section>

        </main>
        <footer>
            <p><a href="../trips/register.php?id=<?= $route_id ?>">
                    < Voltar</a>
            </p>
        </footer>
    </div>

    // Script ao clicar em IMPORTAR LISTA
    <script>
        document.getElementById("btn-importar").addEventListener("click", function() {

            const texto =
                document.getElementById("lista-horarios").value.trim();

            if (texto == "") {
                alert("Informe a lista.");
                return;
            }

            const tbody = document.getElementById("tbodyHorarios");

            tbody.innerHTML = "";

            texto.split(/\r?\n/).forEach(function(horario) {

                horario = horario.trim();

                if (horario == "")
                    return;

                const tr = document.createElement("tr");

                tr.innerHTML = `
            <td>${horario}</td>

            <td>
                <button
                    class="btn-excluir"
                    onclick="removerLinha(this)">
                    EXCLUIR
                </button>
            </td>
        `;

                tbody.appendChild(tr);

            });

            document.getElementById("lista-horarios").value = "";

        });
    </script>

    // Script ao clicar em SALVAR
    <script>
        document.getElementById("btn-salvar").addEventListener("click", function() {

            const trip_id =
                document.getElementById("id-viag").value;

            const service_id =
                document.getElementById("id-serv").value;

            const linhas =
                document.querySelectorAll("#tbodyHorarios tr");

            let dados = [];

            linhas.forEach(function(row) {

                dados.push({

                    trip_id: trip_id,

                    service_id: service_id,

                    departure_time: row.cells[0].innerText.trim()

                });

            });

            fetch("salvar_departures.php", {

                    method: "POST",

                    headers: {
                        "Content-Type": "application/json"
                    },

                    body: JSON.stringify(dados)

                })

                .then(r => r.json())

                .then(resp => {

                    alert(resp.message);

                    if (resp.status == "ok")
                        location.reload();

                });

        });
    </script>

</body>

</html>