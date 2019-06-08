<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Exceptions\BadSpecException;
use Alexcicioc\SwaggerRouter\Exceptions\HttpException;
use Alexcicioc\SwaggerRouter\Exceptions\SchemaValidationException;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;
use Alexcicioc\SwaggerRouter\Validations;

class RequestValidator implements MiddlewareInterface
{
    use ParameterValidationTrait;
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
        foreach ($request->getSpecOperation()->parameters as $parameter) {
            $value = $request->getParam($parameter->name);
            $this->validateParam($value, $parameter->schema, $parameter->name);
        }
        $this->validateSecurity($request);

        // Pass the request and response on to the next responder in the chain
        return $next($request, $response);
    }

    /**
     * @param SwaggerRequest $request
     * @throws HttpException
     */
    public function validateSecurity(SwaggerRequest $request)
    {
        $operation = $request->getSpecOperation();

        foreach ($operation->security as $security) {
            foreach ($security as $securityName => $value) {
                $securityDef = $request->spec->getSecurityDefinition($securityName);
                $this->validateSecurityByType($request, $securityDef);
            }
        }
    }

    /**
     * @param SwaggerRequest $request
     * @param object $securityDef
     * @throws SchemaValidationException
     * @throws BadSpecException
     */
    private function validateSecurityByType(SwaggerRequest $request, object $securityDef)
    {
        switch ($securityDef->type) {
            // TODO handle all types of security
            case "apiKey":
                $value = $request->getHeader($securityDef->name)[0] ?? null;
                Validations::required($value, true, $securityDef->name);
                Validations::type($value, 'string', $securityDef->name);
                break;
        }
    }
}
