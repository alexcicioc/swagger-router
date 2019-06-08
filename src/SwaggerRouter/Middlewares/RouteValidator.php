<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Exceptions\HttpException;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

class RouteValidator implements MiddlewareInterface
{
    /**
     * @param SwaggerRequest $request
     * @param SwaggerResponse $response
     * @param callable $next
     *
     * @return SwaggerResponse
     * @throws HttpException
     *
     */
    public function __invoke(SwaggerRequest $request, SwaggerResponse $response, callable $next): SwaggerResponse
    {
        $method = $request->getMethod();

        $routerPath = $request->getSpecPath();

        if (!$routerPath) {
            // No route definition exists
            throw new HttpException('Not Found', 404);
        }

        if (!isset($routerPath->operations[$method])) {
            // The method doesn't exist for the route
            throw new HttpException('Method not allowed', 405);
        }

        $contentTypeHeader = explode(";", $request->getHeaderLine('Content-Type'))[0];
        $operation = $request->getSpecOperation();
        $supportedContentTypes = array_merge($request->spec->getSupportedContentTypes(), $operation->consumes);

        if ($contentTypeHeader && !in_array($contentTypeHeader, $supportedContentTypes)) {
            throw new HttpException(
                "Unsupported Media Type $contentTypeHeader",
                415
            );
        }

        // Pass the request and response on to the next responder in the chain
        return $next($request, $response);
    }
}
