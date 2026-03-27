<?php
include("../connection.php");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "erro", "msg" => "Dados inválidos"]);
    exit;
}

foreach ($data as $item) {

    $id = mysqli_real_escape_string($conexao, $item['id']);
    $intervalo = mysqli_real_escape_string($conexao, $item['intervalo']);

    $sql = "UPDATE shape_stops 
            SET intervalo = '$intervalo' 
            WHERE Id = '$id'";

    mysqli_query($conexao, $sql);
}

echo json_encode(["status" => "ok"]);