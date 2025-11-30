<?php

namespace EdwinJuarez\Mh\Infrastructure\Http;

use EdwinJuarez\Mh\Application\Ports\Http\MhHttpClientInterface;
use EdwinJuarez\Mh\Config\Settings;
use EdwinJuarez\Mh\Exceptions\TransportException;
use EdwinJuarez\Mh\Support\RetryPolicy;

final class CurlMhHttpClient implements MhHttpClientInterface
{
    public function __construct(
        private readonly Settings $settings,
        private readonly RetryPolicy $retryPolicy = new RetryPolicy(3) // máx 3 reintentos
    ) {}

    public function postForm(string $url, array $form, array $headers = []): array
    {
        $payload = http_build_query($form, '', '&', PHP_QUERY_RFC3986);

        $headers = $this->normalizeHeaders($headers);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['User-Agent']   = $headers['User-Agent']   ?? $this->settings->userAgent;
        $headers['Accept']       = $headers['Accept']       ?? 'application/json';

        return $this->doRequest('POST', $url, $headers, $payload);
    }

    public function postJson(string $baseUrl, string $path, array $headers, string $jsonBody): array
    
    {
        $url = $this->joinUrl($baseUrl, $path);

        $headers = $this->normalizeHeaders($headers);
        $headers['Content-Type'] = 'application/json';
        $headers['User-Agent']   = $headers['User-Agent'] ?? $this->settings->userAgent;
        $headers['Accept']       = $headers['Accept']     ?? 'application/json';

        return $this->doRequest('POST', $url, $headers, $jsonBody);
    }

    public function get(string $baseUrl, string $path, array $headers): array
    {
        $url = $this->joinUrl($baseUrl, $path);

        $headers = $this->normalizeHeaders($headers);
        $headers['User-Agent'] = $headers['User-Agent'] ?? $this->settings->userAgent;
        $headers['Accept']     = $headers['Accept']     ?? 'application/json';

        return $this->doRequest('GET', $url, $headers, null);
    }

    private function doRequest(string $method, string $url, array $headers, ?string $body): array
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            [$status, $respHeaders, $respBody, $curlErr] = $this->executeOnce($method, $url, $headers, $body);

            if ($curlErr !== null) {
                if (!$this->retryPolicy->shouldRetry($attempt, 0)) {
                    throw new TransportException("cURL error: {$curlErr}");
                }
                usleep($this->retryPolicy->sleepUs($attempt));
                continue;
            }

            if ($this->retryPolicy->shouldRetry($attempt, $status)) {
                usleep($this->retryPolicy->sleepUs($attempt));
                continue;
            }

            return [
                'status'  => $status,
                'headers' => $respHeaders,
                'body'    => $respBody,
            ];
        }
    }

    private function executeOnce(string $method, string $url, array $headers, ?string $body): array
    {
        $ch = curl_init();

        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = $k . ': ' . $v;
        }

        $outHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_URL               => $url,
            CURLOPT_CUSTOMREQUEST     => $method,
            CURLOPT_HTTPHEADER        => $curlHeaders,
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_HEADERFUNCTION    => static function ($ch, string $hdrLine) use (&$outHeaders): int {
                $len = strlen($hdrLine);
                $parts = explode(':', $hdrLine, 2);
                if (count($parts) === 2) {
                    $name  = strtolower(trim($parts[0]));
                    $value = trim($parts[1]);
                    $outHeaders[$name] = $value;
                }
                return $len;
            },
            CURLOPT_TIMEOUT_MS        => $this->settings->timeoutMs,
            CURLOPT_CONNECTTIMEOUT_MS => $this->settings->connectTimeoutMs,
            CURLOPT_SSL_VERIFYPEER    => true,
            CURLOPT_SSL_VERIFYHOST    => 2,
        ]);

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body ?? '');
        }

        $respBody = curl_exec($ch);

        if ($respBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return [0, [], '', $err];
        }

        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $normalized = [];
        foreach ($outHeaders as $k => $v) {
            $normalized[$this->titleCaseHeader($k)] = $v;
        }

        return [$status, $normalized, (string)$respBody, null];
    }

    private function joinUrl(string $base, string $path): string
    {
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    private function normalizeHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $k => $v) {
            if ($v === '' || $v === null) continue;
            $out[$this->titleCaseHeader((string)$k)] = (string)$v;
        }
        return $out;
    }

    private function titleCaseHeader(string $h): string
    {
        $h = str_replace('_', '-', strtolower(trim($h)));
        return implode('-', array_map(function($p) {
                return $p === '' ? '' : ucfirst($p);
            }, explode('-', $h)
        ));
    }
}
