<?php

namespace EdwinJuarez\Mh\Infrastructure\Parsers;

use EdwinJuarez\Mh\Dto\SubmitRequest;

final class XmlSubmitRequestFactory
{
    public function createFromXml(string $xmlFirmado): SubmitRequest
    {
        if (trim($xmlFirmado) === '') {
            throw new \InvalidArgumentException('XML firmado vacío.');
        }

        $dom  = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok   = $dom->loadXML($xmlFirmado, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$ok || !$dom->documentElement) {
            throw new \InvalidArgumentException('XML firmado inválido: no se pudo parsear.');
        }

        $xp     = new \DOMXPath($dom);
        $rootNs = $dom->documentElement->namespaceURI ?? null;
        if ($rootNs) {
            $xp->registerNamespace('fe', $rootNs);
        }

        $clave = self::nodeText(self::first($xp, 'Clave'));
        if ($clave === null) {
            throw new \InvalidArgumentException('XML: falta <Clave>.');
        }

        $fechaStr = self::nodeText(self::first($xp, 'FechaEmision'));
        if ($fechaStr === null) {
            throw new \InvalidArgumentException('XML: falta <FechaEmision>.');
        }
        $fechaEmision = $fechaStr;
        try {
            $dt = new \DateTimeImmutable($fechaStr);
            $fechaEmision = $dt->format('Y-m-d\TH:i:sP');
        } catch (\Throwable) {
        }

        $emiTipoNode   = $xp->query("//*[local-name()='Emisor']/*[local-name()='Identificacion']/*[local-name()='Tipo']")->item(0);
        $emisorTipo    = self::nodeText($emiTipoNode);
        if ($emisorTipo === null) {
            throw new \InvalidArgumentException('XML: falta Emisor/Identificacion/Tipo.');
        }

        $emiNumNode    = $xp->query("//*[local-name()='Emisor']/*[local-name()='Identificacion']/*[local-name()='Numero']")->item(0);
        $emisorNumero  = self::nodeText($emiNumNode);
        if ($emisorNumero === null) {
            throw new \InvalidArgumentException('XML: falta Emisor/Identificacion/Numero.');
        }

        $recTipoNode   = $xp->query("//*[local-name()='Receptor']/*[local-name()='Identificacion']/*[local-name()='Tipo']")->item(0);
        $recNumNode    = $xp->query("//*[local-name()='Receptor']/*[local-name()='Identificacion']/*[local-name()='Numero']")->item(0);
        $receptorTipo   = self::nodeText($recTipoNode);   // puede ser null
        $receptorNumero = self::nodeText($recNumNode);    // puede ser null

        $comprobanteXmlBase64 = base64_encode($xmlFirmado);

        return new SubmitRequest(
            clave:                $clave,
            fechaEmision:         $fechaEmision,
            emisorTipo:           $emisorTipo,
            emisorNumero:         $emisorNumero,
            receptorTipo:         $receptorTipo,
            receptorNumero:       $receptorNumero,
            comprobanteXmlBase64: $comprobanteXmlBase64,
            callbackUrl:          null,
            consecutivoReceptor:  null
        );
    }

    private static function nodeText(?\DOMNode $n): ?string
    {
        if (!$n) return null;
        $s = trim($n->textContent ?? '');
        return ($s === '') ? null : $s;
    }

    private static function first(\DOMXPath $xp, string $name): ?\DOMNode
    {
        foreach (["//fe:$name", "//$name", "//*[local-name()='$name']"] as $q) {
            $nodes = $xp->query($q);
            if ($nodes && $nodes->length > 0) {
                return $nodes->item(0);
            }
        }
        return null;
    }
}
