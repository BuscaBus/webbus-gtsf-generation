<?php
include("../connection.php");

$lat = $_GET['latitude'];
$lng = $_GET['longitude'];

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de pontos</title>
    <link rel="stylesheet" href="../css/stops.css?v=1.1">
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
                <label for="id-cid" class="lb-reg-cid">Cidade:</label>
                <select name="cidade" id="selc-cid" class="selc-reg-cid" required>
                    <option value="">Selecione uma cidade</option>
                    <?php
                    $sql_select = "SELECT DISTINCT city FROM city_district ORDER BY city ASC";
                    $result_selec = mysqli_query($conexao, $sql_select);

                    while ($dados = mysqli_fetch_array($result_selec)) {
                        $id = $dados['district_id'];
                        $cidade = $dados['city'];
                        echo "<option value=\"$cidade\">$cidade</option>";
                    }
                    ?>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-bair" class="lb-reg-bair">Bairro:</label>
                <select name="bairro" id="selc-bair" class="selc-reg-bair" required>
                    <option value="">Selecione um bairro</option>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-loc" class="lb-reg-loc">Tipo de local:</label>
                <select name="local" class="selc-reg-loc" id="id-loc">
                    <option value="select">Selecione um tipo de local</option>
                    <option value="0">Ponto</option>
                    <option value="1">Terminal</option>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-term" class="lb-reg-term">Terminal:</label>
                <select name="terminal" class="selc-reg-term" id="id-term">
                    <option value="">Selecione um terminal</option>
                    <option value="TICEN - Terminal de Integração do Centro">TICEN - Terminal de Integração do Centro</option>
                    <option value="TITRI - Terminal de Integração da Trindade">TITRI - Terminal de Integração da Trindade</option>
                    <option value="TIRIO - Terminal de Integração do Rio Tavares">TIRIO - Terminal de Integração do Rio Tavares</option>
                    <option value="TILAG - Terminal de Integração da Lagoa">TILAG - Terminal de Integração da Lagoa</option>
                    <option value="TISAN - Terminal de Integração de Santo Antônio">TISAN - Terminal de Integração de Santo Antônio</option>
                    <option value="TICAN - Terminal de Integração de Canasvieiras">TICAN - Terminal de Integração de Canasvieiras</option>
                    <option value="TECIF - Terminal Cidade de Florianópolis">TECIF - Terminal Cidade de Florianópolis</option>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-box" class="lb-reg-box">Box:</label>
                <input type="text" name="box" class="inpt-reg-box" id="id-box" placeholder="insira o Box...">
            </p>
            <p class="p-estilo">
                <label for="id-lat" class="lb-reg-lat">Latitude:</label>
                <input type="text" name="latitude" class="inpt-reg-lat" id="id-lat"
                    value="<?php echo isset($_GET['latitude']) ? htmlspecialchars($_GET['latitude']) : ''; ?>">
            </p>
            <p class="p-estilo">
                <label for="id-lng" class="lb-reg-lng">Longitude:</label>
                <input type="text" name="longitude" class="inpt-reg-lng" id="id-lng"
                    value="<?php echo isset($_GET['longitude']) ? htmlspecialchars($_GET['longitude']) : ''; ?>">
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
    <!-- JS para tratar o bairro com base na cidade escolhida-->
    <script>
        document.getElementById("selc-cid").addEventListener("change", function() {
            let cidadeSelecionada = this.value;
            let selectBairro = document.getElementById("selc-bair");

            if (!cidadeSelecionada) {
                selectBairro.innerHTML = '<option value="">Selecione um bairro</option>';
                return;
            }

            fetch("buscar_bairros.php?cidade=" + encodeURIComponent(cidadeSelecionada))
                .then(response => response.json())
                .then(data => {
                    selectBairro.innerHTML = '<option value="">Selecione um bairro</option>';
                    data.forEach(function(bairro) {
                        let option = document.createElement("option");
                        option.value = bairro.nome; // <-- salva o nome do bairro
                        option.textContent = bairro.nome;
                        selectBairro.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error("Erro ao buscar bairros:", error);
                });
        });
    </script>

</body>

</html>