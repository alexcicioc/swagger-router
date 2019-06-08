<?php


namespace Alexcicioc\SwaggerRouter;


class SwaggerRouter
{
    /** @var array */
    private $middlewares;

    public function use(callable $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function processNext(SwaggerRequest $request, SwaggerResponse $response)
    {
        $middleware = array_shift($this->middlewares);
        if ($middleware) {
            return $middleware($request, $response, $this);
        }
        return $response;
    }

    public function __invoke(SwaggerRequest $request, SwaggerResponse $response)
    {
        return $this->processNext($request, $response);
    }
}
