<?php
require_once __DIR__ . "/../connection.php";

$sql = "SELECT agency_id, agency_name
        FROM agency
        ORDER BY agency_name";

$result = mysqli_query($conexao, $sql);

$dados = [];

while($row = mysqli_fetch_assoc($result)){
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);