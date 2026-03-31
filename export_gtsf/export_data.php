<?php
include("../connection.php");

// Receber dados
$table  = $_GET['table'] ?? '';
$format = $_GET['format'] ?? 'txt';

// Tabelas permitidas
$allowed_tables = ['agency', 'stops', 'routes', 'trips', 'stop_times', 'calendar', 'calendar_dates', 'fare_attributes', 'fare_rules', 'shapes'];

// Colunas fixas por tabela (PADRÃO GTSF)
$gtsf_columns = [

    'agency' => [
        'agency_id',
        'agency_name',
        'agency_url',
        'agency_timezone',
        'agency_lang',
        'agency_phone',
        'agency_fare_url',
        'agency_email'        
    ],

     'stops' => [
        'stop_id',
        'stop_code',
        'stop_name',
        'stop_desc',
        'stop_lat',
        'stop_lon',
        'location_type',
        'parent_station',
        'platform_code'
    ],

    'routes' => [
        'route_id',
        'agency_id',
        'route_short_name',
        'route_long_name',
        'route_desc',
        'route_type',
        'route_color',
        'route_text_color',
        'route_sort_order',
        'network_id'
    ],

    'trips' => [
        'route_id',
        'service_id',
        'trip_id',
        'trip_headsign',
        'trip_short_name',
        'direction_id',
        'shape_id'  
    ],

    'stop_times' => [
        'trip_id',
        'arrival_time',
        'departure_time',
        'stop_id',
        'stop_sequence',
        'stop_headsign',
        'timepoint'  
    ],

    'calendar' => [
        'service_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'start_date',
        'end_date'  
    ],
    
    'calendar_dates' => [
        'service_id',
        'date',
        'exception_type'         
    ],

    'fare_attributes' => [
        'fare_id',
        'price',
        'currency_type',
        'payment_method',
        'transfers',
        'agency_id'        
    ],

    'fare_rules' => [
        'fare_id',
        'route_id',
        'origin_id',
        'destination_id'                
    ],
    
    'shapes' => [
        'shape_id',
        'shape_pt_lat',
        'shape_pt_lon',
        'shape_pt_sequence',  
        'shape_dist_traveled'              
    ],   

];

// Validação
if (!in_array($table, $allowed_tables)) {
    die("Tabela inválida");
}

if (!isset($gtsf_columns[$table])) {
    die("Colunas não definidas");
}

// Colunas da tabela
$columns = $gtsf_columns[$table];

// Montar SELECT seguro
$columns_sql = implode(",", array_map(function($col) {
    return "`$col`";
}, $columns));

// Nome do arquivo
$filename = $table . "." . $format;

// Headers
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=$filename");

// Abrir saída
$output = fopen('php://output', 'w');

// Cabeçalho
fputcsv($output, $columns);

// Buscar dados
$sql = "SELECT $columns_sql FROM $table";
$result = mysqli_query($conexao, $sql);

// Escrever dados
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}

fclose($output);
exit;