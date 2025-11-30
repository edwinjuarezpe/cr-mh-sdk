<?php

namespace EdwinJuarez\Mh\Support;

final class RetryPolicy
{
    public function __construct(
        private int $maxRetries = 3,
        private int $maxDelayMs = 5000
    ) {}

    public function shouldRetry(int $attempt, int $status): bool
    {
        if ($attempt > $this->maxRetries) {
            return false;
        }
        return in_array($status, [429, 502, 503, 504], true);
    }

    public function delayMs(int $attempt): int
    {
        $base   = (int) (200 * (2 ** max(0, $attempt - 1)));
        $jitter = random_int(0, 200);
        return min($base + $jitter, $this->maxDelayMs);
    }

    public function sleepUs(int $attempt): int
    {
        return $this->delayMs($attempt) * 1000;
    }
}