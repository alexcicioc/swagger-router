<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

interface MiddlewareInterface
{
    public function __invoke(SwaggerRequest $request, SwaggerResponse $response, callable $next): SwaggerResponse;
}
