<?php
    require_once __DIR__ . "/../connection.php";

    // Declaração da variavel para receber o ID
    $pattern_id = (int) $_GET['pattern_id'];
    
    // Consulta o ID no banco de dados
    $sql = "
            SELECT
                *,
                CASE
                    WHEN direction_id = 0 THEN 'Ida'
                    WHEN direction_id = 1 THEN 'Volta'
                END AS direction_format

            FROM trip_patterns

            WHERE pattern_id = ?
        ";
    
    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar consulta: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $pattern_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $result_id = mysqli_fetch_assoc($result);

    if (!$result_id) {
        die("Padrão de viagem não encontrado.");
    }
  
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
    <title>Editar viagem</title>
    <link rel="shortcut icon" href="../img/logo-icon2.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/trips.css?v=1.4">  
</head>

<body>
    <section id="section-iframe">
        <h1>Editar viagem</h1>
        <form action="edit_result.php" method="POST" autocomplete="off">
            <hr>
            <input type="hidden" name="id_route" value="<?=$result_id['route_id']?>">
            <input type="hidden" name="pattern_id" value="<?= $result_id['pattern_id'] ?>">
            <p class="p-estilo">
                <label for="id-edt-origem" class="lb-edt-origem">Origem:</label>
                <input type="text" name="origem" class="inpt-edt-origem" id="id-edt-origem" value="<?=$result_id['trip_short_name']?>">
            </p>
            <p class="p-estilo">
                <label for="id-edt-destino" class="lb-edt-destino">Destino:</label>
                <input type="text" name="destino" class="inpt-edt-destino" id="id-edt-destino" value="<?=$result_id['trip_headsign']?>" required>            
            </p>
            <p class="p-estilo">
                <label for="id-edt-sentido" class="lb-edt-sentido">Sentido:</label>
                <select name="sentido" class="selec-edt-sentido" id="id-edt-sentido">
                    <option value=""><?=$result_id['direction_format']?></option>                                                         
                </select>                           
            </p>
            <p class="p-estilo">
                <label for="id-edt-partida" class="lb-edt-partida">Local de Partida:</label>
                <input type="text" name="partida" class="inpt-edt-partida" id="id-edt-partida" value="<?=$result_id['departure_location']?>" required>            
            </p>
            <br>            
            <hr>
            <nav class="nav-edt-btn">
                <p>
                    <Button class="btn-edt" onclick="return editar()">EDITAR</Button>
                </p>
                <p>
                    <Button class="btn-edt-canc">
                        <a href="register.php?id=<?= $result_id['route_id'] ?>" class="a-btn-canc">CANCELAR</a> 
                    </Button>
                </p>
            </nav>
        </form>
    </section>
</body>

</html>