<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Spec\Schema;

trait ValueOperationTrait
{
    protected function applySchemaTransformations(Schema $schema, &$value)
    {
        $this->transformValue($value, $schema);
        $schema->default && $this->applyDefaultValue($value, $schema->default);
        $schema->format && $this->transformByFormat($value, $schema->format);
        $schema->collectionFormat && $this->transformByCollectionFormat($value, $schema->collectionFormat);
    }

    private function transformByCollectionFormat(&$value, string $collectionFormat): void
    {
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
        }
    }

    private function transformObject(Schema $schema, &$value): void
    {
        foreach ($schema->properties as $propertyName => $property) {
            $this->applySchemaTransformations(new Schema($property), $value->{$propertyName});
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
