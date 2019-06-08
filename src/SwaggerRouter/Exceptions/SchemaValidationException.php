<?php

namespace Alexcicioc\SwaggerRouter\Exceptions;

class SchemaValidationException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }
}