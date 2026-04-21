<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

$conn = anjungan_db();

function query(string $query): array
{
    return anjungan_query($query);
}
