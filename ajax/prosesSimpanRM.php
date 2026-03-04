<?php
require_once '../function/configDB.php';
header('Content-Type: application/json');

$nik       = $_POST['nik'] ?? '';
$noka      = $_POST['noka'] ?? '';
$nama      = $_POST['nama'] ?? '';
$tgl_lahir = $_POST['tgl_lahir'] ?? '';
$jk        = $_POST['jk'] ?? '';
$alamat    = $_POST['alamat'] ?? '';
$no_hp     = $_POST['no_hp'] ?? '';

$kd_prop = $_POST['kd_prop'] ?? '';
$kd_kab  = $_POST['kd_kab']  ?? '';
$kd_kec  = $_POST['kd_kec']  ?? '';
$kd_kel  = $_POST['kd_kel']  ?? '';

// $kd_prop = $_POST['kd_prop'] ?? '';
// $kd_kab  = $_POST['kd_kab'] ?? '';
// $kd_kec  = $_POST['kd_kec'] ?? '';
// $kd_kel  = $_POST['kd_kel'] ?? '';

// $kd_prop = "1";
// $kd_kab  = "1";
// $kd_kec  = "1";
// $kd_kel  = "1";

$nm_prop = $_POST['nm_prop'] ?? '';
$nm_kab  = $_POST['nm_kab'] ?? '';
$nm_kec  = $_POST['nm_kec'] ?? '';
$nm_kel  = $_POST['nm_kel'] ?? '';

// var_dump($noka);
if ($nik == '' || $nama == '' || $tgl_lahir == '') {
    die("Data tidak lengkap");
}

/*
|--------------------------------------------------------------------------
| GENERATE NO_RKM_MEDIS OTOMATIS
|--------------------------------------------------------------------------
*/

$result = mysqli_query($conn, "SELECT MAX(no_rkm_medis) as max_rm FROM pasien");
$row = mysqli_fetch_assoc($result);

$last = $row['max_rm'];
$new_rm = str_pad((int)$last + 1, 6, '0', STR_PAD_LEFT);

/*
|--------------------------------------------------------------------------
| HITUNG UMUR
|--------------------------------------------------------------------------
*/

$umur = date_diff(date_create($tgl_lahir), date_create('today'))->y . " Th";

/*
|--------------------------------------------------------------------------
| INSERT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
INSERT INTO pasien (
    no_rkm_medis,
    nm_pasien,
    no_ktp,
    jk,
    tmp_lahir,
    tgl_lahir,
    alamat,
    alamatpj,
    no_tlp,
    umur,
    kd_pj,
    no_peserta,
    kd_kel,
    kd_kec,
    kd_kab,
    kd_prop,
    propinsipj,
    kelurahanpj,
    kecamatanpj,
    kabupatenpj,
    nm_ibu,
    namakeluarga,
    pekerjaanpj,
    perusahaan_pasien,
    suku_bangsa,
    bahasa_pasien,
    cacat_fisik,
    email,
    nip,
    pnd,
    tgl_daftar
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");



$kd_pj = "BPJ";
$nm_ibu = "-";
$pekerjaanpj = "-";
$perusahaan = "-";
$suku = 1;
$bahasa = 1;
$cacat = 1;
$email = "-";
$nip = "-";
$pnd = "-";
$namakeluarga = $nama;
$alamatpj = $alamat; // biasanya sama dengan alamat pasien
$no_peserta = $noka;
$tmp_lahir="-";
$tgl_daftar = date('Y-m-d');


$kd_prop = is_numeric($kd_prop) ? (int)$kd_prop : 0;
$kd_kab  = is_numeric($kd_kab)  ? (int)$kd_kab  : 0;
$kd_kec  = is_numeric($kd_kec)  ? (int)$kd_kec  : 0;
$kd_kel  = is_numeric($kd_kel)  ? (int)$kd_kel  : 0;


$stmt->bind_param(
    "ssssssssssssiisssssssssssssssss",
    $new_rm,        // 1
    $nama,          // 2
    $nik,           // 3
    $jk,            // 4
    $tmp_lahir,
    $tgl_lahir,     // 5
    $alamat,        // 6
    $alamatpj,      // 7
    $no_hp,         // 8
    $umur,          // 9
    $kd_pj,         // 10 (string tapi boleh s juga)
    $noka,
    $kd_kel,        // 11
    $kd_kec,        // 12
    $kd_kab,        // 13
    $kd_prop,       // 14
    $nm_prop,       // 15
    $nm_kel,        // 16
    $nm_kec,        // 17
    $nm_kab,        // 18
    $nm_ibu,        // 19
    $namakeluarga,  // 20
    $pekerjaanpj,   // 21
    $perusahaan,    // 22
    $suku,          // 23
    $bahasa,        // 24
    $cacat,         // 25
    $email,         // 26
    $nip,           // 27
    $pnd,            // 28
    $tgl_daftar
);

if ($stmt->execute()) {

    echo json_encode([
        "status" => "success",
        "nik" => $nik,
        "noka" => $noka,
        "nama" => $nama,
        "tgl_lahir" => $tgl_lahir,
        "jk" => $jk,
        "alamat" => $alamat
    ]);

} else {

    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);

}

exit;