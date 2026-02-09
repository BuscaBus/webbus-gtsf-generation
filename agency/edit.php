<?php
    include("../connection.php");

    // Declaração da variavel para receber o ID
    $id = $_GET['id'];
    
    // Consulta o ID no banco de dados
    $sql = "SELECT * FROM agency WHERE agency_id = $id";
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
    <title>Cadastrar operadoras</title>
    <link rel="stylesheet" href="../css/agency.css?v=1.0">      
</head>

<body>
    <section>
        <h1>Editar operadora</h1>
        <hr>
        <form action="edit_result.php" method="POST" autocomplete="off">
            <input type="hidden" name="id" class="inpt-edt-id" id="id-nome" value="<?=$result_id['agency_id']?>">
            <p class="p-estilo">
                <label for="id-nome" class="lb-edt-op">Nome:</label>
                <input type="text" name="nome" class="inpt-edt-op" id="id-nome" value="<?=$result_id['agency_name']?>">
            </p>
            <p class="p-estilo">
                <label for="id-cid" class="lb-edt-cid">Cidade:</label>
                <input type="text" name="cidade" class="inpt-edt-cid" id="id-cid" value="<?=$result_id['agency_city']?>">                     
                </select>                
            </p>    
            <p class="p-estilo">
                <label for="id-url" class="lb-edt-site">Site:</label>
                <input type="text" name="url" class="inpt-edt-site" id="id-url" value="<?=$result_id['agency_url']?>">
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