<?php
require_once __DIR__ . "/../connection.php";

// Declaração da variavel para receber o ID
if (
    !isset($_GET['pattern_id']) ||
    !is_numeric($_GET['pattern_id'])
) {

    die("Erro: padrão de viagem não informado ou inválido.");
}

$pattern_id =
    (int) $_GET['pattern_id'];

if (isset($_GET['success'])) {
    echo "<script>alert('Horários cadastrados com sucesso!');</script>";
}

// Consulta no banco de dados
$sql = "
    SELECT
        pattern_id,
        route_id,
        trip_headsign,
        trip_short_name
    FROM trip_patterns
    WHERE pattern_id = ?
";

$stmt =
    $conexao->prepare($sql);

$stmt->bind_param(
    "i",
    $pattern_id
);

$stmt->execute();

$result_id =
    $stmt
    ->get_result()
    ->fetch_assoc();

if (!$result_id) {

    die("Padrão de viagem não encontrado.");
}

$route_id = (int) $result_id['route_id'];

// montar o select das viagens/patterns da mesma rota:
$sql_patterns = "
    SELECT
        pattern_id,
        trip_short_name,
        trip_headsign
    FROM trip_patterns
    WHERE route_id = ?
    ORDER BY
        trip_short_name,
        trip_headsign
";

$stmtPatterns =
    $conexao->prepare(
        $sql_patterns
    );

$stmtPatterns->bind_param(
    "i",
    $route_id
);

$stmtPatterns->execute();

$result_patterns =
    $stmtPatterns->get_result();

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
    <link rel="stylesheet" href="../css/departures.css?v=1.7">
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
                    <p class="p-estilo">
                        <label for="id-pattern" class="lb-reg-viag">Viagem:</label>
                        <select id="id-pattern" name="pattern_id" class="selec-reg-viag">
                            <?php
                            while ($pattern = $result_patterns->fetch_assoc()) { ?>
                                <option value="<?= (int) $pattern['pattern_id'] ?>" <?=
                                                                                    (
                                                                                        (int) $pattern['pattern_id'] === $pattern_id
                                                                                    )
                                                                                        ? 'selected'
                                                                                        : ''
                                                                                    ?>>
                                    <?= htmlspecialchars($pattern['trip_short_name']) ?> - <?= htmlspecialchars(
                                                                                                $pattern['trip_headsign']
                                                                                            ) ?>
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
                <h1 class="h1-cad-hor">Tabela de Horários <br><span id="titulo-servico"></span></h1>  
                <br>              
                <table>
                    <thead>
                        <tr>
                            <th class="th-hor">Horários</th>
                            <th class="th-viagem">Viagem</th>
                            <th class="th-adaptado">
                                <div class="adaptado-header">
                                    <span>Adaptado</span>
                                    <input
                                        type="checkbox"
                                        id="checkTodosAdaptado"
                                        title="Marcar ou desmarcar todos"
                                    >
                                </div>
                            </th>
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
        // Função para obter nome da viagem 
        function obterNomeViagem() {

            const select = document.getElementById("id-pattern");
            const option = select.options[select.selectedIndex];

            return option ? option.text.trim() : "";
        }

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

            const linha =
                botao.closest("tr");

            const trip_id =
                linha.dataset.tripId;


            if (
                !confirm(
                    "Deseja excluir este horário?"
                )
            ) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | HORÁRIO AINDA NÃO SALVO
            |--------------------------------------------------------------------------
            |
            | Se não existe trip_id, a linha só existe na tela.
            |
            */

            if (!trip_id) {

                linha.remove();

                atualizarCheckTodosAdaptado();

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | HORÁRIO JÁ SALVO
            |--------------------------------------------------------------------------
            */

            fetch(
                    "excluir_departure.php", {
                        method: "POST",

                        headers: {
                            "Content-Type": "application/json"
                        },

                        body: JSON.stringify({
                            trip_id: trip_id
                        })
                    }
                )

                .then(
                    response =>
                    response.json()
                )

                .then(
                    resp => {

                        if (
                            resp.status === "ok"
                        ) {

                            linha.remove();

                            atualizarCheckTodosAdaptado();

                        } else {

                            alert(
                                resp.message ||
                                "Erro ao excluir horário."
                            );
                        }

                    }
                )

                .catch(
                    erro => {

                        console.error(
                            erro
                        );

                        alert(
                            "Erro de comunicação com o servidor."
                        );

                    }
                );
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

            texto.split(/\r?\n/).forEach(function(horario) {

                horario = horario.trim();

                if (horario == "")
                    return;

                const tr = document.createElement("tr");

                const nomeViagem = obterNomeViagem();

                tr.innerHTML = `

                <td class="td-horario">
                    ${horario}
                </td>

                <td class="td-viagem">
                    ${nomeViagem}
                </td>

                <td class="td-adaptado">
                    <input
                        type="checkbox"
                        class="check-adaptado"
                        checked
                    >
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

                atualizarCheckTodosAdaptado();

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

            const nomeViagem = obterNomeViagem();

            tr.innerHTML = `

            <td class="td-horario">
                ${horario}
            </td>

            <td class="td-viagem">
                ${nomeViagem}
            </td>

            <td class="td-adaptado">
                <input
                    type="checkbox"
                    class="check-adaptado"
                    title="Marque se a partida é adaptada para cadeirante"
                    checked
                >
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

            atualizarCheckTodosAdaptado();

            input.value = "";
            input.focus();

        });
    </script>

    <script>
        // Script ao clicar em SALVAR
        document.getElementById("btn-salvar").addEventListener("click",
            function() {
                if (!validarServico()) {
                    return;
                }
                const pattern_id = document.getElementById("id-pattern").value;
                const service_id = document.getElementById("id-serv").value;
                const linhas = document.querySelectorAll("#tbodyHorarios tr");

                if (linhas.length === 0) {
                    alert(
                        "Adicione pelo menos um horário."
                    );

                    return;
                }

                let dados = [];

                linhas.forEach(
                    function(row) {
                        const adaptado =
                            row
                            .querySelector(
                                ".check-adaptado"
                            )
                            .checked ?
                            1 :
                            2;

                        dados.push({
                            pattern_id: pattern_id,
                            service_id: service_id,
                            departure_time: row.querySelector(".td-horario").innerText.trim(),
                            wheelchair_accessible: adaptado
                        });
                    }
                );

                fetch(
                        "salvar_departures.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify(
                                dados
                            )
                        }
                    )

                    .then(
                        r => r.json()
                    )

                    .then(
                        resp => {

                            alert(
                                resp.message
                            );

                            if (
                                resp.status === "ok"
                            ) {

                                carregarHorarios();
                            }

                        }
                    )

                    .catch(
                        erro => {
                            console.error(
                                erro
                            );

                            alert(
                                "Erro de comunicação com o servidor."
                            );
                        }
                    );
            }
        );
    </script>

    <script>
        // Script para carregar os horários
        const pattern = document.getElementById("id-pattern");
        const servico = document.getElementById("id-serv");

        pattern.addEventListener(
            "change",
            carregarHorarios
        );

        servico.addEventListener(
            "change",
            function() {

                atualizarTituloServico();

                carregarHorarios();

            }
        );

        function carregarHorarios() {

            const pattern_id = pattern.value;
            const service_id = servico.value;

            if (
                pattern_id === "" || service_id === ""
            ) {
                document.getElementById("tbodyHorarios").innerHTML = "";
                return;
            }

            fetch(
                    "buscar_departures.php" + "?pattern_id=" + encodeURIComponent(pattern_id) + "&service_id=" + encodeURIComponent(
                        service_id
                    )
                )

                .then(
                    r => r.json()
                )

                .then(
                    lista => {

                        const tbody = document.getElementById(
                            "tbodyHorarios"
                        );

                        tbody.innerHTML = "";

                        lista.forEach(
                            function(item) {

                                const tr =
                                    document.createElement("tr");

                                tr.dataset.tripId = item.trip_id;

                                const nomeViagem =
                                    obterNomeViagem();

                                const adaptadoMarcado =
                                    Number(
                                        item.wheelchair_accessible
                                    ) === 1 ?
                                    "checked" :
                                    "";

                                tr.innerHTML = `

                            <td class="td-horario">
                                ${item.departure_time.substring(0, 5)}
                            </td>

                            <td class="td-viagem">
                                ${nomeViagem}
                            </td>

                            <td class="td-adaptado">
                                <input
                                    type="checkbox"
                                    class="check-adaptado"
                                    ${adaptadoMarcado}
                                >
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn-excluir"
                                    onclick="removerLinha(this)"
                                >
                                    EXCLUIR
                                </button>
                            </td>
                        `;

                                tbody.appendChild(tr);
                            }
                        );

                        atualizarCheckTodosAdaptado();
                    }
                );
        }

        // Script para atualizar titulo do serviço
        function atualizarTituloServico() {

            const tituloServico =
                document.getElementById("titulo-servico");

            if (servico.value === "") {

                tituloServico.textContent = "";

                return;
            }

            const option =
                servico.options[servico.selectedIndex];

            tituloServico.textContent =
                "" + option.text.trim();
        }
    </script>

    <script>
        //Script para o checkbox de adaptado
        const checkTodosAdaptado =
            document.getElementById(
                "checkTodosAdaptado"
            );

        /*
        |--------------------------------------------------------------------------
        | MARCAR / DESMARCAR TODOS
        |--------------------------------------------------------------------------
        */

        checkTodosAdaptado.addEventListener(
            "change",
            function() {

                const checkboxes =
                    document.querySelectorAll(
                        "#tbodyHorarios .check-adaptado"
                    );


                checkboxes.forEach(
                    function(check) {

                        check.checked =
                            checkTodosAdaptado.checked;

                    }
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | ATUALIZA CHECKBOX DO CABEÇALHO
        |--------------------------------------------------------------------------
        */

        function atualizarCheckTodosAdaptado() {

            const checkboxes =
                document.querySelectorAll(
                    "#tbodyHorarios .check-adaptado"
                );


            if (checkboxes.length === 0) {

                checkTodosAdaptado.checked =
                    false;

                return;
            }


            checkTodosAdaptado.checked = [...checkboxes].every(
                checkbox =>
                checkbox.checked
            );
        }


        /*
        |--------------------------------------------------------------------------
        | QUANDO ALTERAR UM CHECKBOX INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        document
            .getElementById(
                "tbodyHorarios"
            )
            .addEventListener(
                "change",
                function(event) {

                    if (
                        event.target.classList.contains(
                            "check-adaptado"
                        )
                    ) {

                        atualizarCheckTodosAdaptado();
                    }

                }
            );
    </script>

</body>

</html>