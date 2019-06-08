<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Patterns;
use Alexcicioc\SwaggerRouter\Spec\Parameter;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

class ParamsHandler implements MiddlewareInterface
{
    use ValueOperationTrait;
    /** @var SwaggerRequest */
    private $request;

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
        $parsedBody = $this->getParsedBody();

        if ($parsedBody) {
            $this->request = $this->request->withParsedBody($parsedBody);
        }

        $operation = $this->request->getSpecOperation();
        $parameters = [];

        foreach ($operation->parameters as $parameterDef) {
            $parameters[$parameterDef->name] = $this->getParameterValue($parameterDef);
        }

        $this->request = $this->request->withSwaggerParams($parameters);

        return $next($this->request, $response);
    }

    private function getParsedBody()
    {
        $contentType = $this->request->getHeader('Content-Type')[0] ?? null;
        switch ($contentType) {
            case 'application/json':
                $body = $this->request->getBody();
                if ($body) {
                    $decodedJsonBody = json_decode($body);
                    if ($decodedJsonBody) {
                        return $decodedJsonBody;
                    }
                }
                break;
            case 'application/x-www-form-urlencoded':
                if (!empty($_POST)) {
                    return $_POST;
                }
                break;
        }

        return null;
    }


    private function getPathParams(): array
    {
        $requestPath = $this->request->getUri()->getPath();
        $definitionPath = $this->request->getSpecPath()->path;
        // extract the parameter names from the spec definition path
        preg_match_all(Patterns::EXTRACT_PATH_PARAM_NAME, $definitionPath, $paramNamesMatches);
        $parameterNames = $paramNamesMatches[1] ?? [];
        $pathRegex = Patterns::getExtractPathParams($definitionPath);
        // Match the path param values in the same order as the param names
        preg_match($pathRegex, $requestPath, $pathParamValues);
        // delete the full string match
        array_shift($pathParamValues);
        // Combine the names array and param values to create an associative array of path params
        return array_combine($parameterNames, $pathParamValues);
    }

    private function getParameterValue(Parameter $parameter)
    {
        $value = null;
        $schemaProperties = null;

        switch ($parameter->in) {
            case 'query':
                $value = $this->request->getQueryParams()[$parameter->name] ?? null;
                break;
            case 'path':
                $value = $this->getPathParams()[$parameter->name] ?? null;
                break;
            case 'body':
                $value = $this->request->getParsedBody() ?? null;
                break;
            case 'header':
                $value = $this->request->getHeaderLine($parameter->name) ?? null;
                break;
            case 'formData':
                if ($parameter->getType() === 'file') {
                    $value = $this->request->getUploadedFiles()[$parameter->name] ?? null;
                } else {
                    $value = $this->request->getParsedBody()[$parameter->name] ?? null;
                }
                break;
        }

        $this->applySchemaTransformations($parameter->schema, $value);

        return $value;
    }
}
