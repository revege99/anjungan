<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function anjungan_env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function anjungan_db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $host = anjungan_env('DB_HOST', 'localhost');
    $user = anjungan_env('DB_USERNAME', 'root');
    $pass = anjungan_env('DB_PASSWORD', '');
    $name = anjungan_env('DB_DATABASE', 'sik_tester_lintong');
    $port = (int) anjungan_env('DB_PORT', '3306');

    $connection = mysqli_init();
    $connection->real_connect($host, $user, $pass, $name, $port);
    $connection->set_charset('utf8mb4');

    return $connection;
}

function anjungan_query(string $sql): array
{
    $result = anjungan_db()->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}

function anjungan_json(array $payload, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function anjungan_fail(string $message, int $statusCode = 400, array $extra = []): void
{
    anjungan_json(array_merge([
        'status' => false,
        'message' => $message,
    ], $extra), $statusCode);
}

function anjungan_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        anjungan_fail('Metode request tidak valid', 405);
    }
}

function anjungan_request_value(array $source, string $key, string $default = ''): string
{
    return trim((string) ($source[$key] ?? $default));
}

function anjungan_get(string $key, string $default = ''): string
{
    return anjungan_request_value($_GET, $key, $default);
}

function anjungan_post(string $key, string $default = ''): string
{
    return anjungan_request_value($_POST, $key, $default);
}

function anjungan_json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);

    return is_array($data) ? $data : [];
}

function anjungan_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function anjungan_day_name(?string $englishDay = null): string
{
    $day = $englishDay ?: date('l');

    $map = [
        'Monday' => 'SENIN',
        'Tuesday' => 'SELASA',
        'Wednesday' => 'RABU',
        'Thursday' => 'KAMIS',
        'Friday' => 'JUMAT',
        'Saturday' => 'SABTU',
        'Sunday' => 'AKHAD',
    ];

    return $map[$day] ?? 'SENIN';
}

function anjungan_valid_schedule_day(string $day): bool
{
    return in_array($day, ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'AKHAD'], true);
}

function anjungan_normalize_gender(string $value): string
{
    $normalized = strtoupper(trim($value));

    if (in_array($normalized, ['L', 'LAKI-LAKI', 'LAKI LAKI', 'MALE', 'M'], true)) {
        return 'L';
    }

    if (in_array($normalized, ['P', 'PEREMPUAN', 'FEMALE', 'F'], true)) {
        return 'P';
    }

    return '';
}

function anjungan_gender_label(string $value): string
{
    return anjungan_normalize_gender($value) === 'L' ? 'Laki-laki' : 'Perempuan';
}

function anjungan_normalize_date(string $value): string
{
    $date = trim($value);

    if ($date === '') {
        return '';
    }

    $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y'];

    foreach ($formats as $format) {
        $parsed = DateTime::createFromFormat($format, $date);

        if ($parsed instanceof DateTime) {
            return $parsed->format('Y-m-d');
        }
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('Y-m-d', $timestamp) : $date;
}

function anjungan_format_display_date(string $value): string
{
    $normalized = anjungan_normalize_date($value);
    $timestamp = strtotime($normalized);

    return $timestamp ? date('d-m-Y', $timestamp) : $value;
}

function anjungan_valid_phone(string $value): bool
{
    return (bool) preg_match('/^08[0-9]{8,13}$/', $value);
}

function anjungan_calculate_age(string $birthDate, ?string $referenceDate = null): array
{
    $normalizedBirthDate = anjungan_normalize_date($birthDate);

    if ($normalizedBirthDate === '') {
        return ['value' => 0, 'unit' => 'Th', 'label' => '0 Th'];
    }

    $birth = new DateTime($normalizedBirthDate);
    $today = new DateTime($referenceDate ?: date('Y-m-d'));
    $diff = $birth->diff($today);

    if ($diff->y > 0) {
        return ['value' => $diff->y, 'unit' => 'Th', 'label' => $diff->y . ' Th'];
    }

    if ($diff->m > 0) {
        return ['value' => $diff->m, 'unit' => 'Bl', 'label' => $diff->m . ' Bl'];
    }

    return ['value' => $diff->d, 'unit' => 'Hr', 'label' => $diff->d . ' Hr'];
}

function anjungan_transaction(mysqli $connection, callable $callback)
{
    $connection->begin_transaction();

    try {
        $result = $callback($connection);
        $connection->commit();

        return $result;
    } catch (Throwable $exception) {
        $connection->rollback();
        throw $exception;
    }
}

