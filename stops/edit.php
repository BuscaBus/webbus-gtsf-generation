<?php
include("../connection.php");

    // Declaração da variavel para receber o ID
    $id = $_GET['id'];
    
    // Consulta o ID no banco de dados
    $sql = "SELECT *, 
            CASE 
                WHEN stops.location_type = '0' THEN 'Ponto'
                WHEN stops.location_type = '1' THEN 'Terminal'                    
                END AS status_format
                FROM stops WHERE stop_id = $id";
    $result = mysqli_query($conexao, $sql);

    $result_id = mysqli_fetch_assoc($result);
  
    mysqli_close($conexao);
    
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar pontos</title>
    <link rel="stylesheet" href="../css/stops.css?v=1.4">
</head>

<body>
    <section>
        <h1>Editar pontos</h1>
        <hr>
        <form action="edit_result.php" method="POST" autocomplete="off">
            <input type="hidden" name="id" class="inpt-edt-id" id="id-nome" value="<?=$result_id['stop_id']?>">
            <p class="p-estilo">
                <label for="id-cod" class="lb-edt-cod">Código:</label>
                <input type="text" name="codigo" class="inpt-edt-cod" id="id-cod" value="<?=$result_id['stop_code']?>">
            </p>
            <p class="p-estilo">
                <label for="id-pont" class="lb-edt-pont">Ponto:</label>
                <input type="text" name="ponto" class="inpt-edt-pont" id="id-pont" value="<?=$result_id['stop_name']?>">
            </p>
            <p class="p-estilo">
                <label for="id-cid" class="lb-edt-cid">Cidade:</label>
                <input type="text" name="cidade" class="inpt-edt-cid" id="id-cid" value="<?=$result_id['stop_city']?>" disabled>
            </p>
            <p class="p-estilo">
                <label for="id-bair" class="lb-edt-bair">Bairro:</label>
                <input type="text" name="bairro" class="inpt-edt-bair" id="id-bair" value="<?=$result_id['stop_district']?>" disabled>
            </p>   
            <p class="p-estilo">
                <label for="id-loc" class="lb-edt-loc">Tipo de Local:</label>
                <input type="text" name="local" class="inpt-edt-loc" id="id-loc" value="<?=$result_id['status_format']?>" disabled>
            </p>
            <p class="p-estilo">
                <label for="id-term" class="lb-edt-term">Terminal:</label>
                <input type="text" name="terminal" class="inpt-edt-term" id="id-term" value="<?=$result_id['parent_station']?>" disabled>
            </p>            
            <p class="p-estilo">
                <label for="id-box" class="lb-reg-box">Box:</label>
                <input type="text" name="box" class="inpt-reg-box" id="id-box" value="<?=$result_id['platform_code']?>">
            </p>
            <hr>
            <nav class="nav-reg-btn">
                <p>
                    <button class="btn-edt">EDITAR</button>
                </p>
                <p>
                    <button class="btn-reg-canc">
                        <a href="list.php" class="a-btn-canc">CANCELAR</a>
                    </button>
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