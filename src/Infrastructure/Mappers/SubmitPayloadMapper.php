<?php

namespace EdwinJuarez\Mh\Infrastructure\Mappers;

use EdwinJuarez\Mh\Dto\SubmitRequest;

final class SubmitPayloadMapper
{
    public function toArray(SubmitRequest $req): array
    {
        $payload = [
            'clave' => $req->clave,
            'fecha' => $req->fechaEmision,

            'emisor' => [
                'tipoIdentificacion'   => $req->emisorTipo,
                'numeroIdentificacion' => $req->emisorNumero,
            ],

        ];
        
        if ($req->receptorTipo !== null && $req->receptorNumero !== null) {
            $payload['receptor'] = [
                'tipoIdentificacion'   => $req->receptorTipo,
                'numeroIdentificacion' => $req->receptorNumero,
            ];
        }
        
        if ($req->callbackUrl) {
            $payload['callbackUrl'] = $req->callbackUrl;
        }
        if ($req->consecutivoReceptor) {
            $payload['consecutivoReceptor'] = $req->consecutivoReceptor;
        }
        
        $payload['comprobanteXml'] = $req->comprobanteXmlBase64;
        
        return $payload;
    }
}