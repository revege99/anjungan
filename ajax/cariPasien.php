<?php
declare(strict_types=1);

require_once '../function/configDB.php';

$mode = anjungan_get('mode');

try {
    switch ($mode) {
        case 'cari_pasien':
            handle_search_patient($conn);
            break;

        case 'load_poli':
            handle_load_poli($conn);
            break;

        case 'load_dokter':
            handle_load_doctor($conn);
            break;

        case 'simpan_registrasi':
            handle_save_registration($conn);
            break;

        case 'cek_status_antrean_bpjs':
            handle_bpjs_queue_status($conn);
            break;

        default:
            anjungan_fail('Mode tidak valid', 404);
    }
} catch (Throwable $exception) {
    if ($mode === 'simpan_registrasi') {
        anjungan_json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan server',
        ], 500);
    }

    anjungan_fail('Terjadi kesalahan server', 500);
}

function handle_search_patient(mysqli $conn): void
{
    anjungan_require_post();

    $nik = anjungan_post('nik');
    $noka = anjungan_post('noka');

    if ($nik === '' && $noka === '') {
        anjungan_fail('NIK atau nomor kartu wajib diisi');
    }

    $sql = "
        SELECT
            no_rkm_medis,
            nm_pasien,
            tgl_lahir,
            jk,
            no_peserta,
            no_ktp,
            alamat,
            no_tlp
        FROM pasien
        WHERE (? <> '' AND no_ktp = ?)
           OR (? <> '' AND no_peserta = ?)
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $nik, $nik, $noka, $noka);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    if (!$patient) {
        anjungan_json([
            'status' => false,
            'not_registered' => true,
            'message' => 'Pasien belum memiliki rekam medis',
        ]);
    }

    anjungan_json([
        'status' => true,
        'data' => [
            'no_rkm_medis' => $patient['no_rkm_medis'],
            'nm_pasien' => $patient['nm_pasien'],
            'tgl_lahir' => anjungan_normalize_date((string) $patient['tgl_lahir']),
            'jk' => anjungan_normalize_gender((string) $patient['jk']),
            'no_peserta' => $patient['no_peserta'],
            'no_ktp' => $patient['no_ktp'],
            'alamat' => $patient['alamat'] ?? '',
            'no_tlp' => $patient['no_tlp'] ?? '',
        ],
    ]);
}

function handle_load_poli(mysqli $conn): void
{
    $sql = "
        SELECT
            kd_poli,
            nm_poli
        FROM poliklinik
        WHERE status = '1'
        ORDER BY nm_poli
    ";

    $result = $conn->query($sql);
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    anjungan_json([
        'status' => true,
        'data' => $data,
    ]);
}

function handle_load_doctor(mysqli $conn): void
{
    $kdPoli = anjungan_get('kd_poli');

    if ($kdPoli === '') {
        anjungan_fail('Kode poli wajib diisi');
    }

    $hari = anjungan_day_name();

    $sql = "
        SELECT
            j.kd_dokter,
            d.nm_dokter,
            j.jam_mulai,
            j.jam_selesai,
            j.kuota
        FROM jadwal j
        INNER JOIN dokter d ON d.kd_dokter = j.kd_dokter
        WHERE j.kd_poli = ?
          AND j.hari_kerja = ?
        ORDER BY j.jam_mulai, d.nm_dokter
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $kdPoli, $hari);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'kd_dokter' => $row['kd_dokter'],
            'nm_dokter' => $row['nm_dokter'],
            'jam_mulai' => substr((string) $row['jam_mulai'], 0, 5),
            'jam_selesai' => substr((string) $row['jam_selesai'], 0, 5),
            'kuota' => (int) $row['kuota'],
        ];
    }

    anjungan_json([
        'status' => true,
        'hari' => $hari,
        'data' => $data,
    ]);
}

function handle_save_registration(mysqli $conn): void
{
    anjungan_require_post();

    $noRm = anjungan_post('no_rkm_medis');
    $kdPoli = anjungan_post('kd_poli');
    $kdDokter = anjungan_post('kd_dokter');
    $birthDate = anjungan_normalize_date(anjungan_post('tgl_lahir'));
    $registrationDate = date('Y-m-d');
    $registrationTime = date('H:i:s');

    if ($noRm === '' || $kdPoli === '' || $kdDokter === '') {
        anjungan_json([
            'status' => 'error',
            'message' => 'Data registrasi belum lengkap',
        ]);
    }

    try {
        $payload = anjungan_transaction($conn, function (mysqli $conn) use (
            $noRm,
            $kdPoli,
            $kdDokter,
            $birthDate,
            $registrationDate,
            $registrationTime
        ): array {
            $patientStmt = $conn->prepare("
                SELECT
                    no_rkm_medis,
                    nm_pasien,
                    tgl_lahir,
                    jk,
                    no_tlp
                FROM pasien
                WHERE no_rkm_medis = ?
                LIMIT 1
            ");
            $patientStmt->bind_param('s', $noRm);
            $patientStmt->execute();
            $patient = $patientStmt->get_result()->fetch_assoc();

            if (!$patient) {
                throw new RuntimeException('Data pasien tidak ditemukan');
            }

            $effectiveBirthDate = $birthDate !== ''
                ? $birthDate
                : anjungan_normalize_date((string) $patient['tgl_lahir']);

            $scheduleDay = anjungan_day_name();

            $scheduleStmt = $conn->prepare("
                SELECT kuota
                FROM jadwal
                WHERE kd_poli = ?
                  AND kd_dokter = ?
                  AND hari_kerja = ?
                LIMIT 1
            ");
            $scheduleStmt->bind_param('sss', $kdPoli, $kdDokter, $scheduleDay);
            $scheduleStmt->execute();
            $schedule = $scheduleStmt->get_result()->fetch_assoc();

            if (!$schedule) {
                throw new RuntimeException('Jadwal dokter tidak tersedia untuk hari ini');
            }

            $quota = (int) $schedule['kuota'];

            $quotaStmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM reg_periksa
                WHERE tgl_registrasi = ?
                  AND kd_poli = ?
                  AND kd_dokter = ?
            ");
            $quotaStmt->bind_param('sss', $registrationDate, $kdPoli, $kdDokter);
            $quotaStmt->execute();
            $currentQueue = (int) ($quotaStmt->get_result()->fetch_assoc()['total'] ?? 0);

            if ($quota <= 0 || $currentQueue >= $quota) {
                throw new RuntimeException('Kuota dokter hari ini sudah penuh');
            }

            $pendingStmt = $conn->prepare("
                SELECT no_rawat
                FROM reg_periksa
                WHERE no_rkm_medis = ?
                  AND tgl_registrasi = ?
                  AND stts = 'Belum'
                LIMIT 1
                FOR UPDATE
            ");
            $pendingStmt->bind_param('ss', $noRm, $registrationDate);
            $pendingStmt->execute();

            if ($pendingStmt->get_result()->fetch_assoc()) {
                throw new RuntimeException('Pasien sudah terdaftar dan belum dilayani hari ini');
            }

            $prefixStmt = $conn->prepare("
                SELECT kode_prefix
                FROM maping_dokter_kode
                WHERE kd_dokter = ?
                LIMIT 1
            ");
            $prefixStmt->bind_param('s', $kdDokter);
            $prefixStmt->execute();
            $prefixRow = $prefixStmt->get_result()->fetch_assoc();
            $prefix = trim((string) ($prefixRow['kode_prefix'] ?? 'RG'));

            if ($prefix === '') {
                $prefix = 'RG';
            }

            $lastRegStmt = $conn->prepare("
                SELECT no_reg
                FROM reg_periksa
                WHERE tgl_registrasi = ?
                  AND kd_dokter = ?
                ORDER BY no_reg DESC
                LIMIT 1
                FOR UPDATE
            ");
            $lastRegStmt->bind_param('ss', $registrationDate, $kdDokter);
            $lastRegStmt->execute();
            $lastRegRow = $lastRegStmt->get_result()->fetch_assoc();
            $nextRegSequence = extract_sequence((string) ($lastRegRow['no_reg'] ?? '')) + 1;
            $noReg = $prefix . '-' . str_pad((string) $nextRegSequence, 3, '0', STR_PAD_LEFT);

            $lastRawatStmt = $conn->prepare("
                SELECT no_rawat
                FROM reg_periksa
                WHERE tgl_registrasi = ?
                ORDER BY no_rawat DESC
                LIMIT 1
                FOR UPDATE
            ");
            $lastRawatStmt->bind_param('s', $registrationDate);
            $lastRawatStmt->execute();
            $lastRawatRow = $lastRawatStmt->get_result()->fetch_assoc();
            $nextRawatSequence = extract_sequence((string) ($lastRawatRow['no_rawat'] ?? '')) + 1;
            $noRawat = date('Y/m/d', strtotime($registrationDate)) . '/' . str_pad((string) $nextRawatSequence, 6, '0', STR_PAD_LEFT);

            $age = anjungan_calculate_age($effectiveBirthDate, $registrationDate);

            $insertStmt = $conn->prepare("
                INSERT INTO reg_periksa (
                    no_reg,
                    no_rawat,
                    tgl_registrasi,
                    jam_reg,
                    kd_dokter,
                    no_rkm_medis,
                    kd_poli,
                    p_jawab,
                    almt_pj,
                    hubunganpj,
                    biaya_reg,
                    stts,
                    stts_daftar,
                    status_lanjut,
                    kd_pj,
                    umurdaftar,
                    sttsumur,
                    status_bayar,
                    status_poli
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $personInCharge = '-';
            $addressInCharge = '-';
            $relation = '-';
            $registrationFee = '0';
            $status = 'Belum';
            $registrationStatus = 'Lama';
            $serviceStatus = 'Ralan';
            $payerCode = 'BPJ';
            $paymentStatus = 'Belum Bayar';
            $poliStatus = 'Baru';
            $ageValue = (string) $age['value'];
            $ageUnit = (string) $age['unit'];

            $insertStmt->bind_param(
                'sssssssssssssssssss',
                $noReg,
                $noRawat,
                $registrationDate,
                $registrationTime,
                $kdDokter,
                $noRm,
                $kdPoli,
                $personInCharge,
                $addressInCharge,
                $relation,
                $registrationFee,
                $status,
                $registrationStatus,
                $serviceStatus,
                $payerCode,
                $ageValue,
                $ageUnit,
                $paymentStatus,
                $poliStatus
            );
            $insertStmt->execute();

            $receiptStmt = $conn->prepare("
                SELECT
                    rp.no_reg,
                    rp.no_rawat,
                    rp.tgl_registrasi,
                    p.nm_pasien,
                    p.tgl_lahir,
                    p.no_tlp,
                    p.no_ktp,
                    p.no_peserta,
                    p.jk,
                    p.no_rkm_medis,
                    d.nm_dokter,
                    pl.nm_poli
                FROM reg_periksa rp
                INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
                INNER JOIN dokter d ON d.kd_dokter = rp.kd_dokter
                INNER JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
                WHERE rp.no_rawat = ?
                LIMIT 1
            ");
            $receiptStmt->bind_param('s', $noRawat);
            $receiptStmt->execute();
            $receipt = $receiptStmt->get_result()->fetch_assoc();

            if (!$receipt) {
                throw new RuntimeException('Bukti registrasi tidak ditemukan');
            }

            return [
                'tanggal' => anjungan_format_display_date((string) $receipt['tgl_registrasi']),
                'no_reg' => $receipt['no_reg'],
                'no_rawat' => $receipt['no_rawat'],
                'nm_poli' => $receipt['nm_poli'],
                'nm_dokter' => $receipt['nm_dokter'],
                'nm_pasien' => $receipt['nm_pasien'],
                'tgl_lahir' => anjungan_format_display_date((string) $receipt['tgl_lahir']),
                'no_rkm_medis' => $receipt['no_rkm_medis'],
                'no_ktp' => $receipt['no_ktp'] ?? '',
                'no_peserta' => $receipt['no_peserta'] ?? '',
                'jk' => anjungan_gender_label((string) $receipt['jk']),
                'no_tlp' => $receipt['no_tlp'] ?? '-',
            ];
        });

        anjungan_json(array_merge([
            'status' => 'success',
        ], $payload));
    } catch (RuntimeException $exception) {
        anjungan_json([
            'status' => 'error',
            'message' => $exception->getMessage(),
        ]);
    }
}

function handle_bpjs_queue_status(mysqli $conn): void
{
    $noRawat = anjungan_get('no_rawat');

    if ($noRawat === '') {
        anjungan_fail('Nomor rawat wajib diisi');
    }

    $stmt = $conn->prepare("
        SELECT
            id,
            no_rawat,
            tgl_kirim,
            response,
            status_code,
            message,
            created_at
        FROM antrean_terkirim_bpjs
        WHERE no_rawat = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param('s', $noRawat);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        anjungan_json([
            'status' => true,
            'state' => 'waiting',
            'message' => 'Menunggu service BPJS memproses antrean',
            'data' => null,
        ]);
    }

    $statusCode = isset($row['status_code']) ? (int) $row['status_code'] : null;
    $message = trim((string) ($row['message'] ?? ''));
    $normalizedMessage = strtolower($message);
    $responsePayload = json_decode((string) ($row['response'] ?? ''), true);
    $responseMetadataCode = null;
    $responseMetadataMessage = '';

    if (is_array($responsePayload) && isset($responsePayload['metadata'])) {
        $responseMetadataCode = isset($responsePayload['metadata']['code'])
            ? (int) $responsePayload['metadata']['code']
            : null;
        $responseMetadataMessage = strtolower(trim((string) ($responsePayload['metadata']['message'] ?? '')));
    }

    $state = 'error';
    $successfulMessages = ['ok', 'completed', 'success', 'berhasil'];
    $duplicateSuccessMessage = 'peserta sudah terdaftar di poli tersebut pada hari ini';
    $screeningMarkers = [
        'belum skrining',
        'belum melakukan skrining kesehatan',
    ];

    if (
        $statusCode === 200 ||
        $responseMetadataCode === 200 ||
        in_array($normalizedMessage, $successfulMessages, true) ||
        ($statusCode === 201 && $normalizedMessage === $duplicateSuccessMessage) ||
        ($responseMetadataCode === 201 && $responseMetadataMessage === $duplicateSuccessMessage)
    ) {
        $state = 'completed';
    } else {
        foreach ($screeningMarkers as $marker) {
            if (
                str_contains($normalizedMessage, $marker) ||
                str_contains($responseMetadataMessage, $marker)
            ) {
                $state = 'screening_required';
                break;
            }
        }
    }

    anjungan_json([
        'status' => true,
        'state' => $state,
        'message' => $message !== '' ? $message : 'Status antrean BPJS belum diketahui',
        'data' => [
            'id' => (int) $row['id'],
            'no_rawat' => $row['no_rawat'],
            'tgl_kirim' => $row['tgl_kirim'],
            'status_code' => $statusCode,
            'message' => $message,
            'response_metadata_code' => $responseMetadataCode,
            'response_metadata_message' => $responseMetadataMessage,
            'created_at' => $row['created_at'],
        ],
    ]);
}

function extract_sequence(string $value): int
{
    if ($value === '') {
        return 0;
    }

    if (preg_match('/(\d+)$/', $value, $matches) === 1) {
        return (int) $matches[1];
    }

    return 0;
}
