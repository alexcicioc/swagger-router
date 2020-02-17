# swagger-router

Swagger Router is a library that speeds up API development while having it documented.

It uses a json spec file compatible with [OpenAPI specification 2.0](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md) and should support most of the features but if you find something missing feel free to open a pull request or an issue.


## Install via composer:
``composer require alexcicioc/swagger-router``

## Using swagger-router to bootstrap the API:

### Defining the endpoints using swagger
You'll need to define your API endpoints in the swagger spec. Here's a simple example you can use (it's in yaml for readability but should be converted to json):

**Recommendation:** You can use [Swagger Editor](http://editor.swagger.io/#/) to safely modify the spec then click on the File menu and "Convert and save to JSON".
```yaml
swagger: '2.0'
info:
  version: 0.0.1
  title: Courses API
basePath: /api
schemes:
  - http
  - https
consumes:
  - application/json
produces:
  - application/json
paths:
  /courses:
    x-swagger-router-controller: Courses # used to route to the php controller class
    get:
      description: Returns a list of courses
      operationId: getCourses # used to route to the php controller method
      parameters:
        - $ref: '#/parameters/limitQuery'
        - $ref: '#/parameters/startIndexQuery'
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/CourseList'
        default:
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
  '/courses/{courseId}':
    x-swagger-router-controller: Courses
    get:
      description: Returns a course
      operationId: getCourse # used to route to the php controller method
      parameters:
        - $ref: '#/parameters/courseIdPath'
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Course'
        '404':
          description: Not Found
        default:
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
            
definitions:

  CourseList:
    properties:
      results:
        type: array
        items:
          $ref: '#/definitions/Course'
    required:
      - results
      
  Course:
    properties:
      id:
        description: Course id
        type: integer
        minimum: 1
      title:
        description: Course title
        type: string
        maxLength: 255
      shortDescription:
        description: Short description
        type: string
        maxLength: 255
      longDescription:
        description: Long description
        type: string
      status:
        description: Course status
        type: string
        enum:
          - not-started
          - in-progress
          - complete
        default: not-started
    required:
      - id
      - title
      - shortDescription
      - longDescription
      - status
  
  ErrorResponse:
    required:
      - message
    properties:
      message:
        description: Error Message
        type: string
        
parameters:

  courseIdPath:
    in: path
    name: courseId
    type: integer
    minimum: 1
    required: true
    
  limitQuery:
    in: query
    name: limit
    type: integer
    default: 10
    
  startIndexQuery:
    in: query
    name: startIndex
    type: integer
    default: 0
```

### Redirecting the requests
You'll need to redirect everything to index.php. If you're using apache you can add this to your .htaccess file

```apacheconfig
RewriteEngine on
RewriteRule ^(.*)$ index.php [NC,L,QSA]
```

### Bootstrapping the app using swagger-router
In index.php you'll have to build the SwaggerRouter instance. You can use this sample:

```php
try {
    $app = new SwaggerRouter();
    // Validates and extracts the information from your swagger spec
    $app->use(new SpecParser('/app/swagger.json')); # Path to your spec
    // Optional - Handles the /swagger endpoint that exposes the spec to frontend apps
    $app->use(new SwaggerRawHandler());
    // Validates the called route, http method and content type
    $app->use(new RouteValidator());
    // Handles extracting the parameters from the request and formatting them
    $app->use(new ParamsHandler());
    // Optional - Validates the OAuth2 token given in the Authorization header
    $app->use(new OAuth(AuthorizationFactory::makeResourceServer()));
    // Optional - Handles validating the request parameters
    $app->use(new RequestValidator());
    // Routes the request to it's specific controller (given by x-swagger-router-controller)
    $app->use(new Router('\App\Api\Controllers')); # Controllers namespace (must be PSR-4 compliant)
    // Handles formatting the response
    $app->use(new ResponseHandler());
    // Optional - Handles validating the response
    $app->use(new ResponseValidator());

    $swaggerRequest = SwaggerRequest::fromGlobals(); // extends PSR-7 ServerRequest
    $swaggerResponse = new SwaggerResponse(); // extends PSR-7 Response
    $response = $app($swaggerRequest, $swaggerResponse);
    $response->send();
} catch (HttpException $e) {
    (new SwaggerResponse())
        ->withStatus($e->getCode())
        ->body((object)['message' => $e->getMessage()])
        ->send();
}

```

### Creating a controller
Previously in the spec we added these lines:
```
x-swagger-router-controller: Courses
```
```
operationId: getCourses
```
This tells swagger-router to search for the `Courses` controller and call the method `getCourses`
```php

namespace App\Api\Controllers;

use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;

class Courses
{
    public function getCourses(SwaggerRequest $request, SwaggerResponse $response): SwaggerResponse
    {
        $filters = $request->getParam('technology');
        $limit = $request->getParam('limit');
        $startIndex = $request->getParam('startIndex');

        $results = [];
        // do db stuff here
        
        return $response
                ->withStatus(200)
                ->body((object)['results' => $results]);

    }
    
    public function getCourse(SwaggerRequest $request, SwaggerResponse $response): SwaggerResponse
    {
        $courseId = $request->getParam('courseId');
        
        $course = new \stdClass();
        $course->id = $courseId;
        // db stuff here
        
        return $response
                ->withStatus(200)
                ->body($course);
    }
}
```
# Sample spec:
https://github.com/alexcicioc/swagger-router/blob/master/sample-spec.json


# Laravel compatibility
Swagger Router's middlewares are not compatible with Laravel's yet, however there's a workaround this.

Here's a sample Laravel middleware that calls the swagger router (not properly tested, may have some side effects):

```php
<?php

namespace App\Http\Middleware;

use Alexcicioc\SwaggerRouter\Exceptions\HttpException;
use Alexcicioc\SwaggerRouter\Middlewares\ParamsHandler;
use Alexcicioc\SwaggerRouter\Middlewares\RequestValidator;
use Alexcicioc\SwaggerRouter\Middlewares\ResponseHandler;
use Alexcicioc\SwaggerRouter\Middlewares\ResponseValidator;
use Alexcicioc\SwaggerRouter\Middlewares\Router;
use Alexcicioc\SwaggerRouter\Middlewares\RouteValidator;
use Alexcicioc\SwaggerRouter\Middlewares\SpecParser;
use Alexcicioc\SwaggerRouter\Middlewares\SwaggerRawHandler;
use Alexcicioc\SwaggerRouter\SwaggerRequest;
use Alexcicioc\SwaggerRouter\SwaggerResponse;
use Alexcicioc\SwaggerRouter\SwaggerRouter;
use Closure;
use Illuminate\Http\Request;

class LaravelSwaggerRouter
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $app = new SwaggerRouter();
            // Validates and extracts the information from your swagger spec
            $app->use(new SpecParser('/var/www/php/specs/spec.json')); # Path to your spec
            // Optional - Handles the /swagger endpoint that exposes the spec to frontend apps
            $app->use(new SwaggerRawHandler());
            // Validates the called route, http method and content type
            $app->use(new RouteValidator());
            // Handles extracting the parameters from the request and formatting them
            $app->use(new ParamsHandler());
            // Optional - Handles validating the request parameters
            $app->use(new RequestValidator());
            // Routes the request to it's specific controller (given by x-swagger-router-controller)
            $app->use(new Router('\App\Http\Controllers')); # Controllers namespace (must be PSR-4 compliant)
            // Handles formatting the response
            $app->use(new ResponseHandler());
            // Optional - Handles validating the response
            $app->use(new ResponseValidator());

            $swaggerRequest = SwaggerRequest::fromGlobals();

            $swaggerResponse = new SwaggerResponse(); // extends PSR-7 Response
            $response = $app($swaggerRequest, $swaggerResponse);

            return response()->json($response->rawBody, $response->getStatusCode(), $response->getHeaders());
        } catch (HttpException $e) {
            return response()->json((object)['message' => $e->getMessage()], $e->getCode());
        }
    }
}
```
