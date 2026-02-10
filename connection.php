<?php

    $server = "localhost";
    $user = "root";
    $password = "";
    $dbname = "webbus_gtsf-test"; 
    
    // criar conexão 
    $conexao = mysqli_connect($server, $user, $password, $dbname);

    // verificar conexão
    if ($conexao->connect_errno) {
       echo "Conexão falhou: (" . $conexao->connect_errno . ")" . $conexao->connect_errno;
    }
    
?>