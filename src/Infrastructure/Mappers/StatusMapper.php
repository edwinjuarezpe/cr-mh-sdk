<?php

namespace EdwinJuarez\Mh\Infrastructure\Mappers;

use EdwinJuarez\Mh\Dto\StatusResult;
use EdwinJuarez\Mh\Infrastructure\Parsers\StatusAcuseParser;

final class StatusMapper
{
    public function fromJson(string $jsonBody, ?int $httpStatus = 200): StatusResult
    {
        $data = json_decode($jsonBody, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Respuesta de estado inválida: no es objeto JSON.');
        }

        $get = static function(array $a, array $keys, $default = null) {
            foreach ($keys as $k) {
                if (array_key_exists($k, $a)) {
                    return $a[$k];
                }
            }
            return $default;
        };

        $clave = $get($data, ['clave', 'key']);
        if (!is_string($clave) || $clave === '') {
            throw new \InvalidArgumentException("Respuesta de estado inválida: falta 'clave'.");
        }

        $fecha = $get($data, ['fecha', 'date', 'timestamp']);
        if (!is_string($fecha) || $fecha === '') {
            $fecha = '';
        }

        $rawIndEstado = $get($data, ['ind-estado', 'ind_estado', 'indEstado'], '');
        $estado = $this->normalizeEstado(is_string($rawIndEstado) ? $rawIndEstado : '');

        $acuseB64 = $get($data, ['respuesta-xml', 'respuesta_xml', 'respuestaXml']);
        if (!is_string($acuseB64)) {
            $acuseB64 = null;
        }

        $mensaje  = null;
        $estadoMensaje  = null;
        $detalleMensaje = null;

        if ($acuseB64 !== null && $acuseB64 !== '') {
            $xml = base64_decode($acuseB64, true);
            if ($xml !== false && $xml !== '') {
                $parser = new StatusAcuseParser();
                $parsed = $parser->parse($xml);
                $mensaje  = $parsed['mensaje']  ?? null;
                $estadoMensaje  = $parsed['estadoMensaje']  ?? null;
                $detalleMensaje = $parsed['detalleMensaje'] ?? null;
            }
        }

        return new StatusResult(
            clave:          $clave,
            fecha:          $fecha,
            estado:         $estado,
            acuseXmlBase64: $acuseB64,
            httpStatus:     $httpStatus,
            rawBody:        $jsonBody,
            mensaje:        $mensaje,
            estadoMensaje:  $estadoMensaje,
            detalleMensaje: $detalleMensaje
        );
    }

    private function normalizeEstado(string $raw): string
    {
        $s = strtolower(trim($raw));
        return match ($s) {
            'aceptado'   => 'aceptado',
            'rechazado'  => 'rechazado',
            // Estados no finales los tratamos como "pendiente"
            'recibido', 'procesando', 'pendiente', 'error' => 'pendiente',
            default => 'pendiente',
        };
    }
}