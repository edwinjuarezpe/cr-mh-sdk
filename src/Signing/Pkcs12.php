<?php

namespace EdwinJuarez\Mh\Signing;

final class Pkcs12
{
    private string $certPem;
    private string $pkeyPem;
    private array $extraCertsPem = [];
    private ?array $publicKeyDetails = null;

    private function __construct()
    {
    }

    public static function fromFile(string $path, string $password): self
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException("Cannot read PKCS#12: $path");
        }
        return self::fromString($raw, $password);
    }

    public static function fromString(string $pkcs12, string $password): self
    {
        if (!openssl_pkcs12_read($pkcs12, $certData, $password)) {
            throw new \RuntimeException('Invalid PKCS#12 or incorrect password.');
        }

        $certPem = $certData['cert'] ?? null;
        $pkeyPem = $certData['pkey'] ?? null;

        if (!is_string($certPem) || $certPem === '') {
            throw new \RuntimeException('The PKCS#12 file does not contain a valid X.509 certificate.');
        }
        if (!is_string($pkeyPem) || $pkeyPem === '') {
            throw new \RuntimeException('The PKCS#12 file does not contain a private key.');
        }
      
        $self = new self();
        $self->certPem = $certPem;
        $self->pkeyPem = $pkeyPem;

        if (!empty($certData['extracerts'])) {
            foreach ((array)($certData['extracerts']) as $c) {
                if (is_string($c) && $c !== '') {
                    $self->extraCertsPem[] = $c;
                }
            }
        }
        return $self;
    }

    public function x509CertificateBase64(): string
  	{
  		if (is_null($this->certPem) || $this->certPem === "") {
    		return "";
    	}

        $output = null;
        if (!openssl_x509_export($this->certPem, $output)) {
            throw new \RuntimeException('Failed to export certificate.');
        }

    	$output = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', $output);
		return $output;
  	}

	public function publicKeyModulusBase64(): string
    {
        $details = $this->getPublicKeyDetails();
		return base64_encode($details['rsa']['n']);
	}

	public function publicKeyExponentBase64(): string
	{
		$details = $this->getPublicKeyDetails();
		return base64_encode($details['rsa']['e']);
	}

    public function x509IssuerName(): string
    {
    	$info = openssl_x509_parse($this->certPem);
		$issuer = $info['issuer'] ?? null;

        if (is_array($issuer)) {
             $map = [
                'countryName'            => 'C',
                'stateOrProvinceName'    => 'ST',
                'localityName'           => 'L',
                'organizationName'       => 'O',
                'organizationalUnitName' => 'OU',
                'commonName'             => 'CN',
                'serialNumber'           => 'serialNumber',
                'organizationIdentifier' => '2.5.4.97',
            ];

            $output = [];
            foreach($issuer as $item => $value) {
                $attr   = $map[$item] ?? $item;
	    	    $output[] = $attr . '=' . $value;
	        }

            return implode(",", array_reverse($output));
        }
        throw new \RuntimeException('Certificate issuer not available in a supported format.');
	}
    
    public function x509SerialNumber(): string
    {
    	$info = openssl_x509_parse($this->certPem);

        if (isset($info['serialNumber']) && $info['serialNumber'] !== '') {
            return (string) $info['serialNumber'];
        }
         throw new \RuntimeException('Failed to get serial number from certificate.');
	}

    private function getPublicKeyDetails(): array
    {
        if ($this->publicKeyDetails !== null) {
            return $this->publicKeyDetails;
        }
      
        $publicKey = openssl_pkey_get_public($this->certPem);
        if ($publicKey === false) {
            $err = openssl_error_string() ?: 'Unknown OpenSSL error';
            throw new \RuntimeException("Failed to get public key from certificate. {$err}");
        }
        $details = openssl_pkey_get_details($publicKey);
        if ($details === false) {
            $err = openssl_error_string() ?: 'Unknown OpenSSL error';
            throw new \RuntimeException("Failed to get public key details. {$err}");
        }
        
        return $this->publicKeyDetails = $details;
    }

    public function getCertPem(): string
    {
        return $this->certPem;
    }

    public function getPkeyPem(): string
    {
        return $this->pkeyPem;
    }

    public function getExtraCertsPem(): array
    {
        return $this->extraCertsPem;
    }
}
