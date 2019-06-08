<?php

namespace Alexcicioc\SwaggerRouter;

use Alexcicioc\SwaggerRouter\Middlewares\SpecParser;
use Alexcicioc\SwaggerRouter\Spec\Operation;
use Alexcicioc\SwaggerRouter\Spec\Path;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest;

class SwaggerRequest extends ServerRequest
{
    /** @var SpecParser */
    public $spec;
    /** @var array */
    private $swaggerParams;

    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     *
     * @return SwaggerRequest
     */
    public static function fromGlobals()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $body = new LazyOpenStream('php://input', 'r+');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        $request = new SwaggerRequest($method, $uri, $headers, $body, $protocol, $_SERVER);

        return $request
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    /**
     * @return int
     */
    public function getAuthenticatedUserId(): int
    {
        return $this->getAttribute('oauth_user_id');
    }

    /**
     * @return Path
     */
    public function getSpecPath()
    {
        $path = $this->getUri()->getPath();
        $path = str_replace('//', '/', $path);
        return $this->spec->getPath($path);
    }

    /**
     * @return Operation
     */
    public function getSpecOperation(): Operation
    {
        $method = $this->getMethod();
        $specPath = $this->getSpecPath();
        return $specPath->getOperation($method);
    }

    public function withSpec(SpecParser $spec)
    {
        $new = clone $this;
        $new->spec = $spec;
        return $new;
    }

    public function withSwaggerParams(array $params)
    {
        $new = clone $this;
        $new->swaggerParams = $params;
        return $new;
    }

    public function getParam(string $name)
    {
        return $this->swaggerParams[$name] ?? null;
    }

    public function getParams(array $paramNames = []): array
    {
        $parameters = $this->swaggerParams;
        if (!empty($paramNames)) {
            $parameters = array_filter($parameters, function ($value, $key) use ($paramNames) {
                return in_array($key, $paramNames) && $value !== null;
            }, ARRAY_FILTER_USE_BOTH);
        }

        return $parameters;
    }
}
