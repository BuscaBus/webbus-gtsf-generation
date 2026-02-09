<?php
include("../connection.php");

if (isset($_GET['terminal'])) {
    $terminal = mysqli_real_escape_string($conexao, $_GET['terminal']);

    $sql = "SELECT stop_name FROM stops WHERE parent_station = '$terminal' ORDER BY stop_name ASC";
    $result = mysqli_query($conexao, $sql);

    $box = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $box[] = [
            'nome' => $row['stop_name']
        ];
    }

    echo json_encode($box);
}
?>