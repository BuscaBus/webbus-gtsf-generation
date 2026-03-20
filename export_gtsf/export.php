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
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/calendar.css?v=1.5">
</head>

<body>
    <div>
        <header>
            <h1>Exportador GTSF</h1>
        </header>
        <main>
            <section>                
                <h2>Exportar Dados GTSF</h2>
                <br>
                <form action="export_data.php" method="GET">

                    <label>Tabela:</label>
                    <select name="table" required>
                        <option value="agency">Agency</option>
                        <option value="stops">Stops</option>
                        <option value="routes">Routes</option>
                        <option value="trips">Trips</option>
                        <option value="stop_times">Stop Times</option>
                        
                    </select>

                    <br><br>

                    <label>Formato:</label>
                    <select name="format" required>
                        <option value="txt">TXT</option>
                        <option value="csv">CSV</option>
                    </select>

                    <br><br>

                    <button type="submit">Exportar</button>

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