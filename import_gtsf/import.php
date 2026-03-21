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
    <link rel="stylesheet" href="../css/export_gtsf.css?v=1.0">
</head>

<body>
    <div>
        <header>
            <h1>Importador GTSF</h1>
        </header>
        <main>
            <section>
                <h2>Importar Dados GTSF</h2>
                <form action="import_data.php" method="POST" enctype="multipart/form-data">
                    <label>Tipo de importação:</label><br>
                    <select name="tipo" required>
                        <option value="agency">Agency</option>
                        <option value="stops">Stops</option>
                    </select>
                    <br><br>
                    <input type="file" name="arquivo" accept=".txt,.csv" required>
                    <br><br>
                    <button type="submit" name="importar">Importar</button>
                </form>
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