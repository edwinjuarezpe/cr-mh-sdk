<?php

namespace EdwinJuarez\Mh\Application\Ports\Auth;

interface TokenProviderInterface
{
    public function getAccessToken(): string;
}
