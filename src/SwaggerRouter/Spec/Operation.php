<?php

namespace Alexcicioc\SwaggerRouter\Spec;

class Operation
{
    public $operationId;
    /** @var Parameter[] */
    public $parameters;
    /** @var array */
    public $security;
    /** @var Response[] */
    public $responses;
    /** @var array */
    public $consumes;

    public function __construct(
        string $operationId, array $parameters, array $security, array $consumes, array $responses
    )
    {
        $this->operationId = $operationId;
        $this->parameters = $parameters;
        $this->security = $security;
        $this->responses = $responses;
        $this->consumes = $consumes;
    }
}