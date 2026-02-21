<?php
include("../connection.php");

/*$lat = $_GET['latitude'];
$lng = $_GET['longitude'];*/

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de pontos</title>
    <link rel="stylesheet" href="../css/stops.css?v=1.3">
</head>

<body>
    <section>
        <h1>Cadastrar pontos</h1>
        <form action="result_register.php" method="POST" autocomplete="off">
            <hr>
            <p class="p-estilo">
                <label for="id-cod" class="lb-reg-cod">Código:</label>
                <input type="text" name="codigo" class="inpt-reg-cod" id="id-cod" pattern="\d{5}" minlength="5" maxlength="5" placeholder="insira o código..." required>
            </p>
            <p class="p-estilo">
                <label for="id-pont" class="lb-reg-pont">Ponto:</label>
                <input type="text" name="ponto" class="inpt-reg-pont" id="id-pont" placeholder="insira o endereço..." required>
            </p>
            <p class="p-estilo">
                <label for="id-desc" class="lb-reg-desc">Descrição:</label>
                <input type="text" name="descricao" class="inpt-reg-desc" id="id-desc" placeholder="insira uma descrição...">
            </p>
            <p class="p-estilo">
                <label for="id-loc" class="lb-reg-loc">Tipo de local:</label>
                <select name="tp-local" class="selc-reg-loc" id="id-loc">
                    <option value="select">Selecione um tipo de local</option>
                    <option value="0">Parada (Ponto)</option>
                    <option value="1">Estação (Terminal)</option>
                    <option value="2">Entrada/Saída</option>
                    <option value="3">Nó Genérico</option>
                    <option value="4">Box da plataforma</option>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-term" class="lb-reg-term">Terminal:</label>
                <select name="terminal" id="id-term" class="selc-reg-term">
                    <option>Selecione um terminal</option>;
                    <?php
                    $sql_select = "SELECT stop_id, stop_name FROM stops WHERE  parent_station IS NOT NULL AND parent_station <> '' ORDER BY stop_name ASC";
                    $result_selec = mysqli_query($conexao, $sql_select);

                    while ($dados = mysqli_fetch_array($result_selec)) {
                        $id = $dados['stop_id'];
                        $nome = $dados['stop_name'];
                        echo "<option value='$id'>$nome</option>";
                    }
                    ?>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-box" class="lb-reg-box">Box:</label>
                <input type="text" name="box" class="inpt-reg-box" id="id-box" placeholder="insira o Box...">
            </p>
            <p class="p-estilo">
                <label for="id-lat" class="lb-reg-lat">Latitude:</label>
                <input type="text" name="latitude" class="inpt-reg-lat" id="id-lat"
                    value="<?php echo isset($_GET['latitude']) ? htmlspecialchars($_GET['latitude']) : ''; ?>" readonly>
            </p>
            <p class="p-estilo">
                <label for="id-lng" class="lb-reg-lng">Longitude:</label>
                <input type="text" name="longitude" class="inpt-reg-lng" id="id-lng"
                    value="<?php echo isset($_GET['longitude']) ? htmlspecialchars($_GET['longitude']) : ''; ?>" readonly>
            </p>

            <hr>
            <nav class="nav-reg-btn">
                <p>
                    <Button class="btn-reg-cad">CADASTRAR</Button>
                </p>
                <p>
                    <Button class="btn-reg-canc">
                        <a href="list.php" class="a-btn-canc">CANCELAR</a>
                    </Button>
                </p>
            </nav>

        </form>
    </section>
</body>

</html>