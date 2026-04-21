<?php
declare(strict_types=1);

require_once '../../vendor/autoload.php';
require_once '../../function/bpjs_config.php';

use LZCompressor\LZString;

$jenis = anjungan_get('jenis');
$nomor = anjungan_get('nomor');

if ($jenis === '' || $nomor === '') {
    anjungan_fail('Parameter pencarian belum lengkap');
}

if (!in_array($jenis, ['nik', 'noka'], true)) {
    anjungan_fail('Jenis pencarian tidak valid');
}

$timestamp = (string) time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . '&' . $timestamp, $secret_key, true));
$authorization = 'Basic ' . base64_encode($auth_user . ':' . $auth_pass . ':' . $kd_aplikasi);
$url = $base_url . 'peserta/' . $jenis . '/' . rawurlencode($nomor);

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-cons-id: ' . $cons_id,
        'X-timestamp: ' . $timestamp,
        'X-signature: ' . $signature,
        'X-authorization: ' . $authorization,
        'user_key: ' . $user_key,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false || curl_errno($ch)) {
    curl_close($ch);
    anjungan_fail('Gagal menghubungi layanan BPJS', 502);
}

curl_close($ch);

if ($httpCode !== 200) {
    anjungan_fail('Layanan BPJS mengembalikan HTTP ' . $httpCode, 502);
}

$responseData = json_decode((string) $response, true);

if (!is_array($responseData) || !isset($responseData['metaData']['code'])) {
    anjungan_fail('Respons BPJS tidak valid', 502);
}

if ((int) $responseData['metaData']['code'] !== 200) {
    anjungan_fail((string) ($responseData['metaData']['message'] ?? 'Permintaan BPJS gagal'));
}

$encryptedPayload = (string) ($responseData['response'] ?? '');
$key = $cons_id . $secret_key . $timestamp;
$decryptedPayload = bpjs_decrypt($key, $encryptedPayload);

if ($decryptedPayload === false) {
    anjungan_fail('Data peserta BPJS tidak dapat dibaca', 502);
}

$decompressedPayload = LZString::decompressFromEncodedURIComponent($decryptedPayload);

if ($decompressedPayload === null) {
    anjungan_fail('Data peserta BPJS tidak dapat diproses', 502);
}

$participant = json_decode($decompressedPayload, true);

if (!is_array($participant)) {
    anjungan_fail('Format data peserta BPJS tidak dikenali', 502);
}

anjungan_json([
    'status' => true,
    'data' => normalize_bpjs_participant($participant, $jenis, $nomor),
]);

function bpjs_decrypt(string $key, string $payload)
{
    $cipher = 'AES-256-CBC';
    $keyHash = hex2bin(hash('sha256', $key));
    $iv = substr((string) $keyHash, 0, 16);

    return openssl_decrypt(base64_decode($payload), $cipher, $keyHash, OPENSSL_RAW_DATA, $iv);
}

function normalize_bpjs_participant(array $participant, string $jenis, string $nomor): array
{
    $noKtp = trim((string) ($participant['noKTP'] ?? $participant['nik'] ?? ''));
    $noKartu = trim((string) ($participant['noKartu'] ?? $participant['noka'] ?? ''));
    $nama = trim((string) ($participant['nama'] ?? ''));
    $tglLahir = anjungan_normalize_date((string) ($participant['tglLahir'] ?? ''));
    $gender = anjungan_normalize_gender((string) ($participant['sex'] ?? $participant['jenisKelamin'] ?? ''));
    $active = filter_var($participant['aktif'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    if ($jenis === 'nik' && ($noKtp === '' || $noKtp === '-')) {
        $noKtp = $nomor;
    }

    return array_merge($participant, [
        'noKTP' => $noKtp,
        'noKartu' => $noKartu,
        'nama' => $nama,
        'tglLahir' => $tglLahir,
        'sex' => $gender,
        'jenisKelamin' => $gender,
        'aktif' => $active ?? !empty($participant['aktif']),
        'alamat' => trim((string) ($participant['alamat'] ?? '')),
        'noHP' => trim((string) ($participant['noHP'] ?? $participant['noTelepon'] ?? '')),
    ]);
}
