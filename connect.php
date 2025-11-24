<?php
$host = "localhost";
$username = "root";
$password = "vertrigo";
$database = "da_ltw";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Vui lòng xem lại dữ liệu, hiện tại Không thể kết nối database: " . mysqli_connect_error());
}
mysqli_query($conn, "SET NAMES 'utf8'");
?>