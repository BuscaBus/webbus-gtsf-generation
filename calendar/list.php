<?php
    include("../connection.php");

     // Consulta no banco de dados para exibir na tabela
    $sql = "SELECT *, DATE_FORMAT(start_date, '%d/%m/%Y') AS data_for_start, DATE_FORMAT(end_date, '%d/%m/%Y') AS data_for_end  FROM calendar";
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
    <link rel="stylesheet" href="../css/calendar.css?v=1.3">    
</head>

<body>
    <div>
        <header>
            <h1>Calendários</h1>
        </header>
        <main>
            <section>
                <button class="btn-cadastrar" id="btn-cad">
                    <a href="register.html" class="link">+ CADASTRAR</a>
                </button>
                <hr>
                <br>
                <table>
                    <caption>Relação de calendários</caption>
                    <thead>
                        <th class="th-serv">Serviço</th>
                        <th class="th-inic">Inicio</th>
                        <th class="th-term">Término</th>
                        <th class="th-acoes">Ações</th>
                    </thead>
                    <?php
                        // Laço de repetição para trazer dados do banco
                        while($sql_result = mysqli_fetch_array($result)){
                            $id = $sql_result['service_id'];                            
                            $data_inicio = $sql_result['data_for_start'];  
                            $data_fim = $sql_result['data_for_end'];    
                    ?>
                    <tbody>
                        <tr>
                            <td><?php echo $id ?></td>
                            <td><?php echo $data_inicio ?></td>
                            <td><?php echo $data_fim ?></td>
                            <td>                                
                                <form action="delete.php" method ="POST">
                                    <input type="hidden" name="id" value="<?php echo $id ?>">
                                    <a href="edit.php?id=<?=$sql_result['service_id']?>" class="a-editar" id="a-edit">EDITAR</a>
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
                    $sql = "SELECT COUNT(*) AS total FROM calendar";
                    $result = mysqli_query($conexao, $sql);

                     $row = mysqli_fetch_assoc($result);
                     $total_registros = $row['total'];                    
                ?>
                <!-- Mostra a quantidade de registros-->
                <p>Total de calendários cadastrados: <?php echo $total_registros;?></p>
                <br>           
            </section>
        </main>
        <footer>
            <p><a href="../index.html">< Voltar</a></p>
        </footer>
    </div>
</body>
<script src="../js/modal-calendar.js"></script>
</html>