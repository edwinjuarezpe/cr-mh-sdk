<?php

namespace EdwinJuarez\Mh\Application\Services;

use EdwinJuarez\Mh\Application\Ports\Auth\TokenProviderInterface;
use EdwinJuarez\Mh\Application\Ports\Http\MhHttpClientInterface;
use EdwinJuarez\Mh\Config\Endpoints;
use EdwinJuarez\Mh\Config\Settings;
use EdwinJuarez\Mh\Dto\StatusResult;
use EdwinJuarez\Mh\Exceptions\MhApiException;
use EdwinJuarez\Mh\Infrastructure\Mappers\StatusMapper;

final class StatusService
{
    public function __construct(
        private readonly Settings               $settings,
        private readonly Endpoints              $endpoints,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly MhHttpClientInterface  $http,
        private readonly StatusMapper           $mapper,
    ) {}

    public function statusByClave(string $clave): StatusResult
    {
        $token = $this->tokenProvider->getAccessToken();

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ];

        $resp = $this->http->get(
            $this->settings->baseUrlApi,
            $this->endpoints->estadoPorClave($clave),
            $headers
        );

        $status      = $resp['status']  ?? 0;
        $respHeaders = $resp['headers'] ?? [];
        $respBody    = $resp['body']    ?? '';

        if ($status !== 200) {
            $errorCause = $respHeaders['X-Error-Cause'] ?? null;
            $bodySnippet = mb_substr((string)$respBody, 0, 300);

            if ($errorCause && $bodySnippet) {
                $snippet = "X-Error-Cause: {$errorCause} | Body: {$bodySnippet}";
            } elseif ($errorCause) {
                $snippet = "X-Error-Cause: {$errorCause}";
            } else {
                $snippet = $bodySnippet;
            }

            throw new MhApiException(
                $status,
                $this->endpoints->estadoPorClave($clave),
                $snippet
            );
        }

        return $this->mapper->fromJson((string)$respBody, $status);
    }
}
