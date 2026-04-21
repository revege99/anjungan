<?php
declare(strict_types=1);

require_once '../function/configDB.php';

$input = anjungan_json_input();
$level = anjungan_request_value($input, 'level');
$nama = anjungan_request_value($input, 'nama');

if ($level === '' || $nama === '' || strlen($nama) > 160) {
    anjungan_json(['kd' => 0]);
}

$mapping = [
    'propinsi' => ['table' => 'propinsi', 'code' => 'kd_prop', 'name' => 'nm_prop'],
    'kabupaten' => ['table' => 'kabupaten', 'code' => 'kd_kab', 'name' => 'nm_kab'],
    'kecamatan' => ['table' => 'kecamatan', 'code' => 'kd_kec', 'name' => 'nm_kec'],
    'kelurahan' => ['table' => 'kelurahan', 'code' => 'kd_kel', 'name' => 'nm_kel'],
];

if (!isset($mapping[$level])) {
    anjungan_json(['kd' => 0]);
}

$config = $mapping[$level];

try {
    $sql = sprintf(
        'SELECT %s FROM %s WHERE %s = ? LIMIT 1',
        $config['code'],
        $config['table'],
        $config['name']
    );

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $nama);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        anjungan_json([
            'level' => $level,
            'nama' => $nama,
            'kd' => (int) $existing[$config['code']],
        ]);
    }

    $insertSql = sprintf('INSERT INTO %s (%s) VALUES (?)', $config['table'], $config['name']);
    $insert = $conn->prepare($insertSql);
    $insert->bind_param('s', $nama);
    $insert->execute();

    anjungan_json([
        'level' => $level,
        'nama' => $nama,
        'kd' => (int) $conn->insert_id,
    ]);
} catch (Throwable $exception) {
    anjungan_json(['kd' => 0]);
}
