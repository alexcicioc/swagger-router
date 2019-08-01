<?php

namespace Alexcicioc\SwaggerRouter\Spec;

/**
 * Class Schema
 * @package Alexcicioc\SwaggerRouter\Spec
 *
 * @property mixed $default
 * @property array|boolean|null $required
 * @property object|null $properties
 * @property string|null $type
 * @property array|null $enum
 * @property int|null $maxLength
 * @property int|null $minLength
 * @property int|null $minimum
 * @property int|null $maximum
 * @property int|null $format
 * @property object|null $items
 * @property object|null $pattern
 * @property object|null $x-mimetype
 */
class Schema
{
    public $schema;

    public function __construct(object $schema)
    {
        $this->schema = $schema;
    }

    public function __get($name)
    {
        return $this->schema->{$name} ?? null;
    }


    public function isObject(): bool
    {
        return is_object($this->properties);
    }


    public function getRequiredProperties(): array
    {
        if ($this->isObject()) {
            return $this->required ?? [];
        }
        return [];
    }
}
