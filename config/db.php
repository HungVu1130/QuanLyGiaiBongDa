<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "league_football";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
?>
