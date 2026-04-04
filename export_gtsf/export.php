<?php
include("../connection.php");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema WebBus</title>
    <link rel="shortcut icon" href="../img/logo-icon2.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.2">    
    <link rel="stylesheet" href="../css/export_gtsf.css?v=1.0">
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

                    <label class="lb-tabela">Tabela:</label>
                    <select name="table"  class="selc-tabela" required>
                        <option value="">Selecione</option>
                        <option value="agency">Agency</option>
                        <option value="stops">Stops</option>
                        <option value="routes">Routes</option>
                        <option value="trips">Trips</option>
                        <option value="stop_times">Stop Times</option>
                        <option value="calendar">Calendar</option>
                        <option value="calendar_dates">Calendar dates</option>
                        <option value="fare_attributes">Fare Attributes</option>
                        <option value="fare_rules">Fare Rules</option>
                        <option value="shapes">Shapes</option>                   
                    </select>
                    <br><br>

                    <label class="lb-formato">Formato:</label>
                    <select name="format" class="selc-formato" required>
                        <option value="txt">TXT</option>
                        <option value="csv">CSV</option>
                    </select>

                    <br><br>

                    <button type="submit" class="btn-exportar">EXPORTAR</button>

                </form>
            </section>
        </main>
        <footer>
            <p><a href="../index.html"> < Voltar</a></p>
        </footer>
    </div>
</body>

</html>