# swagger-router

```php
use App\Services\Auth\AuthorizationFactory;
use Alexcicioc\SwaggerRouter\App;
use Alexcicioc\SwaggerRouter\Exceptions\HttpException;
use Alexcicioc\SwaggerRouter\Middlewares\OAuth;
use Alexcicioc\SwaggerRouter\Middlewares\ParamsHandler;
use Alexcicioc\SwaggerRouter\Middlewares\ResponseHandler;
use Alexcicioc\SwaggerRouter\Middlewares\ResponseValidator;
use Alexcicioc\SwaggerRouter\Middlewares\RouteValidator;
use Alexcicioc\SwaggerRouter\Middlewares\Router;
use Alexcicioc\SwaggerRouter\Middlewares\RequestValidator;
use Alexcicioc\SwaggerRouter\Middlewares\SpecParser;
use Alexcicioc\SwaggerRouter\Middlewares\SwaggerRawHandler;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

function handleException(Exception $exception, int $statusCode = 500)
    $responseBody = new \stdClass();
    $responseBody->message = $exception->getMessage();
    if (DEBUG_MODE) {
        $responseBody->trace = $exception->getTraceAsString();
    }
    (new SwaggerResponse())
        ->withStatus($statusCode)
        ->body($responseBody)
        ->send();
}

try {
    $app = new SwaggerRouter();
    $app->use(new SpecParser(SPEC_PATH)); # Path to swagger.json
    $app->use(new SwaggerRawHandler());
    $app->use(new RouteValidator());
    $app->use(new ParamsHandler());
    $app->use(new OAuth(AuthorizationFactory::makeResourceServer())); # This is optional, only if you use oauth
    $app->use(new RequestValidator());
    $app->use(new Router('\App\Api\Controllers')); # Controllers namespace (must be PSR-4 compliant
    $app->use(new ResponseHandler());
    $app->use(new ResponseValidator());

    $response = $app(SwaggerRequest::fromGlobals(), new SwaggerResponse());
    $response->send();
} catch (HttpException $e) {
    handleException($e, $e->getCode());
} catch (Exception $e) {
    handleException($e);
}

```
