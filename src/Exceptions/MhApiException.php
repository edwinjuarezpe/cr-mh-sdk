<?php

namespace EdwinJuarez\Mh\Exceptions;

final class MhApiException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $endpoint,
        public readonly ?string $snippet = null,
        ?\Throwable $previous = null
    ) {
        $msg = "API error {$status} on {$endpoint}";
        if ($snippet) {
            $short = mb_substr($snippet, 0, 300);
            $msg  .= " – {$short}".(mb_strlen($snippet) > 300 ? '…' : '');
        }
        parent::__construct($msg, 0, $previous);
    }
}