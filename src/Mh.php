<?php

namespace EdwinJuarez\Mh;

use EdwinJuarez\Mh\MhClient;
use EdwinJuarez\Mh\Config\Settings;
use EdwinJuarez\Mh\Config\Endpoints;
use EdwinJuarez\Mh\Support\RetryPolicy;
use EdwinJuarez\Mh\Support\SystemClock;
use EdwinJuarez\Mh\Exceptions\ConfigException;
use EdwinJuarez\Mh\Application\Services\StatusService;
use EdwinJuarez\Mh\Application\Services\SubmitService;
use EdwinJuarez\Mh\Infrastructure\Mappers\StatusMapper;
use EdwinJuarez\Mh\Infrastructure\Store\NullTokenStore;
use EdwinJuarez\Mh\Infrastructure\Http\CurlMhHttpClient;
use EdwinJuarez\Mh\Infrastructure\Store\TokenStoreInterface;
use EdwinJuarez\Mh\Infrastructure\Auth\PasswordTokenProvider;
use EdwinJuarez\Mh\Infrastructure\Mappers\SubmitPayloadMapper;
use EdwinJuarez\Mh\Infrastructure\Parsers\XmlSubmitRequestFactory;

/**
 * Factoría: crea un MhClient completamente cableado y listo para usar.
 */
final class Mh
{
    /**
     * Crea el cliente usando grant type "password" (IdP MH).
     *
     * - Si NO pasas $tokenStore, se usará NullTokenStore (no se cachean tokens).
     * - Si quieres cachear tokens, pasa tu propia implementación de TokenStoreInterface
     *   (por ejemplo FileTokenStore, MemoryTokenStore, DbTokenStore, etc.).
     */
    public static function clientWithPassword(
        Settings $settings,
        ?TokenStoreInterface $tokenStore = null
    ): MhClient {
        if (!in_array($settings->env, ['dev','stag','prod'], true)) {
            throw new ConfigException("Invalid env '{$settings->env}'. Use 'dev', 'stag' or 'prod'.");
        }
        // 1) Infra básica
        $clock     = new SystemClock();
        $endpoints = new Endpoints($settings);

        // 2) Token store (Por defecto: NO se usa FileTokenStore ni MemoryTokenStore.)
        if ($tokenStore === null) {
            $tokenStore = new NullTokenStore();
        }

        // 3) HTTP cURL con reintentos prudentes y TLS verificado
        $http = new CurlMhHttpClient(
            settings:    $settings,
            retryPolicy: new RetryPolicy()
        );

        // 4) Proveedor de token (siempre devuelve uno vigente)
        $tokenProvider = new PasswordTokenProvider(
            settings:   $settings,
            endpoints:  $endpoints,
            http:       $http,
            store:      $tokenStore,
            clock:      $clock
        );

        // 5) Parsers & mappers
        $xmlFactory = new XmlSubmitRequestFactory();
        $subMapper  = new SubmitPayloadMapper();
        $stMapper   = new StatusMapper();

        // 6) Casos de uso
        $submit = new SubmitService(
            settings:      $settings,
            endpoints:     $endpoints,
            tokenProvider: $tokenProvider,
            http:          $http,
            mapper:        $subMapper
        );

        $status = new StatusService(
            settings:      $settings,
            endpoints:     $endpoints,
            tokenProvider: $tokenProvider,
            http:          $http,
            mapper:        $stMapper
        );

        // 7) Fachada final
        return new MhClient(
            submitService: $submit,
            statusService: $status,
            xmlFactory:    $xmlFactory
        );
    }
}
