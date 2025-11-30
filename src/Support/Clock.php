<?php

namespace EdwinJuarez\Mh\Support;

interface Clock
{
    public function now(): \DateTimeImmutable;
}