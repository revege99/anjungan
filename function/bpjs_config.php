<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

$cons_id = anjungan_env('BPJS_CONS_ID', '14494');
$secret_key = anjungan_env('BPJS_SECRET_KEY', '6tXBDE443B');
$user_key = anjungan_env('BPJS_USER_KEY', 'f9874c7a2cb354f832927ebdf95f6843');

$base_url = rtrim((string) anjungan_env('BPJS_BASE_URL', 'https://apijkn.bpjs-kesehatan.go.id/pcare-rest/'), '/') . '/';
$auth_user = anjungan_env('BPJS_AUTH_USER', 'Dedi.kristina');
$auth_pass = anjungan_env('BPJS_AUTH_PASS', 'SintLucia@123');
$kd_aplikasi = anjungan_env('BPJS_KD_APLIKASI', '095');
