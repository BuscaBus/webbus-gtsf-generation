<?php
    include("../connection.php");

    // Declaração da variavel para receber o ID
    $id = $_GET['id'];
    
    // Consulta o ID no banco de dados
    $sql = "SELECT *, FORMAT(price, 2) AS price_format, DATE_FORMAT(update_date, '%d/%m/%Y') AS data_format FROM fare_attributes WHERE fare_id = '$id'";
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
    <title>Cadastro de tarifas</title>
    <link rel="stylesheet" href="../css/fare_attributes.css?v=1.6"> 
</head>

<body>
    <section>
        <h1>Editar tarifa</h1>
        <form action="edit_result.php" method="POST" autocomplete="off">
            <hr>
            <p class="p-estilo">
                <label for="id-codigo" class="lb-edt-cod">Código:</label>
                <input type="text" name="codigo" class="inpt-edt-cod" id="id-codigo" value="<?=$result_id['fare_id']?>">
            </p>
            <p class="p-estilo">
                <label for="id-tarifa" class="lb-edt-tar">Tarifa:</label>
                <input type="text" name="tarifa" class="inpt-edt-tar" id="id-tarifa" value="<?=$result_id['price_format']?>">
            </p>
            <p class="p-estilo">
                <label for="id-tipo" class="lb-edt-tipo">Tipo:</label>
                <input type="text" name="tipo" class="inpt-edt-tipo" id="id-tipo" value="<?=$result_id['route_group']?>">
            </p>            
            <p class="p-estilo">
                <label for="id-data" class="lb-edt-data">Data de Atualização:</label>
                <input type="date" name="data" class="inpt-edt-data" id="id-data" value="<?=$result_id['update_date']?>">
            </p>
            <hr>
            <nav class="nav-edt-btn">
                <p>
                    <Button class="btn-edt" onclick="return editar()">EDITAR</Button>
                </p>
                <p>
                    <Button class="btn-edt-canc">
                        <a href="list.php" class="a-btn-canc">CANCELAR</a>
                    </Button>
                </p>
            </nav>

        </form>
    </section>
    
</body>

</html>