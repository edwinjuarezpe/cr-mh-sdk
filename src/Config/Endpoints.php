<?php

namespace EdwinJuarez\Mh\Config;

final class Endpoints
{
   public function __construct(private readonly Settings $cfg) {}

    // Recepción
    public function recepcion(): string 
    { 
        return '/recepcion'; 
    }

    public function estadoPorClave(string $clave): string 
    { 
        return '/recepcion/'.rawurlencode($clave); 
    }

    // OIDC (IdP)
    public function tokenUrl(): string  
    { 
        return rtrim($this->cfg->baseUrlIdp, '/').'/protocol/openid-connect/token'; 
    }

    public function logoutUrl(): string 
    { 
        return rtrim($this->cfg->baseUrlIdp, '/').'/protocol/openid-connect/logout'; 
    }
}
