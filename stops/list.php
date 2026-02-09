<?php
include("../connection.php");

$filtro_sql = "";

// Código para buscar pelo input 
$buscar = "";
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $buscar = mysqli_real_escape_string($conexao, $_GET['buscar']);
    $filtro_sql .= " WHERE stops.stop_code LIKE '%$buscar%'";
}

// Paginação
$pagina = (isset($_GET['pagina'])) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;

$sql_itens = "SELECT * FROM stops $filtro_sql";
$result_itens = mysqli_query($conexao, $sql_itens);
$total_itens = mysqli_num_rows($result_itens);

$quant_paginas = 10;
$num_pagina = ceil($total_itens / $quant_paginas);
$inicio = ($quant_paginas * $pagina) - $quant_paginas;

// Consulta no banco de dados para exibir na tabela
$sql = "SELECT stop_id,
               stop_code, 
               stop_name, 
               stop_district, 
               stop_city,
               location_type, 
               CASE 
                    WHEN location_type = 0 THEN 'Ponto'
                    WHEN location_type = 1 THEN 'Terminal'
                    ELSE 'Desconhecido'
               END AS tipo_local,
               platform_code,                  
               stops.update_date,
               DATE_FORMAT(stops.update_date, '%d/%m/%Y') AS data_format 
        FROM stops $filtro_sql
        ORDER BY stop_code ASC 
        LIMIT $inicio, $quant_paginas";
$result = mysqli_query($conexao, $sql);

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
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/stops.css?v=1.4">
</head>

<body>
    <div>
        <header>
            <h1>Pontos</h1>
        </header>
        <main>
            <section>
                <button class="btn-cadastrar" id="btn-cad">
                    <a href="stop_maps.php" class="a-btn-cad">+ CADASTRAR</a>
                </button>                
                <br>
                <!-- Input para buscar dados e filtrar -->
                <form method="GET">
                    <input name="buscar" class="impt-buscar" value="<?php echo $buscar; ?>" placeholder="Pesquise por código" type="text">
                    <button type="submit" class="btn-buscar">PESQUISAR</button>                    
                </form>
                <hr>
                <br>
                <table>
                    <caption>Relação de pontos</caption>
                    <thead>
                        <th class="th-cod">Código</th>
                        <th class="th-pont">Ponto</th>
                        <th class="th-bair">Bairro</th>
                        <th class="th-cid">Cidade</th>
                        <th class="th-loc">Local</th>
                        <th class="th-box">Box</th>
                        <th class="th-atual">Atualização</th>
                        <th class="th-acoes">Ações</th>
                    </thead>
                    <tbody>
                        <?php
                        while ($sql_result = mysqli_fetch_array($result)) {
                            $id = $sql_result['stop_id'];
                            $codigo = $sql_result['stop_code'];
                            $ponto = $sql_result['stop_name'];
                            $bairro = $sql_result['stop_district'];
                            $cidade = $sql_result['stop_city'];
                            $local = $sql_result['tipo_local'];
                            $box = $sql_result['platform_code'];
                            $atual = $sql_result['data_format'];
                        ?>
                            <tr>
                                <td><?php echo $codigo ?></td>
                                <td><?php echo $ponto ?></td>
                                <td><?php echo $bairro ?></td>
                                <td><?php echo $cidade ?></td>
                                <td><?php echo $local ?></td>
                                <td><?php echo $box ?></td>
                                <td><?php echo $atual ?></td>
                                <td>
                                    <form action="delete.php" method="POST">
                                        <input type="hidden" name="id" value="<?php echo $id ?>">
                                        <a href="edit.php?id=<?= $sql_result['stop_id'] ?>" class="a-editar" id="a-edit">EDITAR</a>
                                        <button class="btn-excluir" onclick="return deletar()">EXCLUIR</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <br>
                <?php
                // Verificar pagina anterior e posterior
                $pagina_ant = $pagina - 1;
                $pagina_post = $pagina + 1;

                // Manter filtro na paginação
                $query_str = "";
                if (!empty($buscar)) {
                    $query_str = "&buscar=" . urlencode($buscar);
                }
                ?>
                <!-- Navegação da paginação-->
                <nav class="nav-pag" aria-label="Page navigation example">
                    <ul class="paginacao">
                        <?php
                        if ($pagina_ant >= 1) { ?>
                            <a class="nav-pag" href="list.php?pagina=<?php echo $pagina_ant . $query_str; ?>">
                                << </a>
                                <?php } else { ?>
                                    <span>
                                        << </span>
                                        <?php }

                                    // Definir range de páginas (máximo 5)
                                    $max_links = 5;
                                    $inicio = max(1, $pagina - floor($max_links / 2));
                                    $fim = min($num_pagina, $inicio + $max_links - 1);

                                    if ($fim - $inicio + 1 < $max_links) {
                                        $inicio = max(1, $fim - $max_links + 1);
                                    }

                                    // Loop limitado
                                    for ($i = $inicio; $i <= $fim; $i++) {
                                        if ($i == $pagina) {
                                            echo "<a class='nav-pag active' href='list.php?pagina=$i$query_str'> $i </a>";
                                        } else {
                                            echo "<a class='nav-pag' href='list.php?pagina=$i$query_str'> $i </a>";
                                        }
                                    }

                                    if ($pagina_post <= $num_pagina) { ?>
                                            <a class="nav-pag" href="list.php?pagina=<?php echo $pagina_post . $query_str; ?>"> >> </a>
                                        <?php } else { ?>
                                            <span> >> </span>
                                        <?php } ?>
                    </ul>
                </nav>
                <br>
                <!--Consulta no banco de dados a quantidade de registros-->
                <?php
                $sql = "SELECT COUNT(*) AS total FROM stops";
                $result = mysqli_query($conexao, $sql);
                $row = mysqli_fetch_assoc($result);
                $total_registros = $row['total'];
                ?>
                <!-- Mostra a quantidade de registros-->
                <p>Total de pontos cadastrados: <?php echo $total_registros; ?></p>
                <br>
            </section>
        </main>
        <footer>
            <p><a href="../index.html">
                    < Voltar</a>
            </p>
        </footer>
    </div>
</body>

</html>