<?php

namespace Alexcicioc\SwaggerRouter\Spec;

class SwaggerRawPath extends Path
{
    public function __construct(string $path)
    {
        parent::__construct($path, '', [], false);
    }
}