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
    /** @var array */
    public $middlewares;


    public function __construct(
        string $operationId, array $parameters, array $security, array $consumes, array $responses,
        array $middlewares
    )
    {
        $this->operationId = $operationId;
        $this->parameters = $parameters;
        $this->security = $security;
        $this->responses = $responses;
        $this->consumes = $consumes;
        $this->middlewares = $middlewares;
    }
}
