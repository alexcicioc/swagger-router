<?php

namespace Alexcicioc\SwaggerRouter\Exceptions;

class HttpException extends \Exception
{
    public function __construct(string $message, int $httpCode)
    {
        parent::__construct($message, $httpCode);
    }
}