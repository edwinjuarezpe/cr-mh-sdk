<?php

namespace EdwinJuarez\Mh\Dto;

final class SubmitOptions
{
    public function __construct(
        public readonly ?string $callbackUrl = null, // Opcional: URL de callback
        public readonly ?string $consecutivoReceptor = null  // Opcional: Numeración consecutiva de los mensajes de confirmación.
    ) {
    }
}