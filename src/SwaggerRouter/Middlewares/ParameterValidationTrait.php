<?php

namespace Alexcicioc\SwaggerRouter\Middlewares;

use Alexcicioc\SwaggerRouter\Exceptions\BadSpecException;
use Alexcicioc\SwaggerRouter\Exceptions\SchemaValidationException;
use Alexcicioc\SwaggerRouter\Spec\Schema;
use Alexcicioc\SwaggerRouter\Validations;

trait ParameterValidationTrait
{
    protected static function getSupportedValidations(): array
    {
        return [
            'type', 'required', 'properties', 'enum', 'maxLength', 'minLength',
            'minimum', 'maximum', 'format', 'items', 'x-mimetype'
        ];
    }

    /**
     * @param string $validationType
     * @param $value
     * @param Schema $schema
     * @param string $parameterName
     * @throws BadSpecException
     * @throws SchemaValidationException
     */
    protected function validate(string $validationType, $value, Schema $schema, string $parameterName): void
    {
        switch ($validationType) {
            case 'type':
                Validations::type($value, $schema->type, $parameterName);
                break;
            case 'enum':
                Validations::enum($value, $schema->enum, $parameterName);
                break;
            case 'required':
                if (is_bool($schema->required)) {
                    Validations::required($value, $schema->required, $parameterName);
                }
                break;
            case 'properties':
                $this->validateProperties($value, $schema->properties, $schema->getRequiredProperties(), $parameterName);
                break;
            case 'maxLength':
                Validations::maxLength($value, $schema->maxLength, $parameterName);
                break;
            case 'minLength':
                Validations::minLength($value, $schema->minLength, $parameterName);
                break;
            case 'minimum':
                Validations::minimum($value, $schema->minimum, $parameterName);
                break;
            case 'maximum':
                Validations::maximum($value, $schema->maximum, $parameterName);
                break;
            case 'format':
                $this->validateFormat($value, $schema->format, $parameterName);
                break;
            case 'items':
                $this->validateItems($value, $schema->items, $parameterName);
                break;
            case 'x-mimetype':
                Validations::mimeType($value, $schema->{'x-mimetype'}, $parameterName);
                break;
        }
    }

    /**
     * @param $value
     * @param string $format
     * @param $parameterName
     * @throws SchemaValidationException
     */
    protected function validateFormat($value, string $format, $parameterName)
    {
        switch ($format) {
            case 'date':
                Validations::date($value, $parameterName);
                break;
            case 'date-time':
                Validations::dateTime($value, $parameterName);
                break;
            case 'email':
                Validations::email($value, $parameterName);
                break;
        }
    }

    /**
     * @param $value
     * @param object $properties
     * @param array $requiredProperties
     * @param string $parameterName
     * @throws SchemaValidationException
     * @throws BadSpecException
     */
    public function validateProperties(
        $value, object $properties, array $requiredProperties, string $parameterName
    )
    {
        if (!is_object($value)) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' expected to be object but "
                . gettype($value) . " given instead"
            );
        }

        foreach ($properties as $propertyName => $propertyValueSchema) {
            if (!isset($value->{$propertyName})) {
                if (in_array($propertyName, $requiredProperties)) {
                    throw new SchemaValidationException(
                        "Required property '$propertyName' missing from '$parameterName'"
                    );
                }

                continue;
            }

            $propertyValue = $value->{$propertyName};
            $propertySchema = new Schema($properties->{$propertyName});
            $this->validateParam($propertyValue, $propertySchema, "$parameterName.$propertyName");
        }
    }


    /**
     * @param $items
     * @param $itemsDef
     * @param string $parameterName
     * @throws BadSpecException
     * @throws SchemaValidationException
     */
    public function validateItems(
        $items, $itemsDef, string $parameterName
    )
    {
        $schema = new Schema($itemsDef);
        foreach ($items as $index => $item) {
            $this->validateParam($item, $schema, $parameterName . "[$index]");
        }
    }

    /**
     * @param $value
     * @param $schema
     * @param $parameterName
     * @throws BadSpecException
     * @throws SchemaValidationException
     */
    public function validateParam($value, Schema $schema, string $parameterName)
    {
        $supportedValidations = self::getSupportedValidations();
        if ($value === null) {
            $supportedValidations = ['required'];
        }

        foreach ($supportedValidations as $validationType) {
            if ($schema->{$validationType} !== null) {
                $this->validate($validationType, $value, $schema, $parameterName);
            }
        }
    }
}