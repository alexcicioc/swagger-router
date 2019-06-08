<?php

namespace Alexcicioc\SwaggerRouter;

use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\Response;

class SwaggerResponse extends Response
{
    use MessageTrait;
    /** @var string */
    public $reasonPhrase;
    public $rawBody = '';


    public function withRawBody($body)
    {
        $new = clone $this;
        $new->rawBody = $body;
        return $new;
    }

    /**
     * @param $body
     * @return SwaggerResponse
     */
    public function body($body): SwaggerResponse
    {
        if (is_object($body) || is_array($body)) {
            return $this
                ->withRawBody($body)
                ->withHeader('Content-Type', 'application/json');
        }

        return $this->withRawBody($body);
    }

    public function send()
    {
        http_response_code($this->getStatusCode());
        foreach ($this->getHeaders() as $headerName => $headerValue) {
            header("$headerName: " . implode(',', $headerValue));
        }
        $processedBody = $this->getProcessedBody();
        echo $processedBody ? $processedBody : $this->getBody();
    }

    private function getProcessedBody()
    {
        if (is_object($this->rawBody) || is_array($this->rawBody)) {
            return json_encode($this->rawBody);
        }
        return $this->rawBody ?? null;
    }
}