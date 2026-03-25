<?php
include("../connection.php");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema WebBus</title>
    <link rel="shortcut icon" href="../img/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/import_gtsf.css?v=1.0">
</head>

<?php if (isset($_GET['msg'])): ?>
<script>
    alert(<?php echo json_encode($_GET['msg']); ?>);
</script>
<?php endif; ?>

<body>
    <div>
        <header>
            <h1>Importador GTSF</h1>
        </header>
        <main>
            <section>
                <h2>Importar Dados GTSF</h2>
                <br>
                <form action="import_data.php" method="POST" enctype="multipart/form-data">
                    <label class="lb-tabela">Tipo de importação:</label><br>
                    <select name="tipo" class="selc-tabela" required>
                        <option value="">Selecionar tipo de arquivo</option>
                        <option value="agency">Agency</option>
                        <option value="stops">Stops</option>
                        <option value="routes">Routes</option>
                        <option value="trips">Trips</option>
                        <option value="stop_times">Stop Times</option>
                        <option value="calendar">Calendar</option>
                        <option value="calendar_dates">Calendar Dates</option>
                        <option value="fare_attributes">Fare Attributes</option>
                        <option value="fare_rules">Fare Rules</option>
                        <option value="shape">Shape</option>
                    </select>
                    <br><br>
                    <input type="file" name="arquivo" class="impt-arquivo" accept=".txt,.csv" required>
                    <br><br>
                    <button type="submit" name="importar" class="btn-importar">IMPORTAR</button>
                </form>
            </section>
        </main>
        <footer>
            <p><a href="../index.html">< Voltar</a></p>
        </footer>
    </div>
</body>

</html>