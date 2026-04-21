<?php
declare(strict_types=1);

require_once '../function/configDB.php';

if (anjungan_get('mode') !== 'getPasien') {
    anjungan_fail('Mode tidak valid', 404);
}

anjungan_require_post();

$nomor = anjungan_post('nomor');

if ($nomor === '') {
    anjungan_fail('Nomor identitas wajib diisi');
}

try {
    $sql = "
        SELECT
            no_rkm_medis,
            nm_pasien,
            no_ktp,
            no_peserta,
            tgl_lahir,
            jk,
            alamat,
            no_tlp
        FROM pasien
        WHERE no_ktp = ? OR no_peserta = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $nomor, $nomor);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    if (!$patient) {
        anjungan_fail('Data pasien tidak ditemukan', 404);
    }

    anjungan_json([
        'status' => true,
        'data' => [
            'no_rkm_medis' => $patient['no_rkm_medis'],
            'nm_pasien' => $patient['nm_pasien'],
            'no_ktp' => $patient['no_ktp'],
            'no_peserta' => $patient['no_peserta'],
            'tgl_lahir' => anjungan_normalize_date((string) $patient['tgl_lahir']),
            'jk' => anjungan_normalize_gender((string) $patient['jk']),
            'alamat' => $patient['alamat'] ?? '',
            'no_tlp' => $patient['no_tlp'] ?? '',
        ],
    ]);
} catch (Throwable $exception) {
    anjungan_fail('Gagal memuat data pasien', 500);
}
