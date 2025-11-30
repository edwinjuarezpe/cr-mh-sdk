<?php

namespace EdwinJuarez\Mh;

use EdwinJuarez\Mh\Application\Services\SubmitService;
use EdwinJuarez\Mh\Application\Services\StatusService;
use EdwinJuarez\Mh\Dto\SubmitAck;
use EdwinJuarez\Mh\Dto\SubmitOptions;
use EdwinJuarez\Mh\Dto\SubmitRequest;
use EdwinJuarez\Mh\Dto\StatusResult;
use EdwinJuarez\Mh\Infrastructure\Parsers\XmlSubmitRequestFactory;

/**
 * Fachada pública del SDK.
 * Ofrece métodos sencillos para enviar y consultar comprobantes.
 */
final class MhClient
{
    public function __construct(
        private readonly SubmitService            $submitService,
        private readonly StatusService            $statusService,
        private readonly XmlSubmitRequestFactory  $xmlFactory,
    ) {}

    /**
     * Envío directo desde un XML firmado (string).
     * Si pasas $opts, se aplican (callbackUrl / consecutivo).
     */
    public function submitXml(string $xmlFirmado, ?SubmitOptions $opts = null): SubmitAck
    {
        $req = $this->xmlFactory->createFromXml($xmlFirmado);

        if ($opts !== null) {
            $req = new SubmitRequest(
                clave:                 $req->clave,
                fechaEmision:          $req->fechaEmision,
                emisorTipo:            $req->emisorTipo,
                emisorNumero:          $req->emisorNumero,
                receptorTipo:          $req->receptorTipo,
                receptorNumero:        $req->receptorNumero,
                comprobanteXmlBase64:  $req->comprobanteXmlBase64,
                callbackUrl:           $opts->callbackUrl,
                consecutivoReceptor:   $opts->consecutivoReceptor
            );
        }

        return $this->submit($req);
    }

    /** Envío a partir de un DTO ya construido. */
    public function submit(SubmitRequest $req): SubmitAck
    {
        return $this->submitService->submit($req);
    }

    /** Consulta el estado por clave. */
    public function statusByClave(string $clave): StatusResult
    {
        return $this->statusService->statusByClave($clave);
    }
}
