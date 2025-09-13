<?php

namespace EdwinJuarez\Mh\Signing;
use DOMDocument;
use EdwinJuarez\Mh\Contracts\SignerInterface;

abstract class AbstractSigner implements SignerInterface
{
    protected DOMDocument $dom;
    
    final public function sign(string $pkcs12Path, string $pkcs12Password, string $xml): string
    {
        $credential = Pkcs12::fromFile($pkcs12Path, $pkcs12Password);
        
        $xmlString = $this->toXmlString($xml);
        
        $this->dom = new DOMDocument();
        $this->dom->loadXML($xmlString);
        
        $this->doSign($credential);

        return rtrim($this->dom->saveXML(), "\n");
    }

    private function toXmlString(string $input): string
    {
        if ($input instanceof \DomDocument) {
            return $input->saveXML();
        }

        if (\is_string($input)) {
            if (is_file($input)) {
                return $this->readFile($input);
            }
            $trim = ltrim($input);
            if ($trim !== '' && $trim[0] === '<') {
                return $input;
            }
        }

        throw new \InvalidArgumentException("Invalid XML input");
    }

    private function readFile(string $path): string
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException("Could not read XML file: $path");
        }
        return $data;
    }

    abstract protected function doSign(Pkcs12 $credential): void;
}
