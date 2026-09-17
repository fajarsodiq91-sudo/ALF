<?php
$host     = "localhost";
$user     = "u627878615_db_santri"; // Sesuaikan dengan user database Anda
$password = "Fadi@179179";       // Sesuaikan dengan password database Anda
$database = "u12345_db_santri";   // Sesuaikan dengan nama database Anda

$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>