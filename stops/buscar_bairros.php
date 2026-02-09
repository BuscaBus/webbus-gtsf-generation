<?php
include("../connection.php");

if (isset($_GET['cidade'])) {
    $cidade = mysqli_real_escape_string($conexao, $_GET['cidade']);

    $sql = "SELECT district FROM city_district WHERE city = '$cidade' ORDER BY district ASC";
    $result = mysqli_query($conexao, $sql);

    $bairros = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $bairros[] = [
            'nome' => $row['district']
        ];
    }

    echo json_encode($bairros);
}
?>
