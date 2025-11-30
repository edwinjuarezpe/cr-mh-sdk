<?php
namespace EdwinJuarez\Mh\Infrastructure\Store;

use EdwinJuarez\Mh\Support\Json;
use EdwinJuarez\Mh\Support\FileLock;

final class FileTokenStore implements TokenStoreInterface
{
    private string $dataDir;
    private string $locksDir;

    private FileLock $lock;

    public function __construct(string $dataDir, string $locksDir)
    {
        $this->dataDir  = rtrim($dataDir, DIRECTORY_SEPARATOR);
        $this->locksDir = rtrim($locksDir, DIRECTORY_SEPARATOR);

        if (!is_dir($this->dataDir)) {
            throw new \RuntimeException("Data dir does not exist: {$this->dataDir}");
        }
        if (!is_dir($this->locksDir)) {
            throw new \RuntimeException("Locks dir does not exist: {$this->locksDir}");
        }

        $this->lock = new FileLock($this->locksDir);
    }
    
    public function get(string $key): ?array
    {
        $path = $this->pathFor($key);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            @unlink($path);
            return null;
        }

        try {
            $data = Json::decode($raw);
        } catch (\Throwable) {
            @unlink($path);
            return null;
        }

        $now = time();
        if (!isset($data['store_exp']) || !is_int($data['store_exp']) || $data['store_exp'] <= $now) {
            @unlink($path);
            return null;
        }

        if (!isset($data['record']) || !is_array($data['record'])) {
            return null;
        }
        $rec = $data['record'];

        foreach (['access_token','access_exp_ts','refresh_token','refresh_exp_ts'] as $k) {
            if (!array_key_exists($k, $rec)) {
                return null;
            }
        }

        if (!is_string($rec['access_token']) || !is_string($rec['refresh_token'])) {
            return null;
        }
        if (!is_int($rec['access_exp_ts']) || !is_int($rec['refresh_exp_ts'])) {
            return null;
        }

        return $rec;
    }
    
    public function set(string $key, array $record, int $ttlSeconds): void
    {
        foreach (['access_token','access_exp_ts','refresh_token','refresh_exp_ts'] as $k) {
            if (!array_key_exists($k, $record)) {
                throw new \InvalidArgumentException("Token record missing field: {$k}");
            }
        }
        if (!is_string($record['access_token']) || !is_string($record['refresh_token'])) {
            throw new \InvalidArgumentException('access_token/refresh_token must be string');
        }
        if (!is_int($record['access_exp_ts']) || !is_int($record['refresh_exp_ts'])) {
            throw new \InvalidArgumentException('access_exp_ts/refresh_exp_ts must be int (timestamp)');
        }

        $now      = time();
        $storeExp = $now + max(0, $ttlSeconds);

        $payload = [
            'record'    => [
                'access_token'   => $record['access_token'],
                'access_exp_ts'  => $record['access_exp_ts'],
                'refresh_token'  => $record['refresh_token'],
                'refresh_exp_ts' => $record['refresh_exp_ts'],
            ],
            'store_exp' => $storeExp,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode token record JSON');
        }

        $path = $this->pathFor($key);
        $this->writeAtomic($path, $json);
        @chmod($path, 0600);
    }

    public function delete(string $key): void
    {
        $path = $this->pathFor($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function acquireLock(string $key, int $timeoutMs = 3000)
    {
        return $this->lock->acquire($key, $timeoutMs);
    }

    public function releaseLock($handle): void
    {
        $this->lock->release($handle);
    }

    private function pathFor(string $key): string
    {
        $name = hash('sha256', $key) . '.json';
        return $this->dataDir . DIRECTORY_SEPARATOR . $name;
    }

    private function writeAtomic(string $path, string $contents): void
    {
        $dir = dirname($path);
        $tmp = $dir . DIRECTORY_SEPARATOR . ('.tmp-' . bin2hex(random_bytes(8)));

        $bytes = @file_put_contents($tmp, $contents, LOCK_EX);
        if ($bytes === false) {
            @unlink($tmp);
            throw new \RuntimeException("Failed writing temp file: {$tmp}");
        }

        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException("Failed renaming temp file to target: {$path}");
        }
    }
}
