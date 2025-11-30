<?php

namespace EdwinJuarez\Mh\Dto;

final class SubmitAck
{
    public function __construct(
        public readonly int    $httpStatus,
        public readonly string $clave,
        public readonly string $location
    ) {}
}