<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {

    echo json_encode([
        "status" => "erro",
        "message" => "Nenhum horário recebido."
    ]);

    exit;
}

mysqli_begin_transaction($conexao);

try {

    // Pega trip_id e service_id
    // Todos os horários enviados pertencem à mesma viagem/serviço
    $trip_id = (int) $data[0]['trip_id'];
    $service_id = $data[0]['service_id'];


    // 1 - Remove todos os horários existentes
    // dessa viagem + serviço
    $sqlDelete = "
        DELETE FROM trip_departures
        WHERE trip_id = ?
        AND service_id = ?
    ";

    $stmtDelete = mysqli_prepare($conexao, $sqlDelete);

    if (!$stmtDelete) {
        throw new Exception(mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmtDelete,
        "is",
        $trip_id,
        $service_id
    );

    if (!mysqli_stmt_execute($stmtDelete)) {
        throw new Exception(mysqli_stmt_error($stmtDelete));
    }


    // 2 - Prepara novamente os horários que ficaram na lista
    $sql = "
        INSERT INTO trip_departures (
            trip_id,
            service_id,
            departure_time,
            wheelchair_accessible,
            timepoint
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conexao));
    }


    // 3 - Insere somente os horários enviados pela tela
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


    // 4 - Atualiza o serviço da viagem
    $sqlTrip = "
        UPDATE trips
        SET service_id = ?
        WHERE trip_id = ?
    ";

    $stmtTrip = mysqli_prepare($conexao, $sqlTrip);

    if (!$stmtTrip) {
        throw new Exception(mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmtTrip,
        "si",
        $service_id,
        $trip_id
    );

    if (!mysqli_stmt_execute($stmtTrip)) {
        throw new Exception(mysqli_stmt_error($stmtTrip));
    }


    mysqli_commit($conexao);


    echo json_encode([
        "status" => "ok",
        "message" => "Horários salvos com sucesso."
    ]);

}
catch (Exception $e) {

    mysqli_rollback($conexao);

    echo json_encode([
        "status" => "erro",
        "message" => $e->getMessage()
    ]);
}