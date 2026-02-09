<?php
include("../connection.php");

$filtro_sql = "";
$pesquisa = "";
$servico = "";
$terminal = "";
$box = "";
$result = null;
$nome_pt = "";

// Só executa se tiver filtros
if (
    (isset($_GET['buscar']) && !empty($_GET['buscar'])) ||
    (isset($_GET['terminal']) && !empty($_GET['terminal'])) ||
    (isset($_GET['box']) && !empty($_GET['box'])) ||
    (isset($_GET['servico']) && !empty($_GET['servico']) && $_GET['servico'] !== "selecionar")
) {

    // Filtro por ponto
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $pesquisa = mysqli_real_escape_string($conexao, $_GET['buscar']);
        $filtro_sql .= " AND st.stop_code = '$pesquisa'";
    }

    // Filtro por terminal
    if (isset($_GET['terminal']) && !empty($_GET['terminal'])) {
        $terminal = mysqli_real_escape_string($conexao, $_GET['terminal']);
        $filtro_sql .= " AND tp.parent_station = '$terminal'";
    }

    // Filtro por box
    if (isset($_GET['box']) && !empty($_GET['box'])) {
        $box = mysqli_real_escape_string($conexao, $_GET['box']);
        $filtro_sql .= " AND tp.stop_name = '$box'";
    }

    // Filtro por serviço (dia da semana)
    if (isset($_GET['servico']) && !empty($_GET['servico']) && $_GET['servico'] !== "selecionar") {
        $servico = mysqli_real_escape_string($conexao, $_GET['servico']);
        $filtro_sql .= " AND t.service_id = '$servico'";
    }

    // Consulta principal corrigida
    $sql = "SELECT 
                t.trip_short_name,
                t.trip_headsign,
                t.service_id,
                r.route_short_name,
                st.stop_code,
                tp.stop_name,
                TIME_FORMAT(st.arrival_time, '%H:%i') AS arrival_time
            FROM stop_times st
            INNER JOIN trips t ON st.trip_id = t.trip_id
            INNER JOIN routes r ON t.route_id = r.route_id
            INNER JOIN stops tp ON st.stop_code = tp.stop_code
            WHERE 1=1 $filtro_sql
            ORDER BY arrival_time ASC";

    $result = mysqli_query($conexao, $sql);

    // Definir nome do ponto
    if ($result && mysqli_num_rows($result) > 0) {
        $row_first = mysqli_fetch_assoc($result);

        if (!empty($box)) {
            $nome_pt = $row_first['stop_name'];
        } elseif (!empty($pesquisa)) {
            $nome_pt = $row_first['stop_name'];
        } else {
            $nome_pt = $row_first['stop_name'];
        }

        mysqli_data_seek($result, 0);
    }
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
    <link rel="stylesheet" href="../css/queries.css?v=1.3">
</head>

<body>
    <div>
        <header>
            <h1>Consultas</h1>
        </header>
        <main>
            <section class="scroll-area">
                <a href="timetable.php" class="a-tab-hor">TABELA DE HORÁRIOS</a>
                <br><br>

                <!-- Formulário -->
                <form method="GET">
                    <label>Por ponto: </label>
                    <input name="buscar" class="impt-buscar" value="<?php if (isset($_GET['buscar'])) echo $_GET['buscar']; ?>" placeholder="Pesquise por código" type="text">
                    <button type="submit" class="btn-buscar">PESQUISAR</button>

                    <Label>Por terminal: </Label>
                    <select name="terminal" id="selc-term" class="selc-term">
                        <option value="">Selecione um terminal</option>
                        <?php
                        $sql_select = "SELECT DISTINCT parent_station FROM stops WHERE parent_station <> '' ORDER BY parent_station ASC";
                        $result_selec = mysqli_query($conexao, $sql_select);
                        while ($dados = mysqli_fetch_array($result_selec)) {
                            $terminal = $dados['parent_station'];
                            echo "<option value=\"$terminal\">$terminal</option>";
                        }
                        ?>
                    </select>

                    <select name="box" id="selc-box" class="selc-box">
                        <option value="">Selecione um Box</option>
                    </select>

                    <button type="submit" class="btn-selec">SELECIONAR</button>
                    <br>

                    <label for="id-serv">Dia da Semana</label>
                    <br>
                    <select name="servico" class="selc-serv" id="id-serv" onchange="this.form.submit()">
                        <option value="Segunda a Sexta" <?php if ($servico == "Segunda a Sexta") echo "selected"; ?>>Segunda a Sexta</option>
                        <option value="Sábado" <?php if ($servico == "Sábado") echo "selected"; ?>>Sábado</option>
                        <option value="Domingo e Feriado" <?php if ($servico == "Domingo e Feriado") echo "selected"; ?>>Domingo e Feriado</option>
                    </select>
                </form>

                <br>
                <hr>
                <br>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <table>
                        <caption><?php echo $nome_pt; ?></caption>
                        <thead>
                            <th class="th-linh">Linha</th>
                            <th class="th-dest">Destino</th>
                            <th class="th-hor">Horário</th>
                            <th class="th-serv">Serviço</th>
                        </thead>
                        <tbody>
                            <?php while ($sql_result = mysqli_fetch_array($result)): ?>
                                <tr>
                                    <td class="td-linh"><?php echo $sql_result['route_short_name'] ?></td>
                                    <td class="td-dest"><?php echo $sql_result['trip_headsign'] ?></td>
                                    <td class="td-hor"><?php echo $sql_result['arrival_time'] ?></td>
                                    <td class="td-serv"><?php echo $sql_result['service_id'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <br>
            </section>
        </main>

        <footer>
            <p><a href="../index.html">< Voltar</a></p>
        </footer>
    </div>

    <!-- JS carregar box -->
    <script>
        document.getElementById("selc-term").addEventListener("change", function() {
            let terminalSelecionado = this.value;
            let selectBox = document.getElementById("selc-box");

            if (!terminalSelecionado) {
                selectBox.innerHTML = '<option value="">Selecione um Box</option>';
                return;
            }

            fetch("buscar_box.php?terminal=" + encodeURIComponent(terminalSelecionado))
                .then(response => response.json())
                .then(data => {
                    selectBox.innerHTML = '<option value="">Selecione um Box</option>';
                    data.forEach(function(box) {
                        let option = document.createElement("option");
                        option.value = box.nome;
                        option.textContent = box.nome;
                        selectBox.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error("Erro ao buscar Box:", error);
                });
        });
    </script>
</body>

</html>
