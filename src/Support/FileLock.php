<?php

namespace EdwinJuarez\Mh\Support;

final class FileLock
{
    public function __construct(private readonly string $locksDir)
    {
        if (!is_dir($locksDir) && !@mkdir($locksDir, 0700, true)) {
            throw new \RuntimeException("Cannot create locks dir: $locksDir");
        }
    }

    public function acquire(string $key, int $timeoutMs = 3000) {
        $path = $this->pathFor($key);
        $start = microtime(true);

        $fh = fopen($path, 'c');
        if ($fh === false) return false;

        do {
            if (flock($fh, LOCK_EX | LOCK_NB)) {
                return $fh;
            }
            usleep(50_000); // 50ms
        } while (((microtime(true) - $start) * 1000) < $timeoutMs);

        fclose($fh);
        return false;
    }

    public function release($handle): void {
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }

    private function pathFor(string $key): string {
        $name = hash('sha256', $key).'.lock';
        return rtrim($this->locksDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;
    }
}