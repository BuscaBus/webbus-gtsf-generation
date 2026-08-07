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
    $sql = "INSERT INTO trip_departures (
                trip_id,
                service_id,
                departure_time,
                wheelchair_accessible,
                timepoint
            ) VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conexao));
    }

    foreach ($data as $item) {

        $trip_id = (int) $item['trip_id'];
        $service_id = $item['service_id'];
        $departure_time = $item['departure_time'];

        $wheelchair_accessible =
            !empty($item['wheelchair_accessible']) ? 1 : 0;

        $timepoint =
            !empty($item['timepoint']) ? 1 : 0;

        mysqli_stmt_bind_param(
            $stmt,
            "issii",
            $trip_id,
            $service_id,
            $departure_time,
            $wheelchair_accessible,
            $timepoint
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
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