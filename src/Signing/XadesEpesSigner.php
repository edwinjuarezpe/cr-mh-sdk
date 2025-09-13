<?php

namespace EdwinJuarez\Mh\Signing;

use DOMElement;
use DOMDocument;

class XadesEpesSigner extends AbstractSigner
{
    private const XMLDSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';
    private const XADES_NS   = 'http://uri.etsi.org/01903/v1.3.2#';
    private const C14N_ALG   = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const ENVELOPED_SIGNATURE = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private const SIGNED_PROPERTIES_TYPE = 'http://uri.etsi.org/01903#SignedProperties';
    private const MH_POLICY_IDENTIFIER = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/Resoluci%C3%B3n_General_sobre_disposiciones_t%C3%A9cnicas_comprobantes_electr%C3%B3nicos_para_efectos_tributarios.pdf';
    private const MH_POLICY_HASH_SHA256_B64 = 'DWxin1xWOeI8OuWQXazh4VjLWAaCLAA954em7DMh0h8=';

    private Algorithm $algorithm;

    public function __construct(?Algorithm $algorithm = null)
    {
        $this->algorithm = $algorithm ?? Algorithm::sha256();
    }

    public function doSign(Pkcs12 $credential): void
    {
        $ids = SignatureIds::new();

        // Digest del documento
        $docDigestB64 = $this->digestWholeDocumentEnveloped();

        $root = $this->dom->documentElement;

        $signature = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Signature');
        $signature->setAttribute('Id', $ids->signatureId);
        $root->appendChild($signature);

        // <ds:SignedInfo>
        $signedInfo = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:SignedInfo');
        $signature->appendChild($signedInfo);

        // <ds:CanonicalizationMethod>
        $canonMethod = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:CanonicalizationMethod');
        $canonMethod->setAttribute('Algorithm', self::C14N_ALG);
        $signedInfo->appendChild($canonMethod);

        // <ds:SignatureMethod>
        $sigMethod = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:SignatureMethod');
        $sigMethod->setAttribute('Algorithm', $this->algorithm->getSignatureUri());
        $signedInfo->appendChild($sigMethod);

        // <ds:Reference> al documento
        $docRef = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Reference');
        $docRef->setAttribute('Id', $ids->docRefId);
        $docRef->setAttribute('URI', '');
        $signedInfo->appendChild($docRef);

        // <ds:Transforms>
        $transforms = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Transforms');
        $docRef->appendChild($transforms);

        // <ds:Transform> Enveloped Signature
        $transformEnveloped = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Transform');
        $transformEnveloped->setAttribute('Algorithm', self::ENVELOPED_SIGNATURE);
        $transforms->appendChild($transformEnveloped);

        $docDigestMethod = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestMethod');
        $docDigestMethod->setAttribute('Algorithm', $this->algorithm->getDigestUri());
        $docRef->appendChild($docDigestMethod);
        
        // <ds:DigestValue> del documento        
        $docDigestValue = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestValue', $docDigestB64);
        $docRef->appendChild($docDigestValue);

        // <ds:SignatureValue>
        $signatureValue = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:SignatureValue');
        $signatureValue->setAttribute('Id', $ids->signatureValueId);
        $signature->appendChild($signatureValue);

        // <ds:KeyInfo> con X509Data y KeyValue
        $keyInfo = $this->buildKeyInfo($signature, $ids, $credential);

        // <ds:Reference> a KeyInfo
        $keyInfoRef = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Reference');
        $keyInfoRef->setAttribute('Id', $ids->keyInfoRefId);
        $keyInfoRef->setAttribute('URI', "#{$ids->keyInfoId}");
        $signedInfo->appendChild($keyInfoRef);

        // <ds:DigestMethod> para KeyInfo
        $keyInfoDigestMethod = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestMethod');
        $keyInfoDigestMethod->setAttribute('Algorithm', $this->algorithm->getDigestUri());
        $keyInfoRef->appendChild($keyInfoDigestMethod);

        // <ds:DigestValue> para KeyInfo
        $keyInfoDigestValue = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestValue', $this->digestElementB64($keyInfo));
        $keyInfoRef->appendChild($keyInfoDigestValue);

        // <ds:Object> con <xades:SignedProperties>
        $signedProperties = $this->buildXadesObject($signature, $ids, $credential);

        $signedPropertiesRef = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Reference');
        $signedPropertiesRef->setAttribute('Type', self::SIGNED_PROPERTIES_TYPE);
        $signedPropertiesRef->setAttribute('URI', "#{$ids->signedPropsId}");
        $signedInfo->appendChild($signedPropertiesRef);

        $signedPropertiesDigestMethod = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestMethod');
        $signedPropertiesDigestMethod->setAttribute('Algorithm', $this->algorithm->getDigestUri());
        $signedPropertiesRef->appendChild($signedPropertiesDigestMethod);

        $signedPropertiesDigestValue = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestValue', $this->digestElementB64($signedProperties));
        $signedPropertiesRef->appendChild($signedPropertiesDigestValue);

        // Calcular el digest del documento (sin ds:Signature)
        $signedInfoC14N = $this->canonicalize($signedInfo);
        $signatureRaw = $this->opensslSign($signedInfoC14N, $credential->getPkeyPem());
        $signatureValue->nodeValue = base64_encode($signatureRaw);

    }

    private function digestWholeDocumentEnveloped(): string
    {
        $clone = new DOMDocument($this->dom->xmlVersion ?: '1.0', $this->dom->encoding ?: 'utf-8');
        $clone->loadXML($this->dom->saveXML());

        // Eliminar cualquier nodo ds:Signature en el clon
        $xp = new \DOMXPath($clone);
        $xp->registerNamespace('ds', self::XMLDSIG_NS);
        foreach ($xp->query('//ds:Signature') as $sigNode) {
            $sigNode->parentNode->removeChild($sigNode);
        }

        $c14n = $clone->C14N();
        return base64_encode(hash($this->algorithm->getName(), $c14n, true));
    }

    private function buildKeyInfo(DOMElement $signature, SignatureIds $ids, Pkcs12 $credential): DOMElement
    {
        // Signature / KeyInfo
        $keyInfo = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:KeyInfo');
        $keyInfo->setAttribute('Id', $ids->keyInfoId);
        $signature->appendChild($keyInfo);

        // X509Data / Certificate
        $x509Data = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:X509Data');
        $keyInfo->appendChild($x509Data);

        $x509Cert = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:X509Certificate', $credential->x509CertificateBase64());
        $x509Data->appendChild($x509Cert);

        // KeyValue / RSAKeyValue
        $keyValue = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:KeyValue');
        $keyInfo->appendChild($keyValue);

        $rsaKV = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:RSAKeyValue');
        $keyValue->appendChild($rsaKV);

        $modulus = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Modulus', $credential->publicKeyModulusBase64());
        $rsaKV->appendChild($modulus);

        $exponent = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Exponent', $credential->publicKeyExponentBase64());
        $rsaKV->appendChild($exponent);

        return $keyInfo;
    }

    private function buildXadesObject(DOMElement $signature, SignatureIds $ids, Pkcs12 $credential): DOMElement
    {
        // ds:Object / Signature
        $object = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:Object');
        $object->setAttribute('Id', $ids->xadesObjectId);
        $signature->appendChild($object);

        // xades:QualifyingProperties
        $qualifyingProperties = $this->dom->createElementNS(self::XADES_NS, 'xades:QualifyingProperties');
        $qualifyingProperties->setAttribute('Id', $ids->qualifyingPropsId);
        $qualifyingProperties->setAttribute('Target', "#{$ids->signatureId}");
        $object->appendChild($qualifyingProperties);

        // xades:SignedProperties
        $signedProperties = $this->dom->createElementNS(self::XADES_NS, 'xades:SignedProperties');
        $signedProperties->setAttribute('Id', $ids->signedPropsId);
        $qualifyingProperties->appendChild($signedProperties);

        // xades:SignedSignatureProperties
        $signedSignatureProperties = $this->dom->createElementNS(self::XADES_NS, 'xades:SignedSignatureProperties');
        $signedProperties->appendChild($signedSignatureProperties);

        // SigningTime
        $dti = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $signedSignatureProperties->appendChild($this->dom->createElementNS(self::XADES_NS, 'xades:SigningTime', $dti->format('Y-m-d\TH:i:s\Z')));

        // SigningCertificate -> Cert -> CertDigest + IssuerSerial
        $signingCertificate = $this->dom->createElementNS(self::XADES_NS, 'xades:SigningCertificate');
        $signedSignatureProperties->appendChild($signingCertificate);

        $cert = $this->dom->createElementNS(self::XADES_NS, 'xades:Cert');
        $signingCertificate->appendChild($cert);

        $certDigest = $this->dom->createElementNS(self::XADES_NS, 'xades:CertDigest');
        $cert->appendChild($certDigest);

        $cdDM = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestMethod');
        $cdDM->setAttribute('Algorithm', $this->algorithm->getDigestUri());
        $certDigest->appendChild($cdDM);

        // digest del cert en DER
        $certDigestB64 = $this->certShaDigestBase64($credential->getCertPem(), $this->algorithm->getName());
        $cdDV = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestValue', $certDigestB64);
        $certDigest->appendChild($cdDV);

        $issuerSerial = $this->dom->createElementNS(self::XADES_NS, 'xades:IssuerSerial');
        $cert->appendChild($issuerSerial);

        $x509IssuerName = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:X509IssuerName', $credential->x509IssuerName());
        $issuerSerial->appendChild($x509IssuerName);

        $x509SerialNumber = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:X509SerialNumber', $credential->x509SerialNumber());
        $issuerSerial->appendChild($x509SerialNumber);

        // SignaturePolicyIdentifier -> EPES
        $signaturePolicyIdentifier = $this->dom->createElementNS(self::XADES_NS, 'xades:SignaturePolicyIdentifier');
        $signedSignatureProperties->appendChild($signaturePolicyIdentifier);

        $signaturePolicyId = $this->dom->createElementNS(self::XADES_NS, 'xades:SignaturePolicyId');
        $signaturePolicyIdentifier->appendChild($signaturePolicyId);

        $sigPolicyId = $this->dom->createElementNS(self::XADES_NS, 'xades:SigPolicyId');
        $signaturePolicyId->appendChild($sigPolicyId);

        $identifier = $this->dom->createElementNS(self::XADES_NS, 'xades:Identifier', self::MH_POLICY_IDENTIFIER);
        $sigPolicyId->appendChild($identifier);

        $sigPolicyHash = $this->dom->createElementNS(self::XADES_NS, 'xades:SigPolicyHash');
        $signaturePolicyId->appendChild($sigPolicyHash);

        $sphDM = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestMethod');
        $sphDM->setAttribute('Algorithm', $this->algorithm->getDigestUri());
        $sigPolicyHash->appendChild($sphDM);

        $sphDV = $this->dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestValue', self::MH_POLICY_HASH_SHA256_B64);
        $sigPolicyHash->appendChild($sphDV);

        // xades:SignedDataObjectProperties -> DataObjectFormat
        $signedDataObjectProperties = $this->dom->createElementNS(self::XADES_NS, 'xades:SignedDataObjectProperties');
        $signedProperties->appendChild($signedDataObjectProperties);

        $dataObjectFormat = $this->dom->createElementNS(self::XADES_NS, 'xades:DataObjectFormat');
        $dataObjectFormat->setAttribute('ObjectReference', "#{$ids->docRefId}");
        $signedDataObjectProperties->appendChild($dataObjectFormat);

        $mimeType = $this->dom->createElementNS(self::XADES_NS, 'xades:MimeType', 'text/xml');
        $dataObjectFormat->appendChild($mimeType);

        $encoding  = $this->dom->createElementNS(self::XADES_NS, 'xades:Encoding', strtoupper($this->dom->encoding ?: 'UTF-8'));
        $dataObjectFormat->appendChild($encoding);

        return $signedProperties;
    }

    private function digestElementB64(DOMElement $el): string
    {
        $c14n = $this->canonicalize($el);
        return base64_encode(hash($this->algorithm->getName(), $c14n, true));
    }

    private function canonicalize(DOMElement $el): string
    {
        return $el->C14N();
    }

    private function certShaDigestBase64(string $certPem, string $hashAlgo): string
    {
        $raw = openssl_x509_fingerprint($certPem, $hashAlgo, true);
        if ($raw === false) {
            throw new \RuntimeException('openssl_x509_fingerprint failed.');
        }
        return base64_encode($raw);
    }

    private function opensslSign(string $data, string $pkeyPem): string
    {
        $pkey = openssl_pkey_get_private($pkeyPem);
        if ($pkey === false) {
            throw new \RuntimeException('Invalid private key.');
        }
        $signature = '';
        if (!openssl_sign($data, $signature, $pkey, $this->algorithm->getOpensslAlgo())) {
            throw new \RuntimeException('OpenSSL sign failed: '.(openssl_error_string() ?: 'unknown error'));
        }
        return $signature;
    }

}