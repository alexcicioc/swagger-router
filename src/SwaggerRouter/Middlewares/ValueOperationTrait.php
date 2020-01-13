<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Spec\Schema;

trait ValueOperationTrait
{
    protected function applySchemaTransformations(Schema $schema, &$value)
    {
        if ($value !== null) {
            if (is_scalar($value)) {
                $this->transformPrimitives($value, $schema);
            } else {
                $this->transformCompositeTypes($value, $schema);
            }
            $this->applyDefaultValue($value, $schema->default);
            $this->transformByFormat($value, $schema->format);
        }
        $schema->collectionFormat && $this->transformByCollectionFormat($value, $schema->collectionFormat);
    }

    private function transformByCollectionFormat(&$value, string $collectionFormat): void
    {
        if (strlen($value) === 0) {
            $value = null;
            return;
        }

        switch ($collectionFormat) {
            case 'csv':
                $value = explode(',', $value);
                break;
            case 'ssv':
                $value = explode(' ', $value);
                break;
            case 'tsv':
                $value = explode("\t", $value);
                break;
            case 'pipes':
                $value = explode("|", $value);
                break;
            default:
                $value = null;
                break;
        }
    }

    private function transformObject(Schema $schema, &$value): void
    {
        if ($schema->properties) {
            foreach ($schema->properties as $propertyName => $property) {
                $this->applySchemaTransformations(new Schema($property), $value->{$propertyName});
            }
        }
    }

    private function transformArray(Schema $schema, &$value): void
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

    private function transformCompositeTypes(&$value, Schema $schema): void
    {
        if (!$schema->type && is_object($value) && $schema->properties) {
            $schema->type = 'object';
        }

        switch ($schema->type) {
            case 'object':
                $this->transformObject($schema, $value);
                break;
            case 'array':
                $this->transformArray($schema, $value);
                break;
        }
    }

    private function transformPrimitives(&$value, Schema $schema): void
    {
        switch ($schema->type) {
            case 'string':
                $value = (string)$value == $value ? (string)$value : $value;
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
                // var_dump((bool)"false"); => bool(true)
                if ($value === 'false') {
                    $value = false;
                    break;
                }
                $value = (bool)$value == $value ? (bool)$value : $value;
                break;
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
