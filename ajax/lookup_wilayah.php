<?php
require_once '../function/configDB.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);

$level = $input['level'] ?? '';
$nama  = trim($input['nama'] ?? '');

if ($nama === '') {
    echo json_encode(['kd' => 0]);
    exit;
}

// =============================
// MAPPING LEVEL
// =============================
switch ($level) {

    case 'propinsi':
        $table = 'propinsi';
        $colKd = 'kd_prop';
        $colNm = 'nm_prop';
        break;

    case 'kabupaten':
        $table = 'kabupaten';
        $colKd = 'kd_kab';
        $colNm = 'nm_kab';
        break;

    case 'kecamatan':
        $table = 'kecamatan';
        $colKd = 'kd_kec';
        $colNm = 'nm_kec';
        break;

    case 'kelurahan':
        $table = 'kelurahan';
        $colKd = 'kd_kel';
        $colNm = 'nm_kel';
        break;

    default:
        echo json_encode(['kd' => 0]);
        exit;
}

// =============================
// 1. CARI DULU
// =============================
$sql = "SELECT $colKd FROM $table WHERE $colNm = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nama);
$stmt->execute();
$stmt->bind_result($kd);
$found = $stmt->fetch();
$stmt->close();

// =============================
// 2. JIKA BELUM ADA → INSERT
// =============================
if (!$found) {

    $ins = $conn->prepare(
        "INSERT INTO $table ($colNm) VALUES (?)"
    );
    $ins->bind_param("s", $nama);
    $ins->execute();

    $kd = $conn->insert_id;

    $ins->close();
}

echo json_encode([
    'level' => $level,
    'nama'  => $nama,
    'kd'    => (int)$kd
]);