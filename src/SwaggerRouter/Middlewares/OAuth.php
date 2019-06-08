<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Exceptions\HttpException;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;

class OAuth implements MiddlewareInterface
{
    /**
     * @var ResourceServer
     */
    private $server;

    /**
     * @param ResourceServer $server
     */
    public function __construct(ResourceServer $server)
    {
        $this->server = $server;
    }

    /**
     * @param SwaggerRequest $request
     * @param SwaggerResponse $response
     * @param callable $next
     *
     * @return SwaggerResponse
     * @throws HttpException
     */
    public function __invoke(SwaggerRequest $request, SwaggerResponse $response, callable $next): SwaggerResponse
    {
        try {
            $operation = $request->getSpecOperation();
            if (!empty($operation->security)) {
                $request = $this->server->validateAuthenticatedRequest($request);
            }
        } catch (OAuthServerException $exception) {
            throw new HttpException($exception->getMessage(), 401);
        }
        return $next($request, $response);
    }
}
