<?php

namespace EdwinJuarez\Mh\Signing;

use EdwinJuarez\Mh\Support\IdGenerator;

class SignatureIds
{
    public function __construct(
        public readonly string $signatureId,
        public readonly string $signatureValueId,
        public readonly string $keyInfoId,
        public readonly string $signedPropsId,
        public readonly string $docRefId,
        public readonly string $keyInfoRefId,
        public readonly string $xadesObjectId,
        public readonly string $qualifyingPropsId
    )
    {
    }

    public static function new(): self
    {
        $uuid = IdGenerator::uuidV4();

         // IDs específicos
        $signatureId       = "Signature-{$uuid}";
        $signatureValueId  = "SignatureValue-{$uuid}";
        $keyInfoId         = "KeyInfoId-{$signatureId}";
        $signedPropsId     = "SignedProperties-{$signatureId}";

        // Otros IDs con UUIDs independientes
        $docRefId          = 'Reference-' . IdGenerator::uuidV4();
        $keyInfoRefId      = 'ReferenceKeyInfo';
        $xadesObjectId     = 'XadesObjectId-' . IdGenerator::uuidV4();
        $qualifyingPropsId = 'QualifyingProperties-' . IdGenerator::uuidV4();

        return new self(
            $signatureId,
            $signatureValueId,
            $keyInfoId,
            $signedPropsId,
            $docRefId,
            $keyInfoRefId,
            $xadesObjectId,
            $qualifyingPropsId
        );
    }
}
