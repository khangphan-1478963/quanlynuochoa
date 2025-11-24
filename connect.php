<?php
$host = "localhost";
$username = "root";
$password = "vertrigo";
$database = "da_ltw";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Không thể kết nối dữ liệu: ".mysqli_connect-error());
}
mysqli_query($conn, "SET NAMES 'utf8'");
?>
