<?php

namespace EdwinJuarez\Mh\Infrastructure\Parsers;

final class StatusAcuseParser
{
    public function parse(string $xml): array
    {
        $dom  = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok   = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$ok || !$dom->documentElement) {
            return ['mensaje' => null, 'estadoMensaje' => null, 'detalleMensaje' => null];
        }

        $xp = new \DOMXPath($dom);
        $ns = $dom->documentElement->namespaceURI;
        if ($ns) {
            $xp->registerNamespace('mh', $ns);
        }

        $mensaje  = $this->firstText($xp, [
            "//mh:Mensaje",
            "//Mensaje",
            "//*[local-name()='Mensaje']",
        ]);
        
        $estado  = $this->firstText($xp, [
            "//mh:EstadoMensaje",
            "//EstadoMensaje",
            "//*[local-name()='EstadoMensaje']",
        ]);

        $detalle = $this->firstText($xp, [
            "//mh:DetalleMensaje",
            "//DetalleMensaje",
            "//*[local-name()='DetalleMensaje']",
        ]);

        return [
            'mensaje'  => $mensaje,
            'estadoMensaje'  => $estado,
            'detalleMensaje' => $detalle,
        ];
    }

    private function firstText(\DOMXPath $xp, array $queries): ?string
    {
        foreach ($queries as $q) {
            $nodes = $xp->query($q);
            if ($nodes && $nodes->length > 0) {
                $s = trim($nodes->item(0)->textContent ?? '');
                if ($s !== '') {
                    return $s;
                }
            }
        }
        return null;
    }
}
