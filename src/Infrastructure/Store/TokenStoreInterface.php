<?php

namespace EdwinJuarez\Mh\Infrastructure\Store;

interface TokenStoreInterface
{
    /**
     * Obtiene el registro de tokens para la clave dada.
     *
     * @param  string $key Clave única de la empresa/identidad (KeyDeriver).
     * @return array{
     *   access_token:string,
     *   access_exp_ts:int,
     *   refresh_token:string,
     *   refresh_exp_ts:int
     * }|null  Arreglo asociativo con los 4 campos o null si no existe / expiró.
     */
    public function get(string $key): ?array;

    /**
     * Guarda o actualiza el registro de tokens para la clave dada.
     *
     * @param string $key         Clave única de la empresa/identidad (KeyDeriver).
     * @param array  $record      Mismo shape que devuelve get():
     *                            [
     *                              'access_token'   => string,
     *                              'access_exp_ts'  => int,
     *                              'refresh_token'  => string,
     *                              'refresh_exp_ts' => int,
     *                            ]
     * @param int    $ttlSeconds  Tiempo-vida sugerido del registro (segundos).
     *                            Usar (refresh_exp_ts - now). Implementaciones
     *                            basadas en archivos pueden ignorarlo; las que
     *                            soportan expiración (cache/DB) pueden usarlo.
     */
    public function set(string $key, array $record, int $ttlSeconds): void;

    /**
     * Elimina el registro asociado a la clave dada (si existe).
     *
     * @param string $key Clave única de la empresa/identidad (KeyDeriver).
     */
    public function delete(string $key): void;
}
