<?php

namespace Alexcicioc\SwaggerRouter\Spec;

class Path
{
    /** @var string  */
    public $path;
    /** @var string  */
    public $controllerName;
    /** @var Operation[] */
    public $operations;
    /** @var bool */
    public $hasPathParams;

    public function __construct(string $path, string $controllerName, array $operations, bool $hasPathParams)
    {
        $this->path = $path;
        $this->controllerName = $controllerName;
        $this->operations = $operations;
        $this->hasPathParams = $hasPathParams;
    }

    public function getOperation($method): Operation
    {
        return $this->operations[$method];
    }
}
