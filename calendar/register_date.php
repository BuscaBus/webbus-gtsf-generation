<?php
include("../connection.php");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de data especial</title>
    <link rel="stylesheet" href="../css/calendar.css?v=1.4">
    <link rel="stylesheet" href="../css/table.css?v=1.1">
</head>

<body>
    <section id="section-iframe">
        <h1>Cadastrar data especial</h1>
        <form action="register_db_date.php" method="POST" autocomplete="off">
            <hr>
            <p class="p-estilo">
                <label for="id-servico" class="lb-reg-serv-date">Serviço:</label>
                <select name="servico" id="id-servico" class="selc-reg-serv">
                    <option>Selecione um serviço</option>;
                    <?php
                    $sql_select = "SELECT service_id FROM calendar";
                    $result_selec = mysqli_query($conexao, $sql_select);

                    while ($dados = mysqli_fetch_array($result_selec)) {
                        $id = $dados['service_id'];
                        echo "<option value='$id'>$id</option>";
                    }
                    ?>
                </select>
            </p>
            <p class="p-estilo">
                <label for="id-data" class="lb-reg-data">Data:</label>
                <input type="date" name="data" class="inpt-reg-data" id="id-data" required>
            </p>
            <p class="p-estilo">
                <label for="id-status" class="lb-reg-status">Status:</label>
                <select name="status" class="selc-reg-status" id="id-status" required>
                    <option value="select">Selecione um status..</option>
                    <option value="1">Serviço adicionado</option>
                    <option value="2">Serviço excluído</option>
                </select>
            </p>
            <br>
            <hr>
            <nav class="nav-reg-btn">
                <p>
                    <Button class="btn-reg-cad">CADASTRAR</Button>
                </p>
                <p>
                    <Button class="btn-reg-canc">
                        <a href="list.php" class="a-btn-canc">CANCELAR</a>
                    </Button>
                </p>
            </nav>
        </form>
        <br> 
        <!-- Tabela com a listagem dos calendários especiais -->
        <table>
            <caption>Relação de datas especiais</caption>
            <thead>
                <th class="th-serv">Serviço</th>
                <th class="th-data">Data</th>
                <th class="th-status">Status</th>
                <th class="th-acoes-reg">Ações</th>
            </thead>
            <?php
            // Laço de repetição para trazer dados do banco
            $sql_select = "SELECT *, DATE_FORMAT(date, '%d/%m/%Y') AS data_form, calendar_dates.exception_type,
            CASE 
                WHEN calendar_dates.exception_type = '1' THEN 'Serviço adicionado '
                WHEN calendar_dates.exception_type = '2' THEN 'Serviço excluído'                    
            END AS status_format
            FROM calendar_dates";
            $result = mysqli_query($conexao, $sql_select);

            while ($sql_result = mysqli_fetch_array($result)) {
                $id = $sql_result['service_id'];
                $data_real  = $sql_result['date']; // YYYY-MM-DD
                $data_exibe = $sql_result['data_form']; // dd/mm/yyyy
                $status = $sql_result['status_format'];

                // Define a classe CSS conforme o tipo
                if ($sql_result['exception_type'] == 1) {
                    $classeStatus = 'status-verde';
                } else {
                    $classeStatus = 'status-vermelho';
                }
            ?>
                <tbody>
                    <tr>
                        <td><?php echo $id ?></td>
                        <td><?php echo $data_exibe ?></td>
                        <td class="<?= $classeStatus ?>"><?php echo $status ?></td>
                        <td>
                            <form action="delete_date.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $id ?>">                                
                                <input type="hidden" name="date" value="<?php echo $data_real ?>">
                                <button type="submit" class="btn-excluir" onclick="return deletar()">EXCLUIR</button>
                            </form>
                        </td>
                    </tr>
                <?php }; ?>
                </tbody>
        </table>
</body>

</html>