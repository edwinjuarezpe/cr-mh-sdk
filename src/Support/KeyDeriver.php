<?php

namespace EdwinJuarez\Mh\Support;

final class KeyDeriver
{
    public static function for(
        string $baseUrlIdp,
        string $clientId,
        string $username,
        ?string $scope = null
    ): string {
        $key = sprintf('mh:oidc:%s:%s:%s', $baseUrlIdp, $clientId, $username);

        if ($scope !== null && $scope !== '') {
            $key .= ':' . substr(hash('sha256', $scope), 0, 16);
        }

        return $key;
    }
}