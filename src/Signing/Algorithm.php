<?php

namespace EdwinJuarez\Mh\Signing;

class Algorithm
{
    private $name;  
    private $signatureUri; 
    private $digestUri; 
    private $opensslAlgo;

    public function __construct(string $name, string $signatureUri, string $digestUri, int $opensslAlgo)
    {
        $this->name         = $name;
        $this->signatureUri = $signatureUri;
        $this->digestUri    = $digestUri;
        $this->opensslAlgo  = $opensslAlgo;
    }


     public static function sha1()
    {
        return new self(
            'sha1',
            'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
            'http://www.w3.org/2000/09/xmldsig#sha1',
            \OPENSSL_ALGO_SHA1
        );
    }

    public static function sha256()
    {
        return new self(
            'sha256',
            'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
            'http://www.w3.org/2001/04/xmlenc#sha256',
            \OPENSSL_ALGO_SHA256
        );
    }

    public static function sha384()
    {
        return new self(
            'sha384',
            'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384',
            'http://www.w3.org/2001/04/xmldsig-more#sha384',
            \OPENSSL_ALGO_SHA384
        );
    }

    public static function sha512()
    {
        return new self(
            'sha512',
            'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512',
            'http://www.w3.org/2001/04/xmlenc#sha512',
            \OPENSSL_ALGO_SHA512
        );
    }

    public static function fromString($algo)
    {
        switch (strtolower(trim((string)$algo))) {
            case 'sha1':   return self::sha1();
            case 'sha384': return self::sha384();
            case 'sha512': return self::sha512();
            case 'sha256':
            default:       return self::sha256(); 
        }
    }

    /**
     * Get the value of name
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the value of signatureUri
     */ 
    public function getSignatureUri()
    {
        return $this->signatureUri;
    }

    /**
     * Get the value of digestUri
     */ 
    public function getDigestUri()
    {
        return $this->digestUri;
    }

    /**
     * Get the value of opensslAlgo
     */ 
    public function getOpensslAlgo()
    {
        return $this->opensslAlgo;
    }

    public function isSupported()
    {
        return \in_array($this->name, \hash_algos(), true);
    }
}
