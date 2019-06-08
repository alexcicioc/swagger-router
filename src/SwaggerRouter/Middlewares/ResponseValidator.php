<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Exceptions\BadSpecException;
use Alexcicioc\SwaggerRouter\Exceptions\HttpException;
use Alexcicioc\SwaggerRouter\Exceptions\SchemaValidationException;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

class ResponseValidator implements MiddlewareInterface
{
    use ParameterValidationTrait;
    /**
     * @var SwaggerResponse
     */
    private $response;
    /**
     * @var SwaggerRequest
     */
    private $request;

    /**
     * @param SwaggerRequest $request
     * @param SwaggerResponse $response
     * @param callable $next
     *
     * @return SwaggerResponse
     * @throws BadSpecException
     * @throws HttpException
     *
     * @throws SchemaValidationException
     */
    public function __invoke(SwaggerRequest $request, SwaggerResponse $response, callable $next): SwaggerResponse
    {
        $this->response = $response;
        $this->request = $request;
        $this->validateResponse();

        return $next($request, $response);
    }

    /**
     * @throws BadSpecException
     * @throws SchemaValidationException
     */
    public function validateResponse()
    {
        $statusCode = $this->response->getStatusCode();
        $operation = $this->request->getSpecOperation();
        $responses = $operation->responses;
        if (isset($responses[$statusCode])) {
            $schema = $responses[$statusCode]->schema;
        } elseif (isset($responses['default'])) {
            $schema = $responses['default']->schema;
        } else {
            throw new SchemaValidationException("Unknown status code $statusCode for $operation->operationId");
        }

        if ($this->response->rawBody) {
            $this->validateProperties(
                $this->response->rawBody,
                $schema->properties,
                $schema->getRequiredProperties(),
                'responseBody'
            );
        }
    }
}
