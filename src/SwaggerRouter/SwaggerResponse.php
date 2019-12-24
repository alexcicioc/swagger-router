<?php

namespace Alexcicioc\SwaggerRouter;

use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\Response;

class SwaggerResponse extends Response
{
    use MessageTrait;
    public $rawBody = '';

    public function withRawBody($body): self
    {
        $new = clone $this;
        $new->rawBody = $body;
        return $new;
    }

    /**
     * Needed to be more IDE friendly
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return self
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        return parent::withStatus($code, $reasonPhrase);
    }

    public function body($body): self
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
