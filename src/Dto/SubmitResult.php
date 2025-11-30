<?php

namespace EdwinJuarez\Mh\Dto;

final class SubmitResult
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly string $clave,
        public readonly ?string $location = null,
        public readonly ?string $rawBody = null
    ) {
    }
}