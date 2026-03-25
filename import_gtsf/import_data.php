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

    header("Location: import.php?msg=Importado ($tipo): $linhas registros");
    exit;
}
