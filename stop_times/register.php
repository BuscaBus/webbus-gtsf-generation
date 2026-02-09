<?php
include("../connection.php");

// Declaração da variavel para receber o ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Erro: ID não informado ou inválido.");
}

$id = (int) $_GET['id'];

// Consulta o ID no banco de dados
$sql = "SELECT route_id, trip_id, service_id, trip_headsign, trip_short_name FROM trips WHERE trip_id = $id";
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
    <link rel="stylesheet" href="../css/stop_times.css?v=1.5">
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
                        <label for="id-serv" class="lb-reg-serv">Serviço:</label>
                        <input type="text" name="servico" class="inpt-reg-serv" id="id-serv" value="<?= $result_id['service_id'] ?>" disabled>
                    </p>
                    <p class="p-estilo">
                        <label for="id-viag" class="lb-reg-viag">Viagem:</label>
                        <input type="text" name="viagem" class="inpt-reg-viag" id="id-viag" value="<?= $result_id['trip_short_name'] ?> - <?= $result_id['trip_headsign'] ?>" disabled>
                    </p>                    
                    <p class="p-estilo">
                        <label for="id-dest" class="lb-reg-dest">Destino:</label>
                        <input type="text" name="destino" class="inpt-reg-dest" id="id-dest" value="<?= $result_id['trip_headsign'] ?>">
                    </p>
                    <p class="p-estilo">
                        <label for="id-ponto" class="lb-reg-ponto">Ponto:</label>
                        <input type="text" name="ponto" class="inpt-reg-ponto" id="id-ponto" pattern="\d{5}" minlength="5" maxlength="5" placeholder="insira o código do ponto..." required>
                    </p>
                    <p class="p-estilo">
                        <label for="id-hrInc" class="lb-reg-hrInc">Hora Inicio:</label>
                        <input type="time" name="hora_inicio" class="inpt-reg-hrInc" id="id-hrInc">
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
                    <h3>Viagem:  <?= $result_id['trip_short_name'] ?> - <?= $result_id['trip_headsign'] ?> </h3>
                    <br>
                    <hr>
                    <br>
                    <thead>
                        <th class="th-hor">Horário</th>
                        <th class="th-destino">Destino</th>                        
                        <th class="th-acoes">Ações</th>
                    </thead>
                    <?php
                    // Consulta no banco de dados para exibir na tabela de viagens 
                    $sql = "SELECT time_id, trip_id, TIME_FORMAT(arrival_time, '%H:%i') AS arrival_time, stop_headsign
                            FROM stop_times WHERE trip_id = $id ORDER BY arrival_time ASC";
                    $result = mysqli_query($conexao, $sql);

                    while ($sql_result = mysqli_fetch_array($result)) {
                        $id = $sql_result['time_id'];
                        $trip_id = $sql_result['trip_id'];
                        $hr_inicio = $sql_result['arrival_time'];
                        $destino = $sql_result['stop_headsign'];                                               
                    ?>
                        <tbody>
                            <tr>
                                <td><?php echo $hr_inicio ?></td>
                                <td><?php echo $destino ?></td>                                                                
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
            <p><a href="../trips/register.php?id=<?= $result_id['route_id'] ?>"> < Voltar</a>
            </p>
        </footer>
    </div>
</body>

</html>