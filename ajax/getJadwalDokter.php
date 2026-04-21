<?php
declare(strict_types=1);

require_once '../function/configDB.php';

$requestedDay = strtoupper(anjungan_get('hari'));
$hari = $requestedDay !== '' ? $requestedDay : anjungan_day_name();

if (!anjungan_valid_schedule_day($hari)) {
    anjungan_fail('Hari jadwal tidak valid');
}

try {
    $sql = "
        SELECT
            p.nm_poli,
            d.nm_dokter,
            j.hari_kerja,
            j.jam_mulai,
            j.jam_selesai,
            j.kuota
        FROM jadwal j
        INNER JOIN dokter d ON d.kd_dokter = j.kd_dokter
        INNER JOIN poliklinik p ON p.kd_poli = j.kd_poli
        WHERE j.hari_kerja = ?
        ORDER BY j.jam_mulai, p.nm_poli, d.nm_dokter
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $hari);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'poli' => $row['nm_poli'],
            'dokter' => $row['nm_dokter'],
            'hari' => $row['hari_kerja'],
            'jam_mulai' => substr((string) $row['jam_mulai'], 0, 5),
            'jam_selesai' => substr((string) $row['jam_selesai'], 0, 5),
            'kuota' => (int) $row['kuota'],
        ];
    }

    anjungan_json([
        'status' => true,
        'hari' => $hari,
        'total' => count($data),
        'data' => $data,
    ]);
} catch (Throwable $exception) {
    anjungan_fail('Gagal memuat jadwal dokter', 500);
}
