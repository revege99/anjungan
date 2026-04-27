<?php

namespace App\Services;

use App\Models\ClinicDatabaseConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClinicDatabaseManager
{
    public function connectionFor(?int $clinicProfileId, string $role = 'simrs'): Connection
    {
        if (! $clinicProfileId) {
            return DB::connection($this->fallbackConnectionName($role));
        }

        $databaseConfig = ClinicDatabaseConnection::query()
            ->active()
            ->where('clinic_profile_id', $clinicProfileId)
            ->where('connection_role', $role)
            ->first();

        if (! $databaseConfig) {
            return DB::connection($this->fallbackConnectionName($role));
        }

        $connectionName = 'clinic_' . $role . '_' . $clinicProfileId;

        Config::set('database.connections.' . $connectionName, [
            'driver' => $databaseConfig->driver ?: 'mariadb',
            'host' => $databaseConfig->zero_tier_ip ?: $databaseConfig->host,
            'port' => (string) ($databaseConfig->port ?: 3306),
            'database' => $databaseConfig->database,
            'username' => $databaseConfig->username,
            'password' => $databaseConfig->password,
            'charset' => $databaseConfig->charset ?: 'utf8mb4',
            'collation' => $databaseConfig->collation ?: 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge($connectionName);

        return DB::connection($connectionName);
    }

    private function fallbackConnectionName(string $role): string
    {
        if ($role === 'simrs' && array_key_exists('simrs', config('database.connections', []))) {
            return 'simrs';
        }

        if (array_key_exists($role, config('database.connections', []))) {
            return $role;
        }

        $default = config('database.default');

        if (! is_string($default) || $default === '') {
            throw new RuntimeException('Default database connection tidak ditemukan.');
        }

        return $default;
    }
}
