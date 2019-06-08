<?php

namespace Alexcicioc\SwaggerRouter\Spec;

class Parameter
{
    public $in;
    public $name;
    /** @var Schema */
    public $schema;

    public function __construct(string $in, string $name, Schema $schema)
    {
        $this->in = $in;
        $this->name = $name;
        $this->schema = $schema;
    }

    public function getType() {
        return $this->schema->type;
    }
}