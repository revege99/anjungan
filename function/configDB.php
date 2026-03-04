<?php 
$conn = mysqli_connect("localhost", "root", "s1ntluc14", "sik_tester_lintong");

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Fungsi untuk query biasa (mengembalikan array hasil)
function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}


 ?>