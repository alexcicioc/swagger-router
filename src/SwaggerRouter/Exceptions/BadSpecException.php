<?php

namespace Alexcicioc\SwaggerRouter\Exceptions;

class BadSpecException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 500);
    }
}