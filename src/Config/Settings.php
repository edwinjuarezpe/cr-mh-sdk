<?php

namespace EdwinJuarez\Mh\Config;

use InvalidArgumentException;
use EdwinJuarez\Mh\Exceptions\ConfigException;

final class Settings
{
    public readonly string $env;

    public readonly string $baseUrlApi;
    public readonly string $baseUrlIdp;

    public readonly string $clientId;
    public readonly string $username;
    public readonly string $password;

    public readonly int $timeoutSec;
    public readonly int $connectTimeoutSec;

    public readonly int $timeoutMs;
    public readonly int $connectTimeoutMs;

    public readonly string $userAgent;

    /**
     * @param string $env                 'stag' | 'prod' | 'dev' (informativo)
     * @param string $baseUrlApi          https://... base de API recepción
     * @param string $baseUrlIdp          https://... base del IdP/OIDC
     * @param string $clientId
     * @param string $username
     * @param string $password
     * @param int    $timeoutSec          timeout total del request (segundos)
     * @param int    $connectTimeoutSec   timeout de conexión (segundos)
     * @param string $userAgent
     */
    public function __construct(
        string $env,
        string $baseUrlApi,
        string $baseUrlIdp,
        string $clientId,
        string $username,
        string $password,
        int $timeoutSec = 20,
        int $connectTimeoutSec = 5,
        string $userAgent = 'CR-MH-SDK/1.0'
    ) {
        $this->env = $env;

        $this->baseUrlApi = $this->normalizeHttpsBase($baseUrlApi, 'baseUrlApi');
        $this->baseUrlIdp = $this->normalizeHttpsBase($baseUrlIdp, 'baseUrlIdp');

        if ($clientId === '' || $username === '' || $password === '') {
            throw new ConfigException('clientId/username/password no pueden estar vacíos.');
        }

        if ($timeoutSec < 1 || $connectTimeoutSec < 0 || $timeoutSec < $connectTimeoutSec) {
            throw new ConfigException('Invalid HTTP timeouts');
        }

        $this->clientId = $clientId;
        $this->username = $username;
        $this->password = $password;

        $this->timeoutSec        = $timeoutSec;
        $this->connectTimeoutSec = $connectTimeoutSec;

        $this->timeoutMs        = $timeoutSec * 1000;
        $this->connectTimeoutMs = $connectTimeoutSec * 1000;

        $this->userAgent = trim($userAgent) !== '' ? $userAgent : 'CR-MH-SDK/0.x';
    }

    private function normalizeHttpsBase(string $url, string $fieldName): string
    {
        $url = rtrim(trim($url), '/');
        if (!str_starts_with($url, 'https://')) {
            throw new ConfigException("{$fieldName} must start with https://");
        }
        return $url;
    }
}
