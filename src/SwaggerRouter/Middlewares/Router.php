<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

class Router implements MiddlewareInterface
{
    public $controllersNamespace;

    /**
     * Router constructor.
     * @param string $controllersNamespace
     */
    public function __construct(string $controllersNamespace)
    {
        $this->controllersNamespace = $controllersNamespace;
    }

    /**
     * @param SwaggerRequest $request
     * @param SwaggerResponse $response
     * @param callable $next
     *
     * @return SwaggerResponse
     */
    public function __invoke(SwaggerRequest $request, SwaggerResponse $response, callable $next): SwaggerResponse
    {
        $routerPath = $request->getSpecPath();
        $operation = $request->getSpecOperation();

        $controllerWithNamespace = $this->controllersNamespace . '\\' . $routerPath->controllerName;
        $controllerMethod = $operation->operationId;

        $response = (new $controllerWithNamespace())->{$controllerMethod}($request, $response);

        return $next($request, $response);
    }
}
