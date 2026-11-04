<?php

namespace App\Exceptions;

class AttemptNotSubmittedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The result is available after the attempt is submitted.');
    }

    public function errorCode(): string
    {
        return 'ATTEMPT_NOT_SUBMITTED';
    }

    public function status(): int
    {
        return 409;
    }
}
