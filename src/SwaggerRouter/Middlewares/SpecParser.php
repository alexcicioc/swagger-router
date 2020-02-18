<?php
declare(strict_types=1);

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Exceptions\BadSpecException;
use Alexcicioc\SwaggerRouter\Patterns;
use Alexcicioc\SwaggerRouter\Spec\Operation;
use Alexcicioc\SwaggerRouter\Spec\Parameter;
use Alexcicioc\SwaggerRouter\Spec\Path;
use Alexcicioc\SwaggerRouter\Spec\Response;
use Alexcicioc\SwaggerRouter\Spec\Schema;
use Alexcicioc\SwaggerRouter\Spec\SwaggerRawPath;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

class SpecParser implements MiddlewareInterface
{
    const METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH'];
    /** @var \stdClass */
    private $spec;
    /** @var string */
    private $specPath;

    /**
     * Spec constructor.
     * @param string $specPath
     */
    public function __construct(string $specPath)
    {
        $this->specPath = $specPath;
    }

    /**
     * @param SwaggerRequest $request
     * @param SwaggerResponse $response
     * @param callable $next
     * @return SwaggerResponse
     * @throws BadSpecException
     */
    public function __invoke(SwaggerRequest $request, SwaggerResponse $response, callable $next): SwaggerResponse
    {
        if (!file_exists($this->specPath)) {
            throw new \InvalidArgumentException('Unable to load spec by path ' . $this->specPath);
        }
        $this->spec = json_decode(file_get_contents($this->specPath));
        if (!is_object($this->spec)) {
            throw new BadSpecException('Failed to process spec');
        }

        $request = $request->withSpec($this);
        return $next($request, $response);
    }

    public function getRawSpec()
    {
        return $this->spec;
    }

    public function getRoutes(): \Generator
    {
        $paths = $this->spec->paths;
        foreach ($paths as $path => $resource) {
            $fullPath = $this->getFullPath($path);

            if (isset($resource->{"x-swagger-pipe"}) && $resource->{"x-swagger-pipe"} == "swagger_raw") {
                yield new SwaggerRawPath($fullPath);
                continue;
            }
            if (!isset($resource->{"x-swagger-router-controller"})) {
                continue;
            }
            preg_match_all(Patterns::MATCH_PATH_PARAM_DEFINITION, $fullPath, $matches);
            $hasPathParams = !empty($matches[0]);

            yield new Path(
                $fullPath,
                $resource->{"x-swagger-router-controller"},
                $this->getPathOperations($resource),
                $hasPathParams
            );
        }
    }

    private function getPathOperations(object $resource): array
    {
        $operations = [];

        foreach (self::METHODS as $httpMethod) {
            if (!isset($resource->{strtolower($httpMethod)})) {
                continue;
            }
            $operation = $resource->{strtolower($httpMethod)};
            $parameters = isset($operation->parameters) ? $this->extractParams($operation->parameters) : [];
            $security = $operation->security ?? [];
            $responses = $this->extractResponses($operation);
            $consumes = $operation->consumes ?? [];
            $middlewares = ($operation->{"x-swagger-router-middlewares"}) ? ($operation->{"x-swagger-router-middlewares"}) : [] ;
            $operations[$httpMethod] = new Operation(
                $operation->operationId,
                $parameters,
                $security,
                $consumes,
                $responses,
                $middlewares
            );
        }

        return $operations;
    }

    public function extractResponses(object $operation): array
    {
        $operation->responses = $operation->responses ?? [];
        $responses = [];
        foreach ($operation->responses as $statusCode => $response) {
            $schema = new Schema($this->resolveRefs($response));
            $responses[$statusCode] = new Response($statusCode, $schema);
        }

        return $responses;
    }

    public function getSecurityDefinition(string $name)
    {
        return $this->spec->securityDefinitions->$name ?? null;
    }

    /**
     * @param array $rawParameters
     * @return Parameter[]
     */
    private function extractParams(array $rawParameters): array
    {
        $parameters = [];
        foreach ($rawParameters as $parameter) {

            $parameter = isset($parameter->{'$ref'}) ?
                $this->getRef($parameter->{'$ref'}) : $parameter;

            $schema = new Schema($this->resolveRefs($parameter));
            $parameters[] = new Parameter($parameter->in, $parameter->name, $schema);
        }
        return $parameters;
    }

    public function resolveRefs(object $object)
    {
        if (isset($object->{'$ref'})) {
            $object = $this->getRef($object->{'$ref'});
        } elseif (isset($object->schema->{'$ref'})) {
            $object = $this->getRef($object->schema->{'$ref'});
        } elseif (isset($object->schema->properties)) {
            $object = $object->schema;
        }

        foreach ($object as $key => $value) {

            if (isset($object->{$key}->schema->{'$ref'})) {
                $object->{$key} = $this->getRef($object->schema->{'$ref'});
            } else if (is_object($object->{$key})) {
                $object->{$key} = $this->resolveRefs($object->{$key});
            }
        }

        return $object;
    }

    public function getRef(string $ref)
    {
        [$parent, $definition] = explode('/', str_replace('#/', '', $ref));
        return $this->spec->{$parent}->{$definition};
    }

    public function getFullPath(string $path): string
    {
        return str_replace('//', '/', $this->getBasePath() . $path);
    }

    public function getBasePath(): string
    {
        return $this->spec->basePath ?? '/';
    }

    public function getSupportedContentTypes(): array
    {
        return $this->spec->consumes ?? [];
    }

    /**
     * @param string $path
     * @return Path
     */
    public function getPath(string $path)
    {
        foreach ($this->getRoutes() as $definitionPath) {
            if ($definitionPath->path === $path) {
                return $definitionPath;
            } elseif ($definitionPath->hasPathParams) {
                $matchPathParamsPattern = Patterns::getMatchPathParams($definitionPath->path);
                if (preg_match($matchPathParamsPattern, $path) === 1) {
                    return $definitionPath;
                }
            }
        }

        return null;
    }
}
