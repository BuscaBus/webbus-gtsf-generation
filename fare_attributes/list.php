<?php
    include("../connection.php");
   
    // Código para filtrar após pesquisar
    $filtro_sql = "";
    if($_POST != NULL){
        $filtro = $_POST["pesquisar"];
        $filtro_sql = "WHERE route_group ='$filtro'";
    }    

   

    // Consulta no banco de dados para exibir na tabela
    $sql = "SELECT *, FORMAT(price, 2) AS price_format, DATE_FORMAT(update_date, '%d/%m/%Y') AS data_format FROM fare_attributes $filtro_sql ORDER BY route_group ASC";
    $result = mysqli_query($conexao, $sql);    
    
    ?>

<!--Script para confirmar a exclusão-->
<script>
    function deletar() {
        if(confirm("Deseja exluir esse item?"))
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
    <link rel="stylesheet" href="../css/fare_attributes.css?v=1.6">    
</head>

<body>
    <div>
        <header>
            <h1>Tarifas</h1>
        </header>
        <main>
            <section class="scroll-area">
                <button class="btn-cadastrar" id="btn-cad">
                    <a href="register.html" class="btn-a-cad">+ CADASTRAR</a>
                </button>
                <br> 
                <!-- Select no banco de dados para filtrar uma operadora--> 
                <form method="POST" action="list.php">
                    <select name="pesquisar" class="selec-pesq">
                        <option>Selecione um tipo de tarifa</option>;
                        <?php
                            $sql_select = "SELECT * FROM fare_attributes ORDER BY route_group ASC";
                            $result_selec = mysqli_query($conexao, $sql_select);

                            while($dados = mysqli_fetch_array($result_selec)){
                                $id = $dados['route_group']; 
                                $tipo = $dados['route_group'];                            
                                echo "<option value='$tipo'>$tipo</option>";
                            }
                        ?>                          
                    </select> 
                    <button type="submit" class="btn-pesq">PESQUISAR</button> 
                </form> 
                <hr>
                <br>                
                <table>
                    <caption>Relação de tarifas vigentes</caption>
                    <thead>
                        <th class="th-cod">Código</th>
                        <th class="th-tarifa">Tarifa</th>
                        <th class="th-tipo">Tipo</th>
                        <th class="th-atual">Atualização</th>
                        <th class="th-acoes">Ações</th>
                    </thead>
                    <?php
                        // Laço de repetição para trazer dados do banco
                        while($sql_result = mysqli_fetch_array($result)){
                            $id = $sql_result['fare_id'];
                            $preco = $sql_result['price_format'];
                            $grupo = $sql_result['route_group'];
                            $data = $sql_result['data_format'];                         
                    ?>
                    <tbody>
                        <tr>
                            <td><?php echo $id ?></td>
                            <td><?php echo $preco ?></td>
                            <td><?php echo $grupo ?></td>
                            <td><?php echo $data ?></td>
                            <td>                                
                                <form action="delete.php" method ="POST">
                                    <input type="hidden" name="codigo" value="<?php echo $id ?>">
                                    <a href="edit.php?id=<?=$sql_result['fare_id']?>" class="a-editar" id="a-edit">EDITAR</a>
                                    <button class="btn-excluir" onclick="return deletar()">EXCLUIR</button>
                                </form>
                            </td>
                        </tr> 
                    <?php }; ?>                           
                    </tbody>
                </table>
                <br>               
                <!--Consulta no banco de dados a quantidade de registros-->
                <?php
                    $sql = "SELECT COUNT(*) AS total FROM fare_attributes";
                    $result = mysqli_query($conexao, $sql);

                     $row = mysqli_fetch_assoc($result);
                     $total_registros = $row['total'];                    
                ?>
                <!-- Mostra a quantidade de registros-->
                <p>Total de tarifas cadastradas: <?php echo $total_registros;?></p>
                <br>                            
            </section>
        </main>
        <footer>
            <p><a href="../index.html">< Voltar</a></p>
        </footer>
    </div>
</body>
</html>