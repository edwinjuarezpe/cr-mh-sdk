<?php

namespace EdwinJuarez\Mh\Application\Ports\Http;

interface MhHttpClientInterface
{
    /**
     * Realiza un POST con JSON al (baseUrl + path) de MH.
     *
     * @param string $baseUrl   Base URL de MH (p.ej. https://api.../recepcion/v1)
     * @param string $path      Path relativo (p.ej. /recepcion)
     * @param array  $headers   Cabeceras HTTP (clave => valor), ej. Authorization
     * @param array  $payload   Cuerpo JSON (assoc array)
     * @param string $jsonBody Cuerpo ya serializado en JSON

     * @return array{status:int, headers:array<string,string>, body:string}
     * @throws \EdwinJuarez\Mh\Exceptions\TransportException en fallas de red/TLS
     */
    public function postJson(string $baseUrl, string $path, array $headers, string $jsonBody): array;
    

    /**
     * Realiza un GET (baseUrl + path) a MH.
     *
     * @param string $baseUrl
     * @param string $path
     * @param array  $headers
     * @return array{status:int, headers:array<string,string>, body:string}
     * @throws \EdwinJuarez\Mh\Exceptions\TransportException en fallas de red/TLS
     */
    public function get(string $baseUrl, string $path, array $headers): array;

    /**
     * Realiza un POST application/x-www-form-urlencoded a la URL absoluta (IdP).
     *
     * @param string $url       URL absoluta (p.ej. https://idp.../token)
     * @param array  $form      Pares clave=>valor (grant_type, client_id, etc.)
     * @param array  $headers   Cabeceras extra si aplica
     * @return array{status:int, headers:array<string,string>, body:string}
     * @throws \EdwinJuarez\Mh\Exceptions\TransportException en fallas de red/TLS
     */
    public function postForm(string $url, array $form, array $headers = []): array;
}