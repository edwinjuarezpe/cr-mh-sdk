<?php

namespace EdwinJuarez\Mh\Infrastructure\Auth;

use EdwinJuarez\Mh\Application\Ports\Auth\TokenProviderInterface;
use EdwinJuarez\Mh\Application\Ports\Http\MhHttpClientInterface;
use EdwinJuarez\Mh\Config\Endpoints;
use EdwinJuarez\Mh\Config\Settings;
use EdwinJuarez\Mh\Exceptions\AuthException;
use EdwinJuarez\Mh\Infrastructure\Store\TokenStoreInterface;
use EdwinJuarez\Mh\Infrastructure\Store\FileTokenStore;
use EdwinJuarez\Mh\Support\Clock;
use EdwinJuarez\Mh\Support\Json;
use EdwinJuarez\Mh\Support\KeyDeriver;

final class PasswordTokenProvider implements TokenProviderInterface
{
    private const SKEW_SECONDS = 120;

    public function __construct(
        private readonly Settings $settings,
        private readonly Endpoints $endpoints,
        private readonly MhHttpClientInterface $http,
        private readonly TokenStoreInterface $store,
        private readonly Clock $clock
    ) {}

    public function getAccessToken(): string
    {
        $key = KeyDeriver::for(
            $this->settings->baseUrlIdp,
            $this->settings->clientId,
            $this->settings->username
        );

        $now = $this->clock->now()->getTimestamp();
        $rec = $this->store->get($key);
        if ($rec !== null && $now < ($rec['access_exp_ts'] - self::SKEW_SECONDS)) {
            return $rec['access_token'];
        }

        $lockHandle = null;
        $fileStore  = null;
        if ($this->store instanceof FileTokenStore) {
            $fileStore  = $this->store;
            $lockHandle = $fileStore->acquireLock($key, 3000);
        }

        try {
            $now = $this->clock->now()->getTimestamp();
            $rec = $this->store->get($key);
            if ($rec !== null && $now < ($rec['access_exp_ts'] - self::SKEW_SECONDS)) {
                return $rec['access_token'];
            }

            if ($rec !== null && $now < $rec['refresh_exp_ts']) {
                $newTok = $this->refreshWith($rec['refresh_token']);
                $saved  = $this->persist($key, $newTok);
                return $saved['access_token'];
            }

            $newTok = $this->loginWithPassword();
            $saved  = $this->persist($key, $newTok);
            return $saved['access_token'];

        } finally {
            if ($lockHandle !== null && $fileStore !== null) {
                $fileStore->releaseLock($lockHandle);
            }


        }
    }

    private function loginWithPassword(): array
    {
        $url  = $this->endpoints->tokenUrl();
        $form = [
            'grant_type' => 'password',
            'client_id'  => $this->settings->clientId,
            'username'   => $this->settings->username,
            'password'   => $this->settings->password,
        ];

        $resp = $this->http->postForm($url, $form, [
            'Accept'     => 'application/json',
            'User-Agent' => $this->settings->userAgent,
        ]);

        return $this->parseTokenResponse($resp, 'password');
    }

    private function refreshWith(string $refreshToken): array
    {
        $url  = $this->endpoints->tokenUrl();
        $form = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->settings->clientId,
            'refresh_token' => $refreshToken,
        ];

        $resp = $this->http->postForm($url, $form, [
            'Accept'     => 'application/json',
            'User-Agent' => $this->settings->userAgent,
        ]);

        return $this->parseTokenResponse($resp, 'refresh_token');
    }

    private function parseTokenResponse(array $resp, string $grant): array
    {
        $status = $resp['status'] ?? 0;
        $body   = $resp['body']   ?? '';

        if ($status !== 200) {
            try {
                $doc  = Json::decode($body);
                $err  = $doc['error'] ?? 'auth_error';
                $desc = $doc['error_description'] ?? 'authentication failed';
                throw new AuthException("OIDC {$grant} failed ({$status}): {$err} - {$desc}");
            } catch (\Throwable) {
                throw new AuthException("OIDC {$grant} failed ({$status}): ". $this->shorten($body));
            }
        }

        $doc = Json::decode($body);

        foreach (['access_token','expires_in','refresh_token','refresh_expires_in'] as $k) {
            if (!array_key_exists($k, $doc)) {
                throw new AuthException("OIDC {$grant} missing field: {$k}");
            }
        }

        $access  = $doc['access_token'];
        $refresh = $doc['refresh_token'];
        $exp     = $doc['expires_in'];
        $rexp    = $doc['refresh_expires_in'];

        if (!is_string($access) || !is_string($refresh)) {
            throw new AuthException("OIDC {$grant} invalid token types");
        }
        if (!(is_int($exp) || is_numeric($exp)) || !(is_int($rexp) || is_numeric($rexp))) {
            throw new AuthException("OIDC {$grant} invalid expires fields");
        }

        return [
            'access_token'        => $access,
            'expires_in'          => (int)$exp,
            'refresh_token'       => $refresh,
            'refresh_expires_in'  => (int)$rexp,
        ];
    }

    private function persist(string $key, array $tok): array
    {
        $now = $this->clock->now()->getTimestamp();

        $record = [
            'access_token'   => $tok['access_token'],
            'access_exp_ts'  => $now + max(1, $tok['expires_in']),
            'refresh_token'  => $tok['refresh_token'],
            'refresh_exp_ts' => $now + max(1, $tok['refresh_expires_in']),
        ];

        $ttl = max(0, $record['refresh_exp_ts'] - $now);
        $this->store->set($key, $record, $ttl);

        return $record;
    }

    private function shorten(string $s): string
    {
        $s = trim($s);
        return mb_strlen($s) > 300 ? (mb_substr($s, 0, 300).'…') : $s;
    }
}
