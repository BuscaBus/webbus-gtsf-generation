<?php
include("../connection.php");

if (isset($_GET['fare_id'])) {
    $fare_id = $_GET['fare_id'];

    $sql = "SELECT fare_id, price, FORMAT(price, 2) AS price_format FROM fare_attributes WHERE fare_id = '$fare_id'";
    $result = mysqli_query($conexao, $sql);

    // $options = "<option value=''></option>";

    while ($row = mysqli_fetch_assoc($result)) {
        $price = $row['price_format'];
        $id = $row['fare_id'];
        $options .= "<option value='$id'>R$ $price</option>";
    }

    echo $options;
}
?>
