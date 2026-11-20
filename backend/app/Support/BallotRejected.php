<?php

namespace App\Support;

use RuntimeException;

/** A ballot the server refused, with a voter-friendly message and an HTTP status. */
class BallotRejected extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
