<?php
require_once __DIR__ . "/../connection.php";

if (isset($_GET['success'])) {
    echo "<script>alert('Horários cadastrados com sucesso!');</script>";
}
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
    <link rel="shortcut icon" href="../img/logo-icon2.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <link rel="stylesheet" href="../css/table.css?v=1.0">
    <link rel="stylesheet" href="../css/departures.css?v=1.0">
</head>

<body>
    <div>
        <header>
            <h1>Horários</h1>
        </header>
        <main class="main-cont">
            <!-- Section para cadastrar horários -->
            <section class="sect-reg-hor">
                <h1 class="h1-cad-hor">Cadastrar Horários</h1>
                <br>
                <form action="result_register.php" method="POST" autocomplete="off" class="form-cad-vig">
                    <input type="hidden" name="route_id" class="inpt1" id="id-route" value="">
                    <input type="hidden" name="trip_id" class="inpt1" id="id-trip" value="">
                    <input type="hidden" name="shape_id" value="">
                    <p class="p-estilo">
                        <label for="id-viag" class="lb-reg-viag">Viagem:</label>
                        <select id="id-viag" class="selec-reg-viag"></select>
                    </p>
                    <p class="p-estilo">
                        <label class="lb-reg-serv">Serviço:</label>
                        <select id="id-serv" class="selec-reg-serv"> </select>                        
                    </p>
                    <p class="p-estilo">
                        <label class="lb-reg-hrpart">Partida:</label>
                        <input type="time" name="horario" class="inpt-reg-hrpart">
                    </p>
                    <br>
                </form>
            </section>

            <!-- Section para listar os horários -->
            <section class="sect-list-hor">
                <br>
                <table>
                    <br><br>
                    <thead>
                        <th class="th-seq">Seq.</th>
                        <th class="th-ponto">Ponto</th>
                        <th class="th-cheg">Chegada</th>
                        <th class="th-part">Partida</th>
                        <th class="th-prev">*</th>
                        <th class="th-inter">Intervalo</th>
                        <th class="th-dest">Destino</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>

                                <input type="hidden" name="stop_sequence[]" value="">
                            </td>

                            <td>
                                <input type="hidden" name="stop_id[]" value="">
                            </td>

                            <td>
                                <input type="time" name="arrival_time[]" class="chegada" value="">
                            </td>

                            <td>
                                <input type="time" name="departure_time[]" class="partida" value="">
                            </td>

                            <td>
                                <input
                                    type="checkbox"
                                    name="timepoint[<?= $seq ?>]"
                                    value="0">
                            </td>

                            <td>
                                <input type="time" name="intervalo[]" class="intervalo" value="" disabled>
                            </td>

                            <td>
                                <input type="text" name="stop_headsign[]" class="headsign" value="">
                            </td>

                        </tr>

                    </tbody>
                </table>
                <br>
                <nav class="nav-reg-btn">
                    <p>
                        <button class="btn-reg-cad">SALVAR</button>
                    </p>
                </nav>
            </section>

        </main>
        <footer>
            <p><a href="../trips/register.php?id=">
                    < Voltar</a>
            </p>
        </footer>
    </div>

</body>

</html>