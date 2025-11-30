<?php   

namespace EdwinJuarez\Mh\Support;

final class Json
{
    public static function encode(mixed $data): string
    {
        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\Throwable $e) {
            throw new \UnexpectedValueException('Data cannot be encoded to JSON: '.$e->getMessage(), 0, $e);
        }   

        return $json;
    }

    public static function decode(string $json): mixed
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \UnexpectedValueException('Invalid JSON: '.$e->getMessage(), 0, $e);
        }
        if (!is_array($data)) {
            throw new \UnexpectedValueException('JSON root must be an object/array');
        }
        return $data;
    }
}   