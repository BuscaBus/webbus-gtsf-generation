<?php
include("../connection.php");

if (isset($_POST['importar'])) {

    $tipo = $_POST['tipo'];

    if ($_FILES['arquivo']['error'] == 0) {

        $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
        $handle = fopen($arquivo_tmp, "r");

        if ($handle !== FALSE) {

            // Detectar separador
            $linha_teste = fgets($handle);
            $delimitador = (strpos($linha_teste, ";") !== false) ? ";" : ",";

            rewind($handle);

            $cabecalho = fgetcsv($handle, 1000, $delimitador);
            $map = array_flip($cabecalho);

            $linhas = 0;

            // 👇 SWITCH AQUI
            while (($dados = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {

                //Arquivos AGENCY ++++++++++++++++++++++++++++++++++++++++++++++
                if ($tipo == "agency") {

                    $agency_name     = $dados[$map['agency_name']] ?? null;
                    $agency_url      = $dados[$map['agency_url']] ?? null;
                    $agency_timezone = $dados[$map['agency_timezone']] ?? null;
                    $agency_lang     = $dados[$map['agency_lang']] ?? null;
                    $agency_phone    = $dados[$map['agency_phone']] ?? null;
                    $agency_fare_url = $dados[$map['agency_fare_url']] ?? null;
                    $agency_email    = $dados[$map['agency_email']] ?? null;

                    $agency_city = null;

                    if (!$agency_name || !$agency_url || !$agency_timezone) continue;

                    $sql = "INSERT INTO agency 
                    (agency_name, agency_url, agency_timezone, agency_lang, agency_phone, agency_fare_url, agency_email, agency_city)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $conexao->prepare($sql);
                    $stmt->bind_param("sssss",
                        $agency_name,
                        $agency_url,
                        $agency_timezone,
                        $agency_lang,
                        $agency_phone,
                        $agency_fare_url,
                        $agency_email,
                        $agency_city
                    );

                    $stmt->execute();
                    $linhas++;
                }

                // Arquivos STOPS ++++++++++++++++++++++++++++++++++++++++++++++++
                elseif ($tipo == "stops") {

                    $stop_id_file   = $dados[$map['stop_id']] ?? null;
                    $stop_code      = $dados[$map['stop_code']] ?? null;
                    $stop_name      = $dados[$map['stop_name']] ?? null;
                    $stop_desc      = $dados[$map['stop_desc']] ?? null;
                    $stop_lat       = $dados[$map['stop_lat']] ?? null;
                    $stop_lon       = $dados[$map['stop_lon']] ?? null;

                    if (!$stop_name || !$stop_lat || !$stop_lon) continue;

                    if (!$stop_code && $stop_id_file) {
                        $stop_code = $stop_id_file;
                    }

                    $sql = "INSERT INTO stops 
                    (stop_code, stop_name, stop_desc, stop_lat, stop_lon)
                    VALUES (?, ?, ?, ?, ?)";

                    $stmt = $conexao->prepare($sql);
                    $stmt->bind_param("sssss",
                        $stop_code,
                        $stop_name,
                        $stop_desc,
                        $stop_lat,
                        $stop_lon
                    );

                    $stmt->execute();
                    $linhas++;
                }
            }

            fclose($handle);

            header("Location: import.php?msg=Importado ($tipo) com sucesso: $linhas registros");
            exit;
        }
    }
}