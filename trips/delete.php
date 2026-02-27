<?php
include("../connection.php");

if (!isset($_POST['id'])) {
    die("Trip inválida.");
}

$trip_id = mysqli_real_escape_string($conexao, $_POST['id']);

$sql = "DELETE FROM trips WHERE trip_id = '$trip_id'";

if (mysqli_query($conexao, $sql)) {
    header("Location: register.php?id=" . $_POST['id-route']);
    exit;
} else {
    echo "Erro ao excluir a viagem.";
}