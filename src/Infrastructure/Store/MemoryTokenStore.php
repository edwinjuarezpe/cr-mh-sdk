<?php

namespace EdwinJuarez\Mh\Infrastructure\Store;

final class MemoryTokenStore implements TokenStoreInterface
{
    private array $items = [];
    
    public function get(string $key): ?array
    {
        $now = time();

        if (!isset($this->items[$key])) {
            return null;
        }

        if ($this->items[$key]['store_exp'] <= $now) {
            unset($this->items[$key]);
            return null;
        }

        $rec = $this->items[$key]['record'];
        return $rec;
    }

    public function set(string $key, array $record, int $ttlSeconds): void
    {
        $now = time();

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

        $storeExp = $now + max(0, $ttlSeconds);

        $this->items[$key] = [
            'record'    => [
                'access_token'   => $record['access_token'],
                'access_exp_ts'  => $record['access_exp_ts'],
                'refresh_token'  => $record['refresh_token'],
                'refresh_exp_ts' => $record['refresh_exp_ts'],
            ],
            'store_exp' => $storeExp,
        ];
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);
    }

    public function purgeExpired(): int
    {
        $now = time();
        $removed = 0;
        foreach ($this->items as $k => $entry) {
            if ($entry['store_exp'] <= $now) {
                unset($this->items[$k]);
                $removed++;
            }
        }
        return $removed;
    }
}