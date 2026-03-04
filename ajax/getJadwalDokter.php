<?php
header('Content-Type: application/json');
require_once '../function/configDB.php';

// ============================
// AMBIL HARI
// ============================
if (isset($_GET['hari']) && $_GET['hari'] !== '') {
    $hari = strtoupper($_GET['hari']);
} else {
    $hari = hariIndo(date('l'));
}

// ============================
// QUERY
// ============================
$sql = "
    SELECT
        p.nm_poli,
        d.nm_dokter,
        j.hari_kerja,
        j.jam_mulai,
        j.jam_selesai,
        j.kuota
    FROM jadwal j
    JOIN dokter d ON d.kd_dokter = j.kd_dokter
    JOIN poliklinik p ON j.kd_poli = p.kd_poli
    WHERE j.hari_kerja = ?
    ORDER BY j.jam_mulai
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hari);
$stmt->execute();
$result = $stmt->get_result();

// ============================
// OUTPUT
// ============================
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'poli'       => $row['nm_poli'],
        'dokter'     => $row['nm_dokter'],
        'hari'       => $row['hari_kerja'],
        'jam_mulai'  => substr($row['jam_mulai'], 0, 5),
        'jam_selesai'=> substr($row['jam_selesai'], 0, 5),
        'kuota'      => (int)$row['kuota']
    ];
}

echo json_encode([
    'status' => true,
    'hari'   => $hari,
    'total'  => count($data),
    'data'   => $data
]);

exit;

// ============================
// FUNCTION
// ============================
function hariIndo($day) {
    return match($day) {
        'Monday'    => 'SENIN',
        'Tuesday'   => 'SELASA',
        'Wednesday' => 'RABU',
        'Thursday'  => 'KAMIS',
        'Friday'    => 'JUMAT',
        'Saturday'  => 'SABTU',
        'Sunday'    => 'AKHAD',
        default     => 'SENIN'
    };
}