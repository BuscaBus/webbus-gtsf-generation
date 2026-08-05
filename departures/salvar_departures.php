<?php

require_once __DIR__."/../connection.php";

header("Content-Type:application/json");

$data=json_decode(file_get_contents("php://input"),true);

if(!$data){

    echo json_encode([
        "status"=>"erro",
        "message"=>"Nenhum horário recebido."
    ]);

    exit;

}

mysqli_begin_transaction($conexao);

try{

    foreach($data as $item){

        $trip_id=$item['trip_id'];
        $service_id=$item['service_id'];
        $departure_time=$item['departure_time'];

        mysqli_query($conexao,"
            INSERT INTO trip_departures
            (
                trip_id,
                service_id,
                departure_time
            )
            VALUES
            (
                '$trip_id',
                '$service_id',
                '$departure_time'
            )
        ");

    }

    mysqli_query($conexao,"
        UPDATE trips
        SET service_id='$service_id'
        WHERE trip_id='$trip_id'
    ");

    mysqli_commit($conexao);

    echo json_encode([
        "status"=>"ok",
        "message"=>"Horários cadastrados com sucesso."
    ]);

}
catch(Exception $e){

    mysqli_rollback($conexao);

    echo json_encode([
        "status"=>"erro",
        "message"=>$e->getMessage()
    ]);

}