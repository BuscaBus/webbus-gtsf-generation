<?php
    include("../connection.php");

    // Declaração da variavel para receber o ID
    $id = $_GET['id'];
    
    // Consulta o ID no banco de dados
    $sql = "SELECT * FROM calendar WHERE service_id = '$id'";
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
    <title>Editar calendário</title>
    <link rel="stylesheet" href="../css/calendar.css?v=1.2">  
</head>

<body>
    <section id="section-iframe">
        <h1>Editar calendário</h1>
        <form action="edit_result.php" method="POST" autocomplete="off">
            <hr>
            <input type="hidden" name="id_original" value="<?=$result_id['service_id']?>">
            <p class="p-estilo">
                <label for="id-edt-serv" class="lb-edt-serv">Serviço:</label>
                <input type="text" name="servico" class="inpt-edt-serv" id="id-edt-serv" value="<?=$result_id['service_id']?>" disabled>
            </p>
            <p class="p-estilo">
                <label for="id-inicio-vig" class="lb-edt-inic">Inicio da vigência:</label>
                <input type="date" name="inic_vig" class="inpt-edt-inic" id="id-edt-inic" value="<?=$result_id['start_date']?>" required>            
            </p>
            <p class="p-estilo">
                <label for="id-term-vig" class="lb-edt-term">Término da vigência:</label>
                <input type="date" name="term_vig" class="inpt-edt-term" id="id-edt-term" value="<?=$result_id['end_date']?>" required>            
            </p>
            <br>            
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