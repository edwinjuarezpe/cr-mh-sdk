<?php

namespace EdwinJuarez\Mh\Support;

final class IdGenerator
{
    public static function uuidV4(): string
    {
        if (function_exists('com_create_guid') === true)
            return strtolower(trim(com_create_guid(), '{}'));

        $uuidBytes = random_bytes(16);
        $uuidBytes[6] = chr((ord($uuidBytes[6]) & 0x0f) | 0x40);
        $uuidBytes[8] = chr((ord($uuidBytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($uuidBytes), 4));
    }
}
