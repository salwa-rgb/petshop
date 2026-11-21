<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "dbpetshop_2559201015";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysql_connect_eror());
}