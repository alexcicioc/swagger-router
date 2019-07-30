<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Spec\Schema;

trait ValueOperationTrait
{
    protected function applySchemaTransformations(Schema $schema, &$value)
    {
        $this->transformValue($value, $schema);
        $this->applyDefaultValue($value, $schema->default);
        $this->transformByFormat($value, $schema->format);
    }

    private function transformObject(Schema $schema, &$value)
    {
        foreach ($schema->properties as $propertyName => $property) {
            $this->applySchemaTransformations(new Schema($property), $value->{$propertyName});
        }
    }

    private function transformArray(Schema $schema, &$value)
    {
        if ($schema->items && is_array($value)) {
            $itemsSchema = new Schema($schema->items);
            foreach ($value as $index => $arrayItem) {
                $this->applySchemaTransformations(
                    $itemsSchema,
                    $value[$index]
                );
            }
        }
    }

    /**
     * Validations happen before parsing the params so it's safe to cast them
     *
     * @param $value
     * @param Schema $schema
     */
    private function transformValue(&$value, Schema $schema): void
    {
        $sanitizeStrings = $this->sanitizeStrings ?? false;

        if ($value !== null) {
            if (!$schema->type && is_object($value)) {
                $schema->type = 'object';
            }

            switch ($schema->type) {
                case 'string':
                    $value = (string)$value == $value ? (string)$value : $value;
                    if ($sanitizeStrings) {
                        $value = htmlentities($value);
                    }
                    break;
                case 'integer':
                    $value = is_numeric($value) && (int)$value == $value ? (int)$value : $value;
                    break;
                case 'number':
                    if (is_numeric($value)) {
                        $intVal = (int)$value == $value ? (int)$value : $value;
                        $floatVal = (float)$value == $value ? (float)$value : $value;
                        $value = is_integer($intVal) ? $intVal : $floatVal;
                    }
                    break;
                case 'boolean':
                    $value = (bool)$value == $value ? (bool)$value : $value;
                    break;
                case 'object':
                    $this->transformObject($schema, $value);
                    break;
                case 'array':
                    $this->transformArray($schema, $value);
                    break;
            }
        }
    }

    private function applyDefaultValue(&$value, $default): void
    {
        if ($value === null && $default !== null) {
            $value = $default;
        }
    }

    private function transformByFormat(&$value, $format): void
    {
        switch ($format) {
            case 'date':
                $value = $this->transformDate($value);
                break;
            case 'date-time':
                $value = $this->transformDateTime($value);
                break;
        }
    }

    private function transformDateTime($dateTime)
    {
        $isoFormat = (new \DateTime($dateTime))->format('Y-m-d\TH:i:s.000\Z');
        return $isoFormat;
    }

    private function transformDate($dateTime)
    {
        $isoFormat = (new \DateTime($dateTime))->format('Y-m-d');
        return $isoFormat;
    }
}
