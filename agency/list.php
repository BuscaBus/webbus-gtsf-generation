<?php
include("../connection.php");

// Código para filtrar após pesquisar
$filtro_sql = "";
if ($_POST != NULL) {
    $filtro = $_POST["pesquisar"];
    $filtro_sql = "WHERE agency_name ='$filtro'";
}


// Paginação
$pagina = (isset($_GET['pagina'])) ? $_GET['pagina'] : 1; //Verificar se está passando na URL a página

$sql_itens = "SELECT * FROM agency";
$result_itens = mysqli_query($conexao, $sql_itens);
$total_itens = mysqli_num_rows($result_itens); // Contar total de operadoras
$quant_paginas = 10; // Setar quantidade de itens por página
$num_pagina = ceil($total_itens / $quant_paginas); // Calcula o numero de páginas necessárias
$inicio = ($quant_paginas * $pagina) - $quant_paginas; // Calcula o inicio da visualização

// Consulta no banco de dados para exibir na tabela
$sql = "SELECT * FROM agency $filtro_sql ORDER BY agency_name ASC LIMIT $inicio, $quant_paginas";
$result = mysqli_query($conexao, $sql);

$total_itens = mysqli_num_rows($result_itens);

?>

<!--Script para confirmar a exclusão-->
<script>
    function deletar() {
        if (confirm("Deseja exluir esse item?"))
            document.forms[0].submit();
        else
            return false
    }
</script>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema WebBus</title>
    <link rel="shortcut icon" href="../img/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.2">
    <link rel="stylesheet" href="../css/agency.css?v=1.6">
</head>

<body>
    <div>
        <header>
            <h1>Operadoras</h1>
        </header>
        <main>
            <section>
                <button class="btn-cadastrar" id="btn-cad">
                    <a href="register.html" class="a-btn-cad">+ CADASTRAR</a>
                </button>
                <br>
                <!-- Select no banco de dados para filtrar uma operadora-->
                <form method="POST" action="list.php">
                    <select name="pesquisar" class="selc-pesq">
                        <option>Selecione uma operadora</option>;
                        <?php
                        $sql_select = "SELECT * FROM agency ORDER BY agency_name ASC";
                        $result_selec = mysqli_query($conexao, $sql_select);

                        while ($dados = mysqli_fetch_array($result_selec)) {
                            $id = $dados['agency_id'];
                            $operadoras = $dados['agency_name'];
                            echo "<option value='$operadoras'>$operadoras</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn-pesq">PESQUISAR</button>
                </form>

                <hr>
                <br>
                <table>
                    <caption>Relação de operadoras</caption>
                    <thead>
                        <th class="th-op">Operadora</th>
                        <th class="th-cid">Cidade</th>
                        <th class="th-site">Site</th>
                        <th class="th-acoes">Ações</th>
                    </thead>
                    <?php
                    // Laço de repetição para trazer dados do banco
                    while ($sql_result = mysqli_fetch_array($result)) {
                        $id = $sql_result['agency_id'];
                        $nome = $sql_result['agency_name'];
                        $cidade = $sql_result['agency_city'];
                        $url = $sql_result['agency_url'];
                    ?>
                        <tbody>
                            <tr>
                                <td><?php echo $nome ?></td>
                                <td><?php echo $cidade ?></td>
                                <td><?php echo $url ?></td>
                                <td>
                                    <form action="delete.php" method="POST">
                                        <input type="hidden" name="id" value="<?php echo $id ?>">
                                        <a href="edit.php?id=<?= $sql_result['agency_id'] ?>" class="a-editar" id="a-edit">EDITAR</a>
                                        <button class="btn-excluir" onclick="return deletar()">EXCLUIR</button>
                                    </form>
                                </td>
                            </tr>
                        <?php }; ?>
                        </tbody>
                </table>
                <br>
                <?php
                // Verificar pagina anterior e posterior
                $pagina_ant = $pagina - 1;
                $pagina_post = $pagina + 1;
                ?>
                <!-- Navegação da páginação-->
                <nav class="nav-pag" aria-label="Page navigation example">
                    <ul class="paginacao">
                        <?php
                        if ($pagina_ant != 0) { ?>
                            <a class="nav-pag" href="list.php?pagina=<?php echo $pagina_ant; ?>"> Páginas: << </a>
                                <?php } else { ?>
                                    <span> Páginas: << </span>
                                        <?php } ?>
                                        <?php
                                        for ($i = 1; $i < $num_pagina + 1; $i++) { ?>
                                            <a class="nav-pag" href="list.php?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        <?php } ?>
                                        <?php
                                        if ($pagina_post <= $num_pagina) { ?>
                                            <a class="nav-pag" href="list.php?pagina=<?php echo $pagina_post; ?>"> >> </a>
                                        <?php } else { ?>
                                            <span> >> </span>
                                        <?php } ?>
                    </ul>
                </nav>
                <br>
                <!--Consulta no banco de dados a quantidade de registros-->
                <?php
                $sql = "SELECT COUNT(*) AS total FROM agency";
                $result = mysqli_query($conexao, $sql);

                $row = mysqli_fetch_assoc($result);
                $total_registros = $row['total'];
                ?>
                <!-- Mostra a quantidade de registros-->
                <p>Total de operadoras cadastradas: <?php echo $total_registros; ?></p>
                <br>
            </section>
        </main>
        <footer>
            <p><a href="../index.html">< Voltar</a>
            </p>
        </footer>
    </div>
</body>
<script src="../js/modal-agency.js"></script>
<script src="../js/agency.js"></script>

</html>