<?php

namespace App\Support;

use RuntimeException;

/** A ballot the server refused, with a voter-friendly message and an HTTP status. */
class BallotRejected extends RuntimeException
{
    /** $unknownCode: the code doesn't exist or was used (counts towards CodeGuard). */
    public function __construct(string $message, public readonly int $status = 422, public readonly bool $unknownCode = false)
    {
        parent::__construct($message);
    }
}
