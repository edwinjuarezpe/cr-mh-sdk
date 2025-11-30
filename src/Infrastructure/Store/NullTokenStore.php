<?php

namespace EdwinJuarez\Mh\Infrastructure\Store;

final class NullTokenStore implements TokenStoreInterface
{
    public function get(string $key): ?array
    {
        return null;
    }

    public function set(string $key, array $record, int $ttlSeconds): void
    {}
    public function delete(string $key): void
    {}
}
