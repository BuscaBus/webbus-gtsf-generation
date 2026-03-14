<?php
include("../connection.php");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;

if(!$id){
    echo json_encode(["status"=>"erro"]);
    exit;
}

$id = intval($id);

$sql = "DELETE FROM shape_stops WHERE Id = $id";

if(mysqli_query($conexao,$sql)){

    echo json_encode(["status"=>"ok"]);

}else{

    echo json_encode(["status"=>"erro"]);

}