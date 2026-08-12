<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

try {

    /*
    |--------------------------------------------------------------------------
    | RECEBER JSON
    |--------------------------------------------------------------------------
    */

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );


    if (!is_array($data)) {

        echo json_encode([
            "status" => "erro",
            "message" => "Dados inválidos."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR ID
    |--------------------------------------------------------------------------
    */

    $id = isset($data["id"])
        ? (int) $data["id"]
        : 0;


    if ($id <= 0) {

        echo json_encode([
            "status" => "erro",
            "message" => "ID da parada inválido."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR SE O REGISTRO EXISTE
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT Id
        FROM shape_stops
        WHERE Id = ?
        LIMIT 1
    ";

    $stmt = $conexao->prepare($sql);

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $resultado = $stmt->get_result();


    if ($resultado->num_rows === 0) {

        echo json_encode([
            "status" => "erro",
            "message" => "Ponto não encontrado no banco de dados."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | EXCLUIR
    |--------------------------------------------------------------------------
    */

    $sql = "
        DELETE FROM shape_stops
        WHERE Id = ?
    ";

    $stmt = $conexao->prepare($sql);

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR EXCLUSÃO
    |--------------------------------------------------------------------------
    */

    if ($stmt->affected_rows > 0) {

        echo json_encode([
            "status" => "ok",
            "message" => "Ponto excluído com sucesso."
        ]);

    } else {

        echo json_encode([
            "status" => "erro",
            "message" => "Nenhum ponto foi excluído."
        ]);

    }


} catch (mysqli_sql_exception $e) {

    echo json_encode([
        "status" => "erro",
        "message" => "Erro no banco de dados: " . $e->getMessage()
    ]);

} catch (Throwable $e) {

    echo json_encode([
        "status" => "erro",
        "message" => "Erro interno: " . $e->getMessage()
    ]);
}