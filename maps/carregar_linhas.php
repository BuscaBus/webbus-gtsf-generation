<?php
require_once __DIR__ . "/../connection.php";

$agency_id = intval($_GET['agency_id']);

$sql = "
SELECT
    route_id,
    route_short_name,
    route_long_name
FROM routes
WHERE agency_id = $agency_id AND route_status = 'A'
ORDER BY route_short_name
";

$result = mysqli_query($conexao, $sql);

$dados = [];

while($row = mysqli_fetch_assoc($result)){
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);