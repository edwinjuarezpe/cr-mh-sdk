<?php

namespace EdwinJuarez\Mh\Contracts;

interface SignerInterface
{
    /**
     * @param string $pkcs12Path       Ruta al archivo .p12/.pfx
     * @param string $pkcs12Password   Clave del certificado
     * @param string $xml              XML sin firma (string)
     * @return string                  XML firmado
     */
    public function sign(string $pkcs12Path, string $pkcs12Password, string $xml): string;
}