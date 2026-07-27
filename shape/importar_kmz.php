<?php

header("Content-Type: application/json");

if (!isset($_FILES['arquivo'])) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Arquivo não enviado."
    ]);
    exit;
}

$arquivo = $_FILES['arquivo'];

$ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

$kml = "";


 // IMPORTAR KML
if ($ext == "kml") {

    $kml = file_get_contents($arquivo['tmp_name']);

}

// IMPORTAR KMZ
elseif ($ext == "kmz") {

    $zip = new ZipArchive();

    if ($zip->open($arquivo['tmp_name']) !== TRUE) {

        echo json_encode([
            "status"=>"erro",
            "mensagem"=>"Não foi possível abrir o KMZ."
        ]);

        exit;
    }

    for($i=0;$i<$zip->numFiles;$i++){

        $nome = $zip->getNameIndex($i);

        if(strtolower(pathinfo($nome,PATHINFO_EXTENSION))=="kml"){

            $kml = $zip->getFromIndex($i);

            break;
        }

    }

    $zip->close();

}

else{

    echo json_encode([
        "status"=>"erro",
        "mensagem"=>"Formato inválido."
    ]);

    exit;

}

if(empty($kml)){

    echo json_encode([
        "status"=>"erro",
        "mensagem"=>"Arquivo KML não encontrado."
    ]);

    exit;

}

// LER XML
libxml_use_internal_errors(true);

$xml = simplexml_load_string($kml);

if(!$xml){

    echo json_encode([
        "status"=>"erro",
        "mensagem"=>"Erro ao ler o XML."
    ]);

    exit;

}

// PEGAR COORDENADAS
$xml->registerXPathNamespace("kml","http://www.opengis.net/kml/2.2");

$nodes = $xml->xpath("//kml:coordinates");

if(!$nodes){

    $nodes = $xml->xpath("//coordinates");
}

if(!$nodes){

    echo json_encode([
        "status"=>"erro",
        "mensagem"=>"Nenhuma coordenada encontrada."
    ]);

    exit;

}

$coords=[];

foreach($nodes as $node){

    $lista = preg_split('/\s+/', trim((string)$node));

    foreach($lista as $coord){

        if(empty($coord)){
            continue;
        }

        $p = explode(",",$coord);

        if(count($p)<2){
            continue;
        }

        $lon = (float)$p[0];
        $lat = (float)$p[1];

        // Leaflet trabalha em LAT,LON
        $coords[] = [$lat,$lon];

    }

}

echo json_encode($coords);