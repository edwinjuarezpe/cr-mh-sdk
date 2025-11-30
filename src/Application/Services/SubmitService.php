<?php

namespace EdwinJuarez\Mh\Application\Services;

use EdwinJuarez\Mh\Application\Ports\Auth\TokenProviderInterface;
use EdwinJuarez\Mh\Application\Ports\Http\MhHttpClientInterface;
use EdwinJuarez\Mh\Config\Endpoints;
use EdwinJuarez\Mh\Config\Settings;
use EdwinJuarez\Mh\Dto\SubmitAck;
use EdwinJuarez\Mh\Dto\SubmitRequest;
use EdwinJuarez\Mh\Exceptions\MhApiException;
use EdwinJuarez\Mh\Infrastructure\Mappers\SubmitPayloadMapper;

final class SubmitService
{
    public function __construct(
        private readonly Settings               $settings,
        private readonly Endpoints              $endpoints,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly MhHttpClientInterface  $http,
        private readonly SubmitPayloadMapper    $mapper,
    ) {}

    public function submit(SubmitRequest $req): SubmitAck
    {
        $token = $this->tokenProvider->getAccessToken();

        $payload  = $this->mapper->toArray($req);
        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json; charset=UTF-8',
            'Accept'        => 'application/json',
        ];
        
        $resp = $this->http->postJson(
            $this->settings->baseUrlApi,
            $this->endpoints->recepcion(),
            $headers,
            $jsonBody
        );

        $status      = $resp['status']  ?? 0;
        $respHeaders = $resp['headers'] ?? [];
        $respBody    = $resp['body']    ?? '';

        if ($status !== 202) {
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
                $this->endpoints->recepcion(),
                $snippet
            );
        }

        $location = $respHeaders['location'] ?? $respHeaders['Location'] ?? null;
        if (!is_string($location) || $location === '') {
            throw new MhApiException(
                $status,
                $this->endpoints->recepcion(),
                ''
            );
        }

        $clave = $this->extractClaveFromLocation($location);

        return new SubmitAck(
            httpStatus: $status,
            clave:      $clave,
            location:   $location
        );
    }

    private function extractClaveFromLocation(string $location): string
    {
        $p = parse_url($location, PHP_URL_PATH) ?? '';
        $p = is_string($p) ? $p : '';
        $parts = array_values(array_filter(explode('/', $p), fn($s) => $s !== ''));
        $last  = $parts[count($parts) - 1] ?? '';
        if ($last === '') {
            throw new \RuntimeException("No se pudo extraer la clave desde Location: {$location}");
        }
        return $last;
    }
}
