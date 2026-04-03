<?php
include("../connection.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Funcion para ajustar coordenadas 
function ajustarCoordenada($valor, $tipo = 'lat')
{
    if ($valor === null) return null;

    $valor = trim((string)$valor);
    if ($valor === '') return null;

    $valor = preg_replace('/[^0-9\-]/', '', $valor);

    if ($valor === '' || $valor === '-') return null;

    $negativo = ($valor[0] === '-');
    $numero = ltrim($valor, '-');

    // divisão correta (seu padrão)
    $resultado = bcdiv($numero, '100000000000', 7);

    if ($negativo) {
        $resultado = '-' . $resultado;
    }

    // validação
    $teste = (float)$resultado;

    if ($tipo === 'lat' && ($teste < -90 || $teste > 90)) return null;
    if ($tipo === 'lon' && ($teste < -180 || $teste > 180)) return null;

    return $resultado; // NÃO usa substr, NÃO usa float
}

// Função para ajustar coordenadas na tabela shape
function ajustarShapeCoord($valor)
{
    if ($valor === null) return null;

    $valor = trim((string)$valor);

    if ($valor === '') return null;

    $valor = preg_replace('/[^0-9\-]/', '', $valor);

    if ($valor === '' || $valor === '-') return null;

    return round(((float)$valor) / 1000000, 6);
}

set_time_limit(0);
ini_set('memory_limit', '512M');

if (isset($_POST['importar'])) {

    $tipo = $_POST['tipo'] ?? null;

    if (!$tipo) {
        die("Tipo não informado");
    }

    $limite = 3000;
    $batch  = 500;

    if ($_FILES['arquivo']['error'] != 0) {
        die("Erro no upload do arquivo");
    }

    $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
    $handle = fopen($arquivo_tmp, "r");

    if (!$handle) {
        die("Erro ao abrir arquivo");
    }

    // DETECTA DELIMITADOR
    $linha_teste = fgets($handle);
    $delimitador = (strpos($linha_teste, ";") !== false) ? ";" : ",";
    rewind($handle);

    // CABEÇALHO
    $cabecalho = fgetcsv($handle, 0, $delimitador);
    $cabecalho = array_map(function ($v) {
        return trim(strtolower($v));
    }, $cabecalho);
    $cabecalho = array_map('strtolower', $cabecalho);

    $map = array_flip($cabecalho);

    $linhas = 0;
    $count  = 0;

    // SQL POR TIPO   
    $sql = null;

    switch ($tipo) {

        case "agency":
            $sql = "INSERT INTO agency 
            (agency_name, agency_url, agency_timezone, agency_lang, agency_phone, agency_fare_url, agency_email, agency_city)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            break;

        case "stops":
            $sql = "INSERT INTO stops 
            (stop_code, stop_name, stop_desc, stop_lat, stop_lon, location_type, parent_station, platform_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            break;

        case "routes":
            $sql = "INSERT INTO routes 
            (agency_id, route_short_name, route_long_name, route_desc, route_type, route_color, route_text_color, route_sort_order, network_id, route_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            break;

        case "trips":
            $sql = "INSERT INTO trips 
           (route_id, service_id, trip_headsign, trip_short_name, direction_id, departure_time, shape_id, departure_location)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            break;

        case "stop_times":
            $sql = "INSERT INTO stop_times 
            (trip_id, arrival_time, departure_time, stop_id, stop_sequence, stop_headsign, timepoint)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
            break;

        case "calendar":
            $sql = "INSERT INTO calendar 
            (service_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            break;

        case "calendar_dates":
            $sql = "INSERT INTO calendar_dates 
            (service_id, date, exception_type)
            VALUES (?, ?, ?)";
            break;

        case "fare_attributes":
            $sql = "INSERT INTO fare_attributes 
            (fare_id, price, currency_type, payment_method, transfers, agency_id)
            VALUES (?, ?, ?, ?, ?, ?)";
            break;

        case "fare_rules":
            $sql = "INSERT INTO fare_rules 
           (fare_id, route_id, origin_id, destination_id)
           VALUES (?, ?, ?, ?)";
            break;

        case "shapes":
            $sql = "INSERT INTO shapes 
           (shape_id, shape_pt_lat, shape_pt_lon, shape_pt_sequence, shape_dist_traveled)
           VALUES (?, ?, ?, ?, ?)";
            break;

        default:
            die("Tipo inválido");
    }

    if (!$sql) {
        die("SQL não definido para o tipo: $tipo");
    }

    $stmt = $conexao->prepare($sql);

    if (!$stmt) {
        die("Erro no prepare: " . $conexao->error);
    }

    $conexao->begin_transaction();

    // LOOP    
    while (($dados = fgetcsv($handle, 0, $delimitador)) !== FALSE) {

        if ($linhas >= $limite) break;

        // ignora linhas vazias
        if (count(array_filter($dados)) == 0) {
            continue;
        }

        switch ($tipo) {

            // AGENCY ++++++++++++++++++++++++++++++++++++++++
            case "agency":

                $agency_name     = $dados[$map['agency_name'] ?? -1] ?? null;
                $agency_url      = $dados[$map['agency_url'] ?? -1] ?? null;
                $agency_timezone = $dados[$map['agency_timezone'] ?? -1] ?? null;
                $agency_lang     = $dados[$map['agency_lang'] ?? -1] ?? null;
                $agency_phone    = $dados[$map['agency_phone'] ?? -1] ?? null;
                $agency_fare_url = $dados[$map['agency_fare_url'] ?? -1] ?? null;
                $agency_email    = $dados[$map['agency_email'] ?? -1] ?? null;
                $agency_city     = $dados[$map['agency_city'] ?? -1] ?? null;

                // validação segura
                if (
                    trim($agency_name ?? '') === '' ||
                    trim($agency_url ?? '') === '' ||
                    trim($agency_timezone ?? '') === ''
                ) {
                    continue 2;
                }

                $stmt->bind_param(
                    "ssssssss",
                    $agency_name,
                    $agency_url,
                    $agency_timezone,
                    $agency_lang,
                    $agency_phone,
                    $agency_fare_url,
                    $agency_email,
                    $agency_city
                );

                break;

            // STOPS ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            case "stops":

                $stop_code = $dados[$map['stop_code'] ?? -1] ?? null;
                $stop_name = $dados[$map['stop_name'] ?? -1] ?? null;
                $stop_desc = $dados[$map['stop_desc'] ?? -1] ?? null;

                $stop_lat_raw = $dados[$map['stop_lat'] ?? -1]
                    ?? $dados[$map['stop_latitude'] ?? -1]
                    ?? $dados[$map['lat'] ?? -1]
                    ?? null;

                $stop_lon_raw = $dados[$map['stop_lon'] ?? -1]
                    ?? $dados[$map['stop_longitude'] ?? -1]
                    ?? $dados[$map['lon'] ?? -1]
                    ?? null;

                // CORREÇÃO
                $stop_lat = ajustarCoordenada($stop_lat_raw, 'lat');
                $stop_lon = ajustarCoordenada($stop_lon_raw, 'lon');

                $location_type  = isset($map['location_type']) ? (int)($dados[$map['location_type']] ?? 0) : 0;
                $parent_station = $dados[$map['parent_station'] ?? -1] ?? null;
                $platform_code  = $dados[$map['platform_code'] ?? -1] ?? null;

                // VALIDAÇÃO FORTE
                if (
                    trim($stop_name ?? '') === '' ||
                    $stop_lat === null ||
                    $stop_lon === null
                ) {
                    continue 2;
                }

                $stmt->bind_param(
                    "sssssiss",
                    $stop_code,
                    $stop_name,
                    $stop_desc,
                    $stop_lat,
                    $stop_lon,
                    $location_type,
                    $parent_station,
                    $platform_code
                );

                break;

            // ROUTES ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            case "routes":

                $agency_id        = isset($map['agency_id']) ? (int)($dados[$map['agency_id']] ?? 0) : 0;
                $route_short_name = $dados[$map['route_short_name'] ?? -1] ?? null;
                $route_long_name  = $dados[$map['route_long_name'] ?? -1] ?? null;
                $route_desc       = $dados[$map['route_desc'] ?? -1] ?? null;
                $route_type       = isset($map['route_type']) ? (int)($dados[$map['route_type']] ?? 0) : 0;
                $route_color      = $dados[$map['route_color'] ?? -1] ?? null;
                $route_text_color = $dados[$map['route_text_color'] ?? -1] ?? null;
                $route_sort_order = isset($map['route_sort_order']) ? (int)($dados[$map['route_sort_order']] ?? 0) : 0;
                $network_id       = $dados[$map['network_id'] ?? -1] ?? null;
                $route_status     = $dados[$map['route_status'] ?? -1] ?? 'A';

                // VALIDAÇÃO (OBRIGATÓRIOS)
                if (
                    $agency_id <= 0 ||
                    $route_type < 0 ||
                    trim($network_id ?? '') === ''
                ) {
                    continue 2;
                }

                $stmt->bind_param(
                    "isssississ",
                    $agency_id,
                    $route_short_name,
                    $route_long_name,
                    $route_desc,
                    $route_type,
                    $route_color,
                    $route_text_color,
                    $route_sort_order,
                    $network_id,
                    $route_status
                );

                break;

            // TRIPS ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++    
            case "trips":

                $route_id   = isset($map['route_id']) ? (int)($dados[$map['route_id']] ?? 0) : 0;
                $service_id = $dados[$map['service_id'] ?? -1] ?? null;

                $trip_headsign   = $dados[$map['trip_headsign'] ?? -1] ?? null;
                $trip_short_name = $dados[$map['trip_short_name'] ?? -1] ?? null;

                $direction_id = isset($map['direction_id']) && trim($dados[$map['direction_id']] ?? '') !== ''
                    ? (int)$dados[$map['direction_id']]
                    : null;

                $departure_time = $dados[$map['departure_time'] ?? -1] ?? null;
                $shape_id       = $dados[$map['shape_id'] ?? -1] ?? null;
                $departure_location = $dados[$map['departure_location'] ?? -1] ?? null;

                // 🚀 VALIDAÇÃO (OBRIGATÓRIOS)
                if (
                    $route_id <= 0 ||
                    trim($service_id ?? '') === '' ||
                    trim($shape_id ?? '') === ''
                ) {
                    continue 2;
                }

                // 🚀 TRATAR TIME VAZIO
                if (trim($departure_time ?? '') === '') {
                    $departure_time = null;
                }

                $stmt->bind_param(
                    "isssisss",
                    $route_id,
                    $service_id,
                    $trip_headsign,
                    $trip_short_name,
                    $direction_id,
                    $departure_time,
                    $shape_id,
                    $departure_location
                );

                break;

            // STOP TIMES ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++     
            case "stop_times":

                $trip_id = isset($map['trip_id']) ? (int)($dados[$map['trip_id']] ?? 0) : 0;

                $arrival_time = $dados[$map['arrival_time'] ?? -1] ?? null;
                $departure_time = $dados[$map['departure_time'] ?? -1] ?? null;

                $stop_id = isset($map['stop_id']) ? (int)($dados[$map['stop_id']] ?? 0) : 0;
                $stop_sequence = isset($map['stop_sequence']) ? (int)($dados[$map['stop_sequence']] ?? 0) : 0;

                $stop_headsign = $dados[$map['stop_headsign'] ?? -1] ?? null;

                $timepoint = isset($map['timepoint']) && trim($dados[$map['timepoint']] ?? '') !== ''
                    ? (int)$dados[$map['timepoint']]
                    : null;

                // 🚀 TRATAR HORÁRIOS
                if (trim($arrival_time ?? '') === '') {
                    $arrival_time = null;
                }

                if (trim($departure_time ?? '') === '') {
                    $departure_time = null;
                }

                // 🚀 VALIDAÇÃO (OBRIGATÓRIOS)
                if (
                    $trip_id <= 0 ||
                    $stop_id <= 0 ||
                    $stop_sequence <= 0
                ) {
                    continue 2;
                }

                $stmt->bind_param(
                    "issiiis",
                    $trip_id,
                    $arrival_time,
                    $departure_time,
                    $stop_id,
                    $stop_sequence,
                    $stop_headsign,
                    $timepoint
                );

                break;

            // CALENDAR ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ 
            case "calendar":

                $service_id = $dados[$map['service_id'] ?? -1] ?? null;

                $monday    = isset($map['monday']) ? (int)($dados[$map['monday']] ?? 0) : 0;
                $tuesday   = isset($map['tuesday']) ? (int)($dados[$map['tuesday']] ?? 0) : 0;
                $wednesday = isset($map['wednesday']) ? (int)($dados[$map['wednesday']] ?? 0) : 0;
                $thursday  = isset($map['thursday']) ? (int)($dados[$map['thursday']] ?? 0) : 0;
                $friday    = isset($map['friday']) ? (int)($dados[$map['friday']] ?? 0) : 0;
                $saturday  = isset($map['saturday']) ? (int)($dados[$map['saturday']] ?? 0) : 0;
                $sunday    = isset($map['sunday']) ? (int)($dados[$map['sunday']] ?? 0) : 0;

                $start_date_raw = $dados[$map['start_date'] ?? -1] ?? null;
                $end_date_raw   = $dados[$map['end_date'] ?? -1] ?? null;

                // 🚀 VALIDAÇÃO OBRIGATÓRIA
                if (
                    trim($service_id ?? '') === '' ||
                    trim($start_date_raw ?? '') === '' ||
                    trim($end_date_raw ?? '') === ''
                ) {
                    continue 2;
                }

                // START DATE
                if (preg_match('/^\d{8}$/', $start_date_raw)) {
                    $start_date = substr($start_date_raw, 0, 4) . '-' .
                        substr($start_date_raw, 4, 2) . '-' .
                        substr($start_date_raw, 6, 2);
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date_raw)) {
                    $start_date = $start_date_raw;
                } else {
                    $start_date = null;
                }

                // END DATE
                if (preg_match('/^\d{8}$/', $end_date_raw)) {
                    $end_date = substr($end_date_raw, 0, 4) . '-' .
                        substr($end_date_raw, 4, 2) . '-' .
                        substr($end_date_raw, 6, 2);
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date_raw)) {
                    $end_date = $end_date_raw;
                } else {
                    $end_date = null;
                }
                // 🚀 SE DER ERRO NA DATA → IGNORA
                if ($start_date === null || $end_date === null) {
                    continue 2;
                }

                $stmt->bind_param(
                    "siiiiiiiss",
                    $service_id,
                    $monday,
                    $tuesday,
                    $wednesday,
                    $thursday,
                    $friday,
                    $saturday,
                    $sunday,
                    $start_date,
                    $end_date
                );

                break;

            // CALENDAR DATES ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++    
            case "calendar_dates":

                $service_id = $dados[$map['service_id'] ?? -1] ?? null;
                $date_raw   = $dados[$map['date'] ?? -1] ?? null;

                $exception_type = isset($map['exception_type'])
                    ? (int)($dados[$map['exception_type']] ?? 0)
                    : 0;

                // 🚀 VALIDAÇÃO OBRIGATÓRIA
                if (
                    trim($service_id ?? '') === '' ||
                    trim($date_raw ?? '') === ''
                ) {
                    continue 2;
                }

                // 🚀 CONVERTE DATA (ACEITA 2 FORMATOS)
                $date = null;

                // formato GTSF: 20260101
                if (preg_match('/^\d{8}$/', $date_raw)) {
                    $date = substr($date_raw, 0, 4) . '-' .
                        substr($date_raw, 4, 2) . '-' .
                        substr($date_raw, 6, 2);
                }
                // formato já pronto: 2026-01-01
                elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_raw)) {
                    $date = $date_raw;
                }

                // 🚀 SE DATA INVÁLIDA → IGNORA
                if ($date === null) {
                    continue 2;
                }

                // 🚀 VALIDA exception_type (GTSF só aceita 1 ou 2)
                if (!in_array($exception_type, [1, 2])) {
                    continue 2;
                }

                $stmt->bind_param(
                    "ssi",
                    $service_id,
                    $date,
                    $exception_type
                );

                break;

            // FARE ATTRIBUTES ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++    
            case "fare_attributes":

                $fare_id = $dados[$map['fare_id'] ?? -1] ?? null;
                $price   = $dados[$map['price'] ?? -1] ?? null;
                $currency_type = $dados[$map['currency_type'] ?? -1] ?? null;

                $payment_method = isset($map['payment_method'])
                    ? (int)($dados[$map['payment_method']] ?? 0)
                    : 0;

                $transfers = isset($map['transfers'])
                    ? (int)($dados[$map['transfers']] ?? null)
                    : null;

                $agency_id = isset($map['agency_id'])
                    ? (int)($dados[$map['agency_id']] ?? null)
                    : null;

                // 🚀 VALIDAÇÃO OBRIGATÓRIA
                if (
                    trim($fare_id ?? '') === '' ||
                    trim($price ?? '') === '' ||
                    trim($currency_type ?? '') === ''
                ) {
                    continue;
                }

                // 🚀 NORMALIZA PREÇO
                $price = str_replace(',', '.', $price);
                $price = (float)$price;

                if ($price < 0) {
                    continue;
                }

                // 🚀 VALIDA payment_method (0 ou 1)
                if (!in_array($payment_method, [0, 1])) {
                    $payment_method = 0;
                }

                // 🚀 transfers pode ser NULL ou 0,1,2
                if (!in_array($transfers, [0, 1, 2])) {
                    $transfers = null;
                }

                // 🚀 agency_id pode ser NULL
                if ($agency_id <= 0) {
                    $agency_id = null;
                }

                $stmt->bind_param(
                    "sdsiii",
                    $fare_id,
                    $price,
                    $currency_type,
                    $payment_method,
                    $transfers,
                    $agency_id
                );

                $executar = true;

                break;

            // FARE RULES ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++    
            case "fare_rules":

                $fare_id = $dados[$map['fare_id'] ?? -1] ?? null;

                $route_id = isset($map['route_id'])
                    ? (int)($dados[$map['route_id']] ?? null)
                    : null;

                $origin_id = $dados[$map['origin_id'] ?? -1] ?? null;
                $destination_id = $dados[$map['destination_id'] ?? -1] ?? null;

                // 🚀 VALIDAÇÃO OBRIGATÓRIA
                if (trim($fare_id ?? '') === '') {
                    continue 2;
                }

                // 🚀 route_id pode ser NULL
                if ($route_id <= 0) {
                    $route_id = null;
                }

                // 🚀 limpa vazios
                if (trim($origin_id ?? '') === '') {
                    $origin_id = null;
                }

                if (trim($destination_id ?? '') === '') {
                    $destination_id = null;
                }

                $stmt->bind_param(
                    "siss",
                    $fare_id,
                    $route_id,
                    $origin_id,
                    $destination_id
                );

                $executar = true;

                break;

            // SHAPE ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            case "shapes":

                $shape_id = $dados[$map['shape_id'] ?? -1] ?? null;

                $lat_raw = $dados[$map['shape_pt_lat'] ?? -1] ?? null;
                $lon_raw = $dados[$map['shape_pt_lon'] ?? -1] ?? null;

                $shape_pt_sequence = isset($map['shape_pt_sequence'])
                    ? (int)($dados[$map['shape_pt_sequence']] ?? 0)
                    : 0;

                $shape_dist_traveled = isset($map['shape_dist_traveled'])
                    ? (float)($dados[$map['shape_dist_traveled']] ?? null)
                    : null;

                // 🚀 CONVERSÃO
                $shape_pt_lat = ajustarShapeCoord($lat_raw);
                $shape_pt_lon = ajustarShapeCoord($lon_raw);

                // 🚀 VALIDAÇÃO FORTE
                if (
                    trim($shape_id ?? '') === '' ||
                    $shape_pt_lat === null ||
                    $shape_pt_lon === null ||
                    $shape_pt_sequence <= 0
                ) {
                    continue 2;
                }

                $stmt->bind_param(
                    "sddid",
                    $shape_id,
                    $shape_pt_lat,
                    $shape_pt_lon,
                    $shape_pt_sequence,
                    $shape_dist_traveled
                );

                $executar = true;

                break;
        }

        $stmt->execute();

        $linhas++;
        $count++;

        if ($count >= $batch) {
            $conexao->commit();
            $conexao->begin_transaction();
            $count = 0;
        }
    }

    $conexao->commit();
    fclose($handle);

    header("Location: import.php?msg=Importado o arquivo $tipo: $linhas registros");
    exit;
}
