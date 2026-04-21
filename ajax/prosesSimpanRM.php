<?php
declare(strict_types=1);

require_once '../function/configDB.php';

anjungan_require_post();

$nik = anjungan_post('nik');
$noka = anjungan_post('noka');
$nama = anjungan_post('nama');
$birthDate = anjungan_normalize_date(anjungan_post('tgl_lahir'));
$gender = anjungan_normalize_gender(anjungan_post('jk'));
$alamat = anjungan_post('alamat');
$phone = anjungan_post('no_hp');

$kdProp = anjungan_post('kd_prop', '0');
$kdKab = anjungan_post('kd_kab', '0');
$kdKec = anjungan_post('kd_kec', '0');
$kdKel = anjungan_post('kd_kel', '0');

$nmProp = anjungan_post('nm_prop');
$nmKab = anjungan_post('nm_kab');
$nmKec = anjungan_post('nm_kec');
$nmKel = anjungan_post('nm_kel');

if ($nik === '' || $nama === '' || $birthDate === '' || $gender === '' || $alamat === '' || $phone === '') {
    anjungan_json([
        'status' => 'error',
        'message' => 'Data pasien belum lengkap',
    ]);
}

if (!anjungan_valid_phone($phone)) {
    anjungan_json([
        'status' => 'error',
        'message' => 'Nomor HP tidak valid',
    ]);
}

try {
    $payload = anjungan_transaction($conn, function (mysqli $conn) use (
        $nik,
        $noka,
        $nama,
        $birthDate,
        $gender,
        $alamat,
        $phone,
        $kdProp,
        $kdKab,
        $kdKec,
        $kdKel,
        $nmProp,
        $nmKab,
        $nmKec,
        $nmKel
    ): array {
        $duplicateStmt = $conn->prepare("
            SELECT no_rkm_medis
            FROM pasien
            WHERE no_ktp = ?
               OR (? <> '' AND no_peserta = ?)
            LIMIT 1
            FOR UPDATE
        ");
        $duplicateStmt->bind_param('sss', $nik, $noka, $noka);
        $duplicateStmt->execute();
        $existing = $duplicateStmt->get_result()->fetch_assoc();

        if ($existing) {
            throw new RuntimeException('Pasien sudah memiliki rekam medis dengan nomor ' . $existing['no_rkm_medis']);
        }

        $lastRmStmt = $conn->query("
            SELECT no_rkm_medis
            FROM pasien
            ORDER BY CAST(no_rkm_medis AS UNSIGNED) DESC
            LIMIT 1
            FOR UPDATE
        ");
        $lastRmRow = $lastRmStmt->fetch_assoc();
        $nextRm = ((int) ($lastRmRow['no_rkm_medis'] ?? 0)) + 1;
        $medicalRecordNumber = str_pad((string) $nextRm, 8, '0', STR_PAD_LEFT);

        $age = anjungan_calculate_age($birthDate);
        $registeredAt = date('Y-m-d');

        $insertStmt = $conn->prepare("
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
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $birthPlace = '-';
        $guarantorAddress = $alamat;
        $payerCode = 'BPJ';
        $motherName = '-';
        $familyName = $nama;
        $guarantorJob = '-';
        $company = '-';
        $ethnicity = '1';
        $language = '1';
        $disability = '1';
        $email = '-';
        $employeeNumber = '-';
        $education = '-';

        $insertStmt->bind_param(
            'sssssssssssssssssssssssssssssss',
            $medicalRecordNumber,
            $nama,
            $nik,
            $gender,
            $birthPlace,
            $birthDate,
            $alamat,
            $guarantorAddress,
            $phone,
            $age['label'],
            $payerCode,
            $noka,
            $kdKel,
            $kdKec,
            $kdKab,
            $kdProp,
            $nmProp,
            $nmKel,
            $nmKec,
            $nmKab,
            $motherName,
            $familyName,
            $guarantorJob,
            $company,
            $ethnicity,
            $language,
            $disability,
            $email,
            $employeeNumber,
            $education,
            $registeredAt
        );
        $insertStmt->execute();

        return [
            'nik' => $nik,
            'noka' => $noka,
            'nama' => $nama,
            'tgl_lahir' => $birthDate,
            'jk' => $gender,
            'alamat' => $alamat,
            'no_hp' => $phone,
            'no_rkm_medis' => $medicalRecordNumber,
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
} catch (Throwable $exception) {
    anjungan_json([
        'status' => 'error',
        'message' => 'Terjadi kesalahan sistem',
    ], 500);
}
