<?php

namespace App\Exceptions;

class ExamNotAvailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This exam is not published or has no questions.');
    }

    public function errorCode(): string
    {
        return 'EXAM_NOT_AVAILABLE';
    }

    public function status(): int
    {
        return 422;
    }
}
