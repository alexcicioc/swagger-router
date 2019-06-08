<?php

namespace Alexcicioc\SwaggerRouter\Spec;

class Response
{
    /** @var Schema */
    public $schema;
    /** @var string */
    public $statusCode;

    public function __construct(string $statusCode, Schema $schema)
    {
        $this->statusCode = $statusCode;
        $this->schema = $schema;
    }
}