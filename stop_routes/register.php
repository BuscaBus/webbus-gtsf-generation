<?php
include("../connection.php");

// Declaração da variavel para receber o ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Erro: ID não informado ou inválido.");
}

$id = (int) $_GET['id'];

// Consulta a viagem atual
$sql = "SELECT route_id, trip_id, service_id, trip_headsign, trip_short_name 
        FROM trips WHERE trip_id = $id";
$result = mysqli_query($conexao, $sql);
$result_id = mysqli_fetch_assoc($result);

// Definindo route_id antes de usar na query das viagens
$route_id = $result_id['route_id'];

// Buscar todas as viagens dessa mesma rota sem nomes repetidos
$sql_viagens = "SELECT DISTINCT trip_short_name, trip_headsign, MIN(trip_id) as trip_id
                FROM trips 
                WHERE route_id = $route_id 
                GROUP BY trip_short_name, trip_headsign
                ORDER BY trip_short_name ASC";
$res_viagens = mysqli_query($conexao, $sql_viagens);

?>

<!--Script para confirmar a exclusão-->
<script>
    function deletar() {
        if (confirm("Deseja excluir esse item?"))
            document.forms[0].submit();
        else
            return false
    }
</script>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Sistema WebBus</title>
    <link rel="shortcut icon" href="../img/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/stop_routes.css?v=1.4">
</head>

<body>
    <div>
        <header>
            <h1>Trajetos</h1>
        </header>
        <main class="main-cont">
            <!-- Section para cadastrar horários -->
            <section class="sect-reg-pont">
                <h1 class="h1-cad-pont">Cadastrar Trajeto</h1>
                <br>
                <form action="result_register.php" method="POST" autocomplete="off" class="form-cad-vig">
                    <input type="hidden" name="route_id" value="<?= $result_id['route_id'] ?>">

                    <p class="p-estilo">
                        <label for="id-viag" class="lb-reg-viag">Viagem:</label>
                        <select name="viagem" id="id-viag" class="selc-reg-viag">
                            <?php while ($viag = mysqli_fetch_assoc($res_viagens)) { ?>
                                <option value="<?= $viag['trip_id'] ?>"
                                    <?= ($viag['trip_id'] == $result_id['trip_id']) ? 'selected' : '' ?>>
                                    <?= $viag['trip_short_name'] ?> - <?= $viag['trip_headsign'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </p>

                    <p class="p-estilo">
                        <label for="id-sequ" class="lb-reg-sequ">Sequência:</label>
                        <input type="text" name="sequencia" class="inpt-reg-sequ" id="id-sequ" placeholder="insira a numeração da sequência...">
                    </p>

                    <p class="p-estilo">
                        <label for="id-pont" class="lb-reg-pont">Ponto:</label>
                        <input type="text" name="ponto" class="inpt-reg-pont" id="id-pont" pattern="\d{5}" minlength="5" maxlength="5" placeholder="insira o código do ponto...">
                    </p>

                    <br>
                    <nav class="nav-reg-btn">
                        <p><button class="btn-reg-cad">CADASTRAR</button></p>
                        <p>
                            <button class="btn-reg-canc">
                                <a href="../trips/register.php?id=<?= $result_id['route_id'] ?>" class="a-btn-canc">CANCELAR</a>
                            </button>
                        </p>
                    </nav>
                </form>
            </section>

            <!-- Section para listar os pontos do trajeto -->
            <section class="sect-list-hor">
                <br>
                <table>
                    <h3 id="h3-viagem">Viagem: <?= $result_id['trip_short_name'] ?> - <?= $result_id['trip_headsign'] ?></h3>
                    <br>
                    <hr>
                    <br>
                    <thead>
                        <th class="th-sequ">Sequ</th>
                        <th class="th-cod">Código</th>
                        <th class="th-ponto">Ponto</th>
                        <th class="th-bairr">Bairro</th>
                        <th class="th-cida">Cidade</th>
                        <th class="th-acoes">Ação</th>
                    </thead>
                    <?php
                    // Consulta no banco de dados para exibir na tabela do trajeto 
                    $sql = "SELECT sr.stop_route_id,
                                   sr.trip_id,
                                   sr.stop_sequence,
                                   sr.stop_code,
                                   s.stop_name,
                                   s.stop_district,
                                   s.stop_city
                            FROM stop_routes sr
                            INNER JOIN stops s ON sr.stop_code = s.stop_code
                            WHERE sr.trip_id = $id
                            ORDER BY sr.stop_sequence ASC";

                    $result = mysqli_query($conexao, $sql);

                    while ($sql_result = mysqli_fetch_array($result)) {
                        $id = $sql_result['stop_route_id'];
                        $trip_id = $sql_result['trip_id'];
                        $sequencia = $sql_result['stop_sequence'];
                        $codigo = $sql_result['stop_code'];
                        $ponto = $sql_result['stop_name'];
                        $bairro = $sql_result['stop_district'];
                        $cidade = $sql_result['stop_city'];
                    ?>
                        <tbody>
                            <tr>
                                <td><?php echo $sequencia ?></td>
                                <td><?php echo $codigo ?></td>
                                <td><?php echo $ponto ?></td>
                                <td><?php echo $bairro ?></td>
                                <td><?php echo $cidade ?></td>
                                <td>
                                    <form action="delete.php" method="POST">
                                        <input type="hidden" name="id" value="<?php echo $id ?>">
                                        <input type="hidden" name="trip-id" value="<?php echo $trip_id ?>">
                                        <button class="btn-excluir" onclick="return deletar()">EXCLUIR</button>
                                    </form>
                                </td>
                            </tr>
                        <?php }; ?>
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

    <!-- JS para atulizar a viagem na lista de trajeto -->
    <script>
        const selectViagem = document.getElementById('id-viag');

        // Recarrega a página ao selecionar uma nova viagem
        selectViagem.addEventListener('change', function() {
            const novoTripId = selectViagem.value;
            window.location.href = "?id=" + novoTripId;
        });
    </script>


</body>

</html>