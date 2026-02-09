<?php
    include("../connection.php");

    // Declaração da variavel para receber o ID
    $id = $_GET['id'];
    
    // Consulta o ID no banco de dados
    $sql = "SELECT 
            routes.*,
            fare_attributes.price
        FROM 
            routes
        JOIN 
            fare_attributes ON fare_attributes.fare_id = routes.route_group
        WHERE 
            routes.route_id = $id";
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
    <link rel="stylesheet" href="../css/route.css?v=1.2">
    
</head>
<body>
    <section id="section-iframe">
        <h1>Editar linha</h1>
        <hr>
        <form action="edit_result.php" method="POST" autocomplete="off">
            <input type="hidden" name="id" class="inpt1" id="id-nome" value="<?=$result_id['route_id']?>">            
            <p class="p-estilo">
                <label for="id-cod" class="lb-edt-cod">Código:</label>
                <input type="text" name="codigo" class="inpt-edt" id="id-cod" value="<?=$result_id['route_short_name']?>" disabled>
            </p>
            <p class="p-estilo">
                <label for="id-linha" class="lb-edt-linha">Linha:</label>
                <input type="text" name="linha" class="inpt-edt" id="id-linha" value="<?=$result_id['route_long_name']?>">
            </p>
            <p class="p-estilo">
                <label for="id-desc" class="lb-edt-desc">Descrição:</label>
                <textarea name="descricao" id="id-desc" class="txt-edt"><?=$result_id['route_desc']?></textarea>
            </p>
            <p class="p-estilo">
                <label for="id-grp" class="lb-edt-grup">Grupo:</label>
                <select name="tipo" id="id-grp" class="selc-edt">
                    <?php
                        // fare_id atualmente salvo na tabela routes (em route_group)
                        $grupo_salvo_id = $result_id['route_group']; 

                        // Buscar todos os grupos disponíveis
                        $sql_select = "SELECT fare_id, route_group FROM fare_attributes ORDER BY route_group ASC";
                        $result_selec = mysqli_query($conexao, $sql_select);

                        while($dados = mysqli_fetch_array($result_selec)) {
                            $fare_id = $dados['fare_id'];
                            $nome_grupo = htmlspecialchars($dados['route_group']); // protege caracteres especiais

                            // Se o fare_id atual for o mesmo que está salvo, marca como selected
                            $selected = ($fare_id == $grupo_salvo_id) ? 'selected' : '';

                            echo "<option value='$fare_id' $selected>$nome_grupo</option>";
                        }
                    ?>       
                </select>         
            </p>                   
            <p class="p-estilo">
                <label for="id-tarifa" class="lb-edt-tarifa">Tarifa:</label>
                <select name="tarifa" id="id-tarifa" class="selc-edt" disabled>                   
                        <?php
                            // Converte o valor da tarifa salva para número com 2 casas decimais, usando ponto como separador decimal 
                            $tarifa_salva_valor = number_format((float)$result_id['price'], 2, '.', '');
                           
                            // Consulta todas as tarifas disponíveis na tabela fare_attributes, ordenadas pelo valor da tarifa (price)
                            $sql_select = "SELECT fare_id, price, FORMAT(price, 2) AS price_format FROM fare_attributes ORDER BY price ASC";
                            $result_selec = mysqli_query($conexao, $sql_select);

                            while($dados = mysqli_fetch_array($result_selec)) {
                                $fare_id = $dados['fare_id'];
                                $preco_raw = number_format((float)$dados['price'], 2, '.', '');
                                $tarifa = htmlspecialchars($dados['price_format']);
                                $selected = ($preco_raw == $tarifa_salva_valor) ? 'selected' : '';

                                // Exibe a opção do select com o valor da tarifa e, se for a tarifa atual, marcada como selecionada
                                echo "<option value='$preco_raw' $selected>R$ $tarifa</option>";
                            }
                        ?>
                    </select>
            </p>
            <?php $status_salvo = $result_id['route_status']; ?>
            <p class="p-estilo">
                <label for="id-status" class="lb-edt-status">Status:</label>

                <input type="radio" name="status" id="id-status-a" value="A" <?= ($status_salvo === 'A') ? 'checked' : '' ?>>
                <label for="id-status-a">Ativa</label>

                <input type="radio" name="status" id="id-status-i" value="I" <?= ($status_salvo === 'I') ? 'checked' : '' ?>>
                <label for="id-status-i">Inativa</label>
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
<?php mysqli_close($conexao); ?>
</html>