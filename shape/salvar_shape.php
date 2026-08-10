<?php

require_once __DIR__ . "/../connection.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


/*
|--------------------------------------------------------------------------
| RECEBE OS DADOS
|--------------------------------------------------------------------------
*/

$data = json_decode(
    file_get_contents("php://input"),
    true
);


$pattern_id = isset($data['pattern_id'])
    ? (int) $data['pattern_id']
    : 0;


$shape_id = !empty($data['shape_id'])
    ? trim($data['shape_id'])
    : null;


$coords = $data['coords'] ?? [];


/*
|--------------------------------------------------------------------------
| VALIDAÇÃO
|--------------------------------------------------------------------------
*/

if (
    $pattern_id <= 0 ||
    empty($coords)
) {

    echo json_encode([
        "status" => "error",
        "message" => "Dados inválidos."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| INICIA TRANSAÇÃO
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conexao);


try {

    /*
    |--------------------------------------------------------------------------
    | 1. BUSCA O PATTERN
    |--------------------------------------------------------------------------
    */

    $sqlPattern = "
        SELECT
            route_id,
            shape_id

        FROM trip_patterns

        WHERE pattern_id = ?

        LIMIT 1
    ";


    $stmtPattern = mysqli_prepare(
        $conexao,
        $sqlPattern
    );


    if (!$stmtPattern) {

        throw new Exception(
            "Erro ao preparar consulta do pattern: "
            . mysqli_error($conexao)
        );
    }


    mysqli_stmt_bind_param(
        $stmtPattern,
        "i",
        $pattern_id
    );


    mysqli_stmt_execute(
        $stmtPattern
    );


    $resultPattern =
        mysqli_stmt_get_result(
            $stmtPattern
        );


    $pattern =
        mysqli_fetch_assoc(
            $resultPattern
        );


    if (!$pattern) {

        throw new Exception(
            "Padrão de viagem não encontrado."
        );
    }


    $route_id =
        (int) $pattern['route_id'];


    $shape_id_atual =
        $pattern['shape_id'];


    /*
    |--------------------------------------------------------------------------
    | 2. SE O PATTERN JÁ POSSUI SHAPE, USA O SHAPE DO BANCO
    |--------------------------------------------------------------------------
    |
    | Não confiamos somente no shape_id recebido pelo JavaScript.
    | Se o pattern já possui um shape, ele é a fonte de verdade.
    |
    */

    if (!empty($shape_id_atual)) {

        $shape_id =
            $shape_id_atual;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. CRIA UM NOVO SHAPE_ID SE O PATTERN AINDA NÃO POSSUI
    |--------------------------------------------------------------------------
    */

    if (empty($shape_id)) {

        $shape_id =
            "SHP_P" . $pattern_id;


        /*
        |--------------------------------------------------------------------------
        | Verifica se o ID já existe no shape_master
        |--------------------------------------------------------------------------
        */

        $sqlCheck = "
            SELECT shape_id

            FROM shape_master

            WHERE shape_id = ?

            LIMIT 1
        ";


        $stmtCheck = mysqli_prepare(
            $conexao,
            $sqlCheck
        );


        if (!$stmtCheck) {

            throw new Exception(
                "Erro ao verificar Shape ID: "
                . mysqli_error($conexao)
            );
        }


        mysqli_stmt_bind_param(
            $stmtCheck,
            "s",
            $shape_id
        );


        mysqli_stmt_execute(
            $stmtCheck
        );


        $resultCheck =
            mysqli_stmt_get_result(
                $stmtCheck
            );


        /*
        |--------------------------------------------------------------------------
        | Caso o ID já exista, gera um identificador alternativo
        |--------------------------------------------------------------------------
        */

        if (
            mysqli_num_rows(
                $resultCheck
            ) > 0
        ) {

            $shape_id =
                "SHP_P"
                . $pattern_id
                . "_"
                . time();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 4. GARANTE QUE O SHAPE EXISTA NO SHAPE_MASTER
    |--------------------------------------------------------------------------
    |
    | shapes.shape_id possui FK para shape_master.shape_id.
    | Portanto o registro precisa existir aqui ANTES dos pontos.
    |
    */

    $sqlMaster = "
        INSERT IGNORE INTO shape_master (
            shape_id
        )
        VALUES (?)
    ";


    $stmtMaster = mysqli_prepare(
        $conexao,
        $sqlMaster
    );


    if (!$stmtMaster) {

        throw new Exception(
            "Erro ao preparar Shape Master: "
            . mysqli_error($conexao)
        );
    }


    mysqli_stmt_bind_param(
        $stmtMaster,
        "s",
        $shape_id
    );


    mysqli_stmt_execute(
        $stmtMaster
    );


    /*
    |--------------------------------------------------------------------------
    | 5. REMOVE OS PONTOS ANTIGOS DO SHAPE
    |--------------------------------------------------------------------------
    |
    | O registro no shape_master NÃO é removido.
    | Isso permite editar o trajeto sem quebrar as FKs.
    |
    */

    $sqlDeleteShape = "
        DELETE FROM shapes

        WHERE shape_id = ?
    ";


    $stmtDeleteShape = mysqli_prepare(
        $conexao,
        $sqlDeleteShape
    );


    if (!$stmtDeleteShape) {

        throw new Exception(
            "Erro ao preparar remoção dos pontos: "
            . mysqli_error($conexao)
        );
    }


    mysqli_stmt_bind_param(
        $stmtDeleteShape,
        "s",
        $shape_id
    );


    mysqli_stmt_execute(
        $stmtDeleteShape
    );


    /*
    |--------------------------------------------------------------------------
    | 6. PREPARA INSERT DOS PONTOS DO SHAPE
    |--------------------------------------------------------------------------
    */

    $sqlShape = "
        INSERT INTO shapes (
            shape_id,
            shape_pt_lat,
            shape_pt_lon,
            shape_pt_sequence
        )
        VALUES (?, ?, ?, ?)
    ";


    $stmtShape = mysqli_prepare(
        $conexao,
        $sqlShape
    );


    if (!$stmtShape) {

        throw new Exception(
            "Erro ao preparar pontos do Shape: "
            . mysqli_error($conexao)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 7. SALVA AS COORDENADAS
    |--------------------------------------------------------------------------
    */

    $seq = 1;
    $pontos_salvos = 0;


    foreach ($coords as $pt) {

        /*
        |--------------------------------------------------------------------------
        | Valida longitude e latitude
        |--------------------------------------------------------------------------
        */

        if (
            !isset($pt[0]) ||
            !isset($pt[1])
        ) {

            continue;
        }


        if (
            !is_numeric($pt[0]) ||
            !is_numeric($pt[1])
        ) {

            continue;
        }


        $lon =
            (float) $pt[0];

        $lat =
            (float) $pt[1];


        /*
        |--------------------------------------------------------------------------
        | Validação básica das coordenadas
        |--------------------------------------------------------------------------
        */

        if (
            $lat < -90 ||
            $lat > 90 ||
            $lon < -180 ||
            $lon > 180
        ) {

            continue;
        }


        mysqli_stmt_bind_param(
            $stmtShape,
            "sddi",
            $shape_id,
            $lat,
            $lon,
            $seq
        );


        mysqli_stmt_execute(
            $stmtShape
        );


        $seq++;
        $pontos_salvos++;
    }


    /*
    |--------------------------------------------------------------------------
    | Shape precisa possuir ao menos 2 pontos
    |--------------------------------------------------------------------------
    */

    if ($pontos_salvos < 2) {

        throw new Exception(
            "O trajeto precisa possuir pelo menos dois pontos válidos."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 8. VINCULA O SHAPE AO PATTERN
    |--------------------------------------------------------------------------
    */

    $sqlUpdatePattern = "
        UPDATE trip_patterns

        SET shape_id = ?

        WHERE pattern_id = ?
    ";


    $stmtUpdatePattern = mysqli_prepare(
        $conexao,
        $sqlUpdatePattern
    );


    if (!$stmtUpdatePattern) {

        throw new Exception(
            "Erro ao preparar atualização do pattern: "
            . mysqli_error($conexao)
        );
    }


    mysqli_stmt_bind_param(
        $stmtUpdatePattern,
        "si",
        $shape_id,
        $pattern_id
    );


    mysqli_stmt_execute(
        $stmtUpdatePattern
    );


    /*
    |--------------------------------------------------------------------------
    | 9. GARANTE O RELACIONAMENTO ROUTE + SHAPE EM MAPS_TRIPS
    |--------------------------------------------------------------------------
    |
    | Como existe UNIQUE(route_id, shape_id), podemos usar
    | INSERT IGNORE e dispensar DELETE + INSERT.
    |
    */

    $sqlMap = "
        INSERT IGNORE INTO maps_trips (
            route_id,
            shape_id
        )
        VALUES (?, ?)
    ";


    $stmtMap = mysqli_prepare(
        $conexao,
        $sqlMap
    );


    if (!$stmtMap) {

        throw new Exception(
            "Erro ao preparar Maps Trips: "
            . mysqli_error($conexao)
        );
    }


    mysqli_stmt_bind_param(
        $stmtMap,
        "is",
        $route_id,
        $shape_id
    );


    mysqli_stmt_execute(
        $stmtMap
    );


    /*
    |--------------------------------------------------------------------------
    | FINALIZA TRANSAÇÃO
    |--------------------------------------------------------------------------
    */

    mysqli_commit(
        $conexao
    );


    /*
    |--------------------------------------------------------------------------
    | RESPOSTA
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "status" => "ok",
        "message" => "Trajeto salvo com sucesso.",
        "pattern_id" => $pattern_id,
        "shape_id" => $shape_id,
        "pontos_salvos" => $pontos_salvos
    ]);


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | DESFAZ ALTERAÇÕES EM CASO DE ERRO
    |--------------------------------------------------------------------------
    */

    mysqli_rollback(
        $conexao
    );


    echo json_encode([
        "status" => "error",
        "message" => "Erro ao salvar trajeto.",
        "detail" => $e->getMessage()
    ]);
}