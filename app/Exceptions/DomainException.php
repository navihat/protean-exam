<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A broken business rule. bootstrap/app.php renders it for /api as
 * {message, code} with the status below (C7.2, D12).
 */
abstract class DomainException extends RuntimeException
{
    abstract public function errorCode(): string;

    abstract public function status(): int;
}
