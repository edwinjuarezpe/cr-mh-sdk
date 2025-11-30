<?php

namespace EdwinJuarez\Mh\Dto;

final class StatusResult
{
    public function __construct(
          public readonly string  $clave,
        public readonly string  $fecha,          // ISO como viene de MH
        // 'aceptado' | 'rechazado' | 'pendiente'
        public readonly string  $estado,
        public readonly ?string $acuseXmlBase64 = null,
        public readonly ?int    $httpStatus = 200,
        public readonly ?string $rawBody    = null,
        public readonly ?string $mensaje = null,
        public readonly ?string $estadoMensaje  = null,
        public readonly ?string $detalleMensaje = null
    ) {
    }
}