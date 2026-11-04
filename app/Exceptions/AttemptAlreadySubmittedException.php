<?php

namespace App\Exceptions;

class AttemptAlreadySubmittedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This attempt has already been submitted.');
    }

    public function errorCode(): string
    {
        return 'ATTEMPT_ALREADY_SUBMITTED';
    }

    public function status(): int
    {
        return 409;
    }
}
