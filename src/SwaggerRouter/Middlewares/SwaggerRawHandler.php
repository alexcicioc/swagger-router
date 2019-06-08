<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Spec\SwaggerRawPath;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

class SwaggerRawHandler implements MiddlewareInterface
{
    /**
     * @param SwaggerRequest $request
     * @param SwaggerResponse $response
     * @param callable $next
     *
     * @return SwaggerResponse
     */
    public function __invoke(SwaggerRequest $request, SwaggerResponse $response, callable $next): SwaggerResponse
    {
        $specPath = $request->getSpecPath();
        if ($specPath instanceof SwaggerRawPath) {
            $response->withStatus(200)->body($request->spec->getRawSpec())->send();
            die();
        }
        return $next($request, $response);
    }
}