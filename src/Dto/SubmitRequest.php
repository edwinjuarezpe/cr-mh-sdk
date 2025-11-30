<?php

namespace EdwinJuarez\Mh\Dto;

final class SubmitRequest
{
     public function __construct(
        public readonly string $clave, 
        public readonly string $fechaEmision,
        public readonly string $emisorTipo,
        public readonly string $emisorNumero,
        public readonly ?string $receptorTipo,
        public readonly ?string $receptorNumero,
        public readonly string $comprobanteXmlBase64,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $consecutivoReceptor = null
    ) {
    }
}