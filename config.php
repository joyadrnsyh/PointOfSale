<?php
// Mulai session
session_start();

// Konfigurasi Database
$host = 'localhost';
$user = 'root';
$pass = ''; // Kosongkan jika tidak ada password
$db   = 'db_tokosayuran';

// Buat Koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek Koneksi
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Fungsi untuk format Rupiah
function format_rupiah($angka){
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>