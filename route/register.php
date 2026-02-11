<?php
include("../connection.php");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de linhas</title>
    <link rel="stylesheet" href="../css/route.css?v=1.8">
</head>

<body>
    <section>
        <h1>Cadastrar linha</h1>
        <form action="result_register.php" method="POST" autocomplete="off">
            <hr>
            <p class="p-estilo">
                <label for="id-grp" class="lb-reg-op">Operadora:</label>
                <select name="operadora" id="id-grp" class="selc-reg-op">
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
                <input type="text" name="codigo" class="inpt-reg-cod" id="id-cod" placeholder="insira o código da linha..." required>
            </p>
            <p class="p-estilo">
                <label for="id-linha" class="lb-reg-linha">Linha:</label>
                <input type="text" name="linha" class="inpt-reg-linha" id="id-linha" placeholder="insira o nome da linha..." required>
            </p>
            <p class="p-estilo">
                <label for="id-desc" class="lb-reg-desc">Descrição:</label>
                <textarea name="descricao" id="id-desc" class="txt-reg-desc" placeholder="insira uma descrição..."></textarea>
            </p>            
            <p class="p-estilo">
                <label for="id-tipo" class="lb-reg-tipo">Tipo:</label>
                <select name="tipo" class="selc-reg-tipo" id="id-tipo" required>
                    <option value="select">Selecione um tipo..</option>
                    <option value="0">Bonde, VLT</option>
                    <option value="1">Metrô</option>
                    <option value="2">Trem</option>
                    <option value="3">Ônibus</option>                    
                </select>                
            <p class="p-estilo">
                <label for="id-cor" class="lb-reg-cor">Cor da linha:</label>
                <input type="color" name="cor-linha"  class="inpt-reg-cor" id="id-cor-linha">              
            </p>
            <p class="p-estilo">
                <label for="id-cor" class="lb-reg-cor">Cor do texto:</label>
                <input type="color" name="cor-texto"  class="inpt-reg-cor" id="id-cor-texto" value="#FFFFFF">              
            </p>
            <p class="p-estilo">
                <label for="id-ordem" class="lb-reg-ordem">Ordem:</label>
                <input type="text" name="ordem" class="inpt-reg-ordem" id="id-ordem" placeholder="insira a ordem da linha...">
            </p>
            <p class="p-estilo">
                <label for="id-grupo" class="lb-reg-grupo">Grupo:</label>
                <input type="text" name="grupo" class="inpt-reg-grupo" id="id-grupo" placeholder="insira o grupo da linha...">
            </p>            
            <hr>            
            <nav class="nav-reg-btn">
                <p>
                    <Button type="submit" class="btn-reg-cad">CADASTRAR</Button>
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
