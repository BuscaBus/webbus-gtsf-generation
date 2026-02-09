<?php
include("../connection.php");

// Declaração da variavel para receber o ID
$id = $_GET['id'];

// Consulta o ID no banco de dados
$sql = "SELECT * FROM trips WHERE trip_id = $id";
$result = mysqli_query($conexao, $sql);

// Variavel que recebe o ID do banco de dados    
$result_id = mysqli_fetch_assoc($result);

mysqli_close($conexao);

?>
<!--Script para confirmar a edição-->
<script>
    function editar() {
        return confirm("Tem certeza que deseja salvar as alterações?");
    }
</script>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema WebBus</title>
    <link rel="stylesheet" href="../css/trips.css?v=1.1">
</head>

<body>
    <section class="sect-edt">
        <br>
        <h1>Editar viagem</h1>
        <hr>
        <form action="edit_result.php" method="POST" autocomplete="off">
            <input type="hidden" name="id" class="inpt1" id="id-trip" value="<?= $result_id['trip_id'] ?>">
            <input type="hidden" name="id-route" class="inpt1" id="id-route" value="<?= $result_id['route_id'] ?>">
            <p>
                <label for="id-serv" class="lb-edt-serv">Serviço:</label>
                <input type="text" name="servico" class="inpt-edt-serv" id="id-serv" value="<?= $result_id['service_id'] ?>" disabled>
            </p>
            <p>
                <label for="id-sent" class="lb-edt-sent">Sentido:</label>
                <input type="text" name="sentido" class="inpt-edt-sent" id="id-sent" value="<?= $result_id['direction_id'] ?>" disabled>
            </p>                 
            <p>
                <label for="id-orig" class="lb-edt-orig">Origem:</label>
                <input type="text" name="origem" class="inpt-edt-orig" id="id-org" value="<?= $result_id['trip_short_name'] ?>">
            </p>
            <p>
                <label for="id-dest" class="lb-edt-dest">Destino:</label>
                <input type="text" name="destino" class="inpt-edt-dest" id="id-dest" value="<?= $result_id['trip_headsign'] ?>">
            </p>
            <p>
                <label for="id-part" class="lb-edt-part">Local de Partida:</label>
                <input type="text" name="partida" class="inpt-edt-part" id="id-part" value="<?= $result_id['departure_location'] ?>">
            </p>
            <br>
            <hr>
            <nav class="nav-edt-btn">
                <p>
                    <button class="btn-edt">EDITAR</button>
                </p>
                <p>
                    <button class="btn-edt-canc">
                        <a href="register.php?id=<?= $result_id['route_id'] ?>" class="a-btn-canc">CANCELAR</a>
                    </button>
                </p>
            </nav>
        </form>
    </section>
</body>

</html>