<?php
include("../connection.php");

// Declaração da variavel para receber o ID
$id = $_GET['id'];

// Consulta o ID no banco de dados
$sql = "SELECT *,
            routes.route_type,
            CASE 
                WHEN routes.route_type = '0' THEN 'Bonde, VLT'
                WHEN routes.route_type = '1' THEN 'Metrô'
                WHEN routes.route_type = '2' THEN 'Trem'
                WHEN routes.route_type = '3' THEN 'Ônibus'                    
            END AS status_format
        FROM routes 
        WHERE routes.route_id = $id";
$result = mysqli_query($conexao, $sql);

// Variavel que recebe o ID do banco de dados    
$result_id = mysqli_fetch_assoc($result);

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
    <title>Editar linha</title>
    <link rel="stylesheet" href="../css/route.css?v=1.6">

</head>

<body>
    <section id="section-iframe">
        <h1>Editar linha</h1>
        <hr>
        <form action="edit_result.php" method="POST" autocomplete="off">
            <input type="hidden" name="id" class="inpt1" id="id-nome" value="<?= $result_id['route_id'] ?>">
            <p class="p-estilo">
                <label for="id-cod" class="lb-edt-cod">Código:</label>
                <input type="text" name="codigo" class="inpt-edt-cod" id="id-cod" value="<?= $result_id['route_short_name'] ?>" disabled>
            </p>
            <p class="p-estilo">
                <label for="id-linha" class="lb-edt-linha">Linha:</label>
                <input type="text" name="linha" class="inpt-edt-linha" id="id-linha" value="<?= $result_id['route_long_name'] ?>">
            </p>
            <p class="p-estilo">
                <label for="id-desc" class="lb-edt-desc">Descrição:</label>
                <textarea name="descricao" id="id-desc" class="txt-edt-desc"><?= $result_id['route_desc'] ?></textarea>
            </p>
            <p class="p-estilo">
                <label for="id-tipo" class="lb-edt-tipo">Tipo:</label>
                <input type="text" name="tipo" class="inpt-edt-tipo" id="id-edt-tipo" value="<?= $result_id['status_format'] ?>" disabled>
            </p>
            <p class="p-estilo">
                <label for="id-cor" class="lb-edt-cor">Cor da linha:</label>
                <input type="color" name="cor-linha" class="inpt-edt-cor" id="id-cor-linha" value="<?= $result_id['route_color'] ?>">
            </p>
            <p class="p-estilo">
                <label for="id-cor" class="lb-edt-cor">Cor do texto:</label>
                <input type="color" name="cor-texto" class="inpt-edt-cor" id="id-cor-texto" value="<?= $result_id['route_text_color'] ?>">
            </p>
            <p class="p-estilo">
                <label for="id-ordem" class="lb-edt-ordem">Ordem:</label>
                <input type="text" name="ordem" class="inpt-edt-ordem" id="id-edt-ordem" value="<?= $result_id['route_sort_order'] ?>">
            </p>
            <p class="p-estilo">
                <label for="id-grupo" class="lb-edt-grupo">Grupo:</label>
                <input type="text" name="grupo" class="inpt-edt-grupo" id="id-edt-grupo" value="<?= $result_id['network_id'] ?>">
            </p>
            <p class="p-estilo">
                <label for="id-status" class="lb-edt-status">Status:</label>

                <input type="radio" name="status" id="id-status-a" value="A"
                    <?= ($result_id['route_status'] === 'A') ? 'checked' : '' ?>>
                <label for="id-status-a">Ativa</label>

                <input type="radio" name="status" id="id-status-i" value="I"
                    <?= ($result_id['route_status'] === 'I') ? 'checked' : '' ?>>
                <label for="id-status-i">Inativa</label>
            </p>
            <hr>
            <nav class="nav-edt-btn">
                <p>
                    <Button type="submit" class="btn-edt" onclick="return editar()">EDITAR</Button>
                </p>
                <p>
                    <Button type="button" class="btn-edt-canc">
                        <a href="list.php" class="a-btn-canc">CANCELAR</a>
                    </Button>
                </p>
            </nav>
        </form>
    </section>
</body>
<?php mysqli_close($conexao); ?>

</html>