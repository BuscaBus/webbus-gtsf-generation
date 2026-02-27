<?php
include("../connection.php");

// Serviço padrão (evita Undefined array key)
$servicoSelecionado = $_GET['servico'] ?? "Segunda a Sexta";

// Declaração da variavel para receber o ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Erro: ID não informado ou inválido.");
}

$id = (int) $_GET['id'];

// Consulta o ID no banco de dados
$sql = "SELECT * FROM routes WHERE route_id = $id";
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
    <link rel="stylesheet" href="../css/trips.css?v=1.6">
</head>

<body>
    <div>
        <header>
            <h1>Viagens</h1>
        </header>
        <main class="main-cont">

            <!-- Section para cadastrar novas viagens -->
            <section class="sect-reg-viag">
                <br>
                <h1 class="h1-cad-vig">Cadastrar viagens</h1>
                <br>
                <form action="result_register.php" method="POST" autocomplete="off" class="form-cad-vig">
                    <input type="hidden" name="id" class="inpt1" id="id-nome" value="<?= $result_id['route_id'] ?>">
                    <p class="p-estilo">
                        <label for="id-cod" class="lb-reg-cod">Código:</label>
                        <input type="text" name="codigo" class="inpt-reg-cod" id="id-cod" value="<?= $result_id['route_short_name'] ?>" disabled>
                    </p>
                    <p class="p-estilo">
                        <label for="id-linha" class="lb-reg-linha">Linha:</label>
                        <input type="text" name="linha" class="inpt-reg-linha" id="id-linha" value="<?= $result_id['route_long_name'] ?>" disabled>
                    </p>
                    <p class="p-estilo">
                        <label for="id-serv" class="lb-reg-serv">Serviço:</label>
                        <select name="servico" class="selc-reg-serv" id="id-serv">
                            <option value="">Selecione um serviço</option>
                            <?php
                            $sql_select = "SELECT service_id FROM calendar ORDER BY service_id DESC";
                            $result_selec = mysqli_query($conexao, $sql_select);

                            while ($dados = mysqli_fetch_array($result_selec)) {
                                $servicos = $dados['service_id'];
                                $selected = ($servicos == $result_id['service_id']) ? 'selected' : '';
                                echo "<option value='$servicos' $selected>$servicos</option>";
                            }
                            ?>
                        </select>
                    </p>
                    <p class="p-estilo">
                        <label for="id-sent" class="lb-reg-sent">Sentido:</label>
                        <select name="sentido" class="selc-reg-sent" id="id-sent">
                            <option value="select">Selecione um sentido</option>
                            <option value="0">Ida</option>
                            <option value="1">Volta</option>
                        </select>
                    </p>
                    <p class="p-estilo">
                        <label for="id-orig" class="lb-reg-orig">Origem:</label>
                        <input type="text" name="origem" class="inpt-reg-orig" id="id-org" placeholder="insira a origem da viagem...">
                    </p>
                    <p class="p-estilo">
                        <label for="id-dest" class="lb-reg-dest">Destino:</label>
                        <input type="text" name="destino" class="inpt-reg-dest" id="id-dest" placeholder="insira o destino da viagem...">
                    </p>
                    <p class="p-estilo">
                        <label for="id-part" class="lb-reg-part">Local de Partida:</label>
                        <input type="text" name="partida" class="inpt-reg-part" id="id-part" placeholder="insira o local de partida...">
                    </p>
                    <p class="p-estilo">
                        <label for="id-trac" class="lb-reg-trac">Traçado:</label>
                        <select name="tracado" class="selc-reg-trac" id="id-trac">
                            <option value="">Selecione um traçado</option>
                            <?php
                            $sql_select = "SELECT DISTINCT mt.shape_id FROM maps_trips mt WHERE mt.route_id = $id ORDER BY mt.shape_id ASC";

                            $result_selec = mysqli_query($conexao, $sql_select);

                            while ($dados = mysqli_fetch_assoc($result_selec)) {
                                $tracado = $dados['shape_id'];
                                echo "<option value='$tracado'>$tracado</option>";
                            }
                            ?>
                        </select>
                    </p>
                    <br>
                    <nav class="nav-reg-btn">
                        <p>
                            <button class="btn-reg-cad">CADASTRAR</button>
                        </p>
                        <p>
                            <button class="btn-reg-canc">
                                <a href="../route/list.php" class="a-btn-canc">CANCELAR</a>
                            </button>
                        </p>
                    </nav>
                </form>
            </section>

            <!-- Section para listar as viagens -->
            <section class="sect-list-viag">
                <br>
                <table>
                    <form method="GET">
                        <input type="hidden" name="id" value="<?= $id ?>">
                    </form>

                    <caption class="cap-list-vig">Relação de viagens</caption>
                    <thead>
                        <th class="th-viag">Viagem</th>
                        <th class="th-sent">Sentido</th>
                        <th class="th-part">Partida</th>
                        <th class="th-acoes">Ações</th>
                    </thead>
                    <?php
                    // Serviço padrão: Segunda a Sexta
                    if (!isset($_GET['servico']) || $_GET['servico'] == "") {
                        $_GET['servico'] = "Segunda a Sexta";
                    }

                    // Consulta no banco de dados para exibir na tabela de viagens 
                    $filtro_servico = "";

                    $filtro_servico = "";

                    if ($servicoSelecionado != "Todas") {
                        $servico = mysqli_real_escape_string($conexao, $servicoSelecionado);
                        $filtro_servico = "AND service_id = '$servico'";
                    }

                    $sql = "
                    SELECT 
                        MIN(trip_id) AS trip_id, route_id, trip_headsign, trip_short_name, direction_id, departure_location,
                        CASE 
                            WHEN direction_id = '0' THEN 'Ida'
                            WHEN direction_id = '1' THEN 'Volta'
                        END AS direction_format
                    FROM trips WHERE route_id = $id $filtro_servico
                    GROUP BY route_id, trip_headsign, trip_short_name, direction_id, departure_location
                    ORDER BY direction_id ASC";

                    $result = mysqli_query($conexao, $sql);

                    $first_trip_id = null; // salvar a primeira viagem

                    while ($sql_result = mysqli_fetch_array($result)) {
                        if ($first_trip_id === null) {
                            $first_trip_id = $sql_result['trip_id']; // guarda a primeira
                        }

                        $id_trip   = $sql_result['trip_id'];
                        $id_route  = $sql_result['route_id'];
                        $destino   = $sql_result['trip_headsign'];
                        $origem    = $sql_result['trip_short_name'];
                        $sentido   = $sql_result['direction_format'];
                        $partida   = $sql_result['departure_location'];
                    ?>
                        <tbody>
                            <tr>
                                <td><?= $origem ?> - <?= $destino ?></td>
                                <td><?= $sentido ?></td>
                                <td><?= $partida ?></td>
                                <td>
                                    <form action="delete.php" method="POST">
                                        <input type="hidden" name="id" value="<?= $id_trip ?>">
                                        <input type="hidden" name="id-route" value="<?= $id_route ?>">
                                        <a href="../stop_times/register.php?id=<?= $id_trip ?>" class="a-horario" id="a-hor">PARTIDAS</a>
                                        <a href="../stop_times/maps_trips.php?id=<?= $id_trip ?>" class="a-mapa" id="a-traj">MAPA</a>
                                        <a href="edit.php?id=<?= $id_trip ?>" class="a-editar" id="a-edit">EDITAR</a>
                                        <button class="btn-excluir" onclick="return deletar()">EXCLUIR</button>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    <?php } ?>
                </table>
                <br>
                <?php if ($first_trip_id): ?>
                    <form method="POST">
                        <a href="../stop_routes/register.php?id=<?= $first_trip_id ?>" class="a-trajeto" id="a-traj">PONTOS DO TRAJETO</a>
                    </form>
                <?php endif; ?>
            </section>

        </main>
        <footer>
            <p><a href="../route/list.php">
                    < Voltar</a>
            </p>
        </footer>
    </div>
</body>

</html>