<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

class ResponseHandler implements MiddlewareInterface
{
    use ValueOperationTrait;
    /** @var SwaggerRequest */
    private $request;
    /** @var SwaggerResponse */
    private $response;

    /**
     * @param SwaggerRequest $request
     * @param SwaggerResponse $response
     * @param callable $next
     *
     * @return SwaggerResponse
     */
    public function __invoke(SwaggerRequest $request, SwaggerResponse $response, callable $next): SwaggerResponse
    {
        $this->request = $request;
        $this->response = $response;
        if ($this->response->rawBody) {
            $this->transformResponse();
        }

        return $next($this->request, $this->response);
    }

    private function transformResponse()
    {
        $statusCode = $this->response->getStatusCode();
        $responses = $this->request->getSpecOperation()->responses;
        if (isset($responses[$statusCode])) {
            $schema = $responses[$statusCode]->schema;
        } else {
            $schema = $responses['default']->schema;
        }

        $this->applySchemaTransformations($schema, $this->response->rawBody);
    }
}
