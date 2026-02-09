<?php
include("../connection.php");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de linhas</title>
    <link rel="stylesheet" href="../css/route.css?v=1.7">
</head>

<body>
    <section>
        <h1>Cadastrar linha</h1>
        <form action="result_register.php" method="POST" autocomplete="off">
            <hr>
            <p class="p-estilo">
                <label for="id-grp" class="lb-reg-op">Operadora:</label>
                <select name="operadora" id="id-grp" class="selc-reg">
                    <option>Selecione uma operadora</option>;
                    <?php
                    $sql_select = "SELECT agency_id, agency_name FROM agency ORDER BY agency_name ASC";
                    $result_selec = mysqli_query($conexao, $sql_select);

                    while ($dados = mysqli_fetch_array($result_selec)) {
                        $id = $dados['agency_id'];
                        $operadoras = $dados['agency_name'];
                        echo "<option value='$id'>$operadoras</option>";
                    }
                    ?>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-cod" class="lb-reg-cod">Código:</label>
                <input type="text" name="codigo" class="inpt-reg" id="id-cod" placeholder="insira o código da linha..." required>
            </p>
            <p class="p-estilo">
                <label for="id-linha" class="lb-reg-linha">Linha:</label>
                <input type="text" name="linha" class="inpt-reg" id="id-linha" placeholder="insira o nome da linha..." required>
            </p>
            <p class="p-estilo">
                <label for="id-desc" class="lb-reg-desc">Descrição:</label>
                <textarea name="descricao" id="id-desc" class="txt-reg" placeholder="insira uma descrição..."></textarea>
            </p>
            <p class="p-estilo">
                <label for="id-grp" class="lb-reg-grup">Grupo:</label>
                <select name="tipo" id="select-grupo" class="selc-reg">
                    <option>Selecione um grupo de linha</option>;
                    <?php
                    $sql_select = "SELECT fare_id, route_group FROM fare_attributes ORDER BY route_group ASC";
                    $result_selec = mysqli_query($conexao, $sql_select);

                    while ($dados = mysqli_fetch_array($result_selec)) {
                        $id = $dados['fare_id'];
                        $tipo = $dados['route_group'];
                        echo "<option value='$id'>$tipo</option>";
                    }
                    ?>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-tarifa" class="lb-reg-tarifa">Tarifa:</label>
                <select name="tarifa" id="select-tarifa" class="selc-reg" disabled>
                    <option>Aguardando ...</option>;
                    <?php
                    $sql_select = "SELECT fare_id, price, FORMAT(fare_attributes.price, 2) AS price_format FROM fare_attributes  ORDER BY price ASC";
                    $result_selec = mysqli_query($conexao, $sql_select);

                    while ($dados = mysqli_fetch_array($result_selec)) {
                        $id = $dados['fare_id'];
                        $tarifa = $dados['price_format'];
                        echo "<option value='$tarifa'>R$ $tarifa</option>";
                    }
                    ?>
                </select>
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
<!-- JS para tratar a tarifa com base no tipo de linha escolhido-->
<script>
    document.getElementById('select-grupo').addEventListener('change', function() {
        const grupoId = this.value;
        const tarifaSelect = document.getElementById('select-tarifa');

        // Limpa o select de tarifa enquanto carrega
        tarifaSelect.innerHTML = '<option>Carregando...</option>';

        fetch('get_tarifas.php?fare_id=' + grupoId)
            .then(response => response.text())
            .then(data => {
                tarifaSelect.innerHTML = data;
            })
            .catch(error => {
                tarifaSelect.innerHTML = '<option>Erro ao carregar</option>';
                console.error('Erro:', error);
            });
    });
</script>