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

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema WebBus</title>
    <link rel="shortcut icon" href="../img/logo-icon2.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.3">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/departures.css?v=1.5">
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
                            <option value="" selected disabled>Selecione um serviço</option>
                            <?php while ($service = mysqli_fetch_assoc($result_calendar)) { ?>
                                <option value="<?= $service['service_id'] ?>">
                                    <?= htmlspecialchars($service['service_id']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </p>
                    <p class="p-estilo">
                        <label class="lb-reg-hrpart">Partida:</label>
                        <input type="time" id="inpt-reg-hrpart" name="horario" class="inpt-reg-hrpart">
                    </p>

                    <p class="p-estilo">
                        <label class="lb-reg-lista">Adicionar lista de horários:</label><br>
                        <textarea id="lista-horarios" class="txt-lista-horarios" rows="30"></textarea>
                    </p>
                    <p>
                        <button type="button" id="btn-importar" class="btn-reg-importar">ADICIONAR LISTA </button>
                    </p>
                </form>
                <br>
                <P>
                    <button type="button" id="btn-adic" class="btn-reg-adic">ADICIONAR</button>
                </P>
            </section>

            <!-- Section para listar os horários -->
            <section class="sect-list-hor">
                <br>
                <table>
                    <thead>
                        <tr>
                            <th class="th-hor">Horários</th>
                            <th class="th-fixo">Fixo</th>
                            <th class="th-adaptado">Adaptado</th>                            
                            <th class="th-acoes">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyHorarios"></tbody>
                </table>
                <br>
                <P>
                    <button type="button" id="btn-salvar" class="btn-reg-salvar">SALVAR</button>
                </P>
            </section>

        </main>
        <footer>
            <p><a href="../trips/register.php?id=<?= $route_id ?>">
                    < Voltar</a>
            </p>
        </footer>
    </div>
    
        <script>
            // Função reutilizável para validar o serviço
            function validarServico() {

                const service = document.getElementById("id-serv");

                if (service.value === "") {
                    alert("Selecione um serviço antes de continuar.");
                    service.focus();
                    return false;
                }

                return true;
            }


            // Função para excluir um horário da lista
            function removerLinha(botao) {

                if (!confirm("Deseja excluir este horário?")) {
                    return;
                }

                const linha = botao.closest("tr");

                linha.remove();
            }
    </script>

    <script>
        // Script ao clicar em ADICIONAR LISTA
        document.getElementById("btn-importar").addEventListener("click", function() {

            if (!validarServico()) return;

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
                    <input
                        type="checkbox"
                        class="check-fixo"
                        checked>
                </td>

                <td>
                    <input
                        type="checkbox"
                        class="check-adaptado" checked>
                </td>               

                <td>
                    <button
                        type="button"
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

    <script>
        // Script do botão ADICIONAR
        document.getElementById("btn-adic").addEventListener("click", function() {

            if (!validarServico()) return;

            const input = document.getElementById("inpt-reg-hrpart");
            const horario = input.value.trim();

            if (horario === "") {
                alert("Informe um horário.");
                input.focus();
                return;
            }

            const tbody = document.getElementById("tbodyHorarios");

            // Evita horários repetidos
            const existe = [...tbody.querySelectorAll("tr")].some(function(tr) {
                return tr.cells[0].innerText.trim() === horario;
            });

            if (existe) {
                alert("Este horário já foi adicionado.");
                return;
            }

            const tr = document.createElement("tr");

            tr.innerHTML = `
            <td>${horario}</td>

            <td>
                <input
                    type="checkbox"
                    class="check-fixo"
                    title="Marcado: horário fixo. Desmarcado: horário previsto" checked>
            </td>

            <td>
                <input
                    type="checkbox"
                    class="check-adaptado"
                    title="Marque se a partida é adaptada para cadeirante" checked>
            </td>            

            <td>
                <button
                    type="button"
                    class="btn-excluir"
                    onclick="removerLinha(this)">
                    EXCLUIR
                </button>
            </td>
        `;

            tbody.appendChild(tr);

            input.value = "";
            input.focus();

        });
    </script>

    <script>
        // Script ao clicar em SALVAR
        document.getElementById("btn-salvar").addEventListener("click", function() {

            const trip_id =
                document.getElementById("id-viag").value;

            const service_id =
                document.getElementById("id-serv").value;

            const linhas =
                document.querySelectorAll("#tbodyHorarios tr");

            let dados = [];
            linhas.forEach(function(row) {

                const adaptado =
                    row.querySelector(".check-adaptado").checked ? 1 : 0;

                const horarioFixo =
                    row.querySelector(".check-fixo").checked ? 1 : 0;

                dados.push({
                    trip_id: trip_id,
                    service_id: service_id,
                    departure_time: row.cells[0].innerText.trim(),

                    // Marcado = 1, desmarcado = 0
                    wheelchair_accessible: adaptado,

                    // GTFS: 1 = exato, 0 = aproximado
                    timepoint: horarioFixo
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

    <script>
        // Script para carregar os horários
        const viagem = document.getElementById("id-viag");
        const servico = document.getElementById("id-serv");

        viagem.addEventListener("change", carregarHorarios);
        servico.addEventListener("change", carregarHorarios);

        function carregarHorarios() {

            const trip_id = viagem.value;
            const service_id = servico.value;

            if (trip_id === "" || service_id === "")
                return;

            fetch(`buscar_departures.php?trip_id=${trip_id}&service_id=${service_id}`)
                .then(r => r.json())
                .then(lista => {

                    const tbody = document.getElementById("tbodyHorarios");

                    tbody.innerHTML = "";

                    lista.forEach(function (item) {

                        const tr = document.createElement("tr");

                        const adaptadoMarcado =
                            Number(item.wheelchair_accessible) === 1
                                ? "checked"
                                : "";

                        const fixoMarcado =
                            Number(item.timepoint) === 1
                                ? "checked"
                                : "";

                        tr.innerHTML = `
                            <td>${item.departure_time.substring(0, 5)}</td>

                            <td>
                                <input
                                    type="checkbox"
                                    class="check-fixo"
                                    ${fixoMarcado}>
                            </td>

                            <td>
                                <input
                                    type="checkbox"
                                    class="check-adaptado"
                                    ${adaptadoMarcado}>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn-excluir"
                                    onclick="removerLinha(this)">
                                    EXCLUIR
                                </button>
                            </td>
                        `;

                        tbody.appendChild(tr);

                        });

                });

        }
    </script>

</body>

</html>