<?php

namespace Alexcicioc\SwaggerRouter;

use Alexcicioc\SwaggerRouter\Exceptions\BadSpecException;
use Alexcicioc\SwaggerRouter\Exceptions\SchemaValidationException;
use GuzzleHttp\Psr7\UploadedFile;

class Validations
{
    /**
     * @param $value
     * @param bool $required
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function required($value, bool $required, string $parameterName): void
    {
        if ($value === null && $required) {
            throw new SchemaValidationException(
                "Required parameter '$parameterName' missing"
            );
        }
    }

    /**
     * @param mixed $value
     * @param array $enum
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function enum($value, array $enum, string $parameterName): void
    {
        if ($enum && !in_array($value, $enum)) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' with value '$value' failed enum validation"
            );
        }
    }

    /**
     * @param mixed $value
     * @param string $type
     * @param string $parameterName
     * @throws BadSpecException
     * @throws SchemaValidationException
     */
    public static function type($value, string $type, string $parameterName): void
    {
        if (!self::isValidType($type, $value)) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' with type '$type' didn't match parameter value '$value'"
            );
        }
    }

    /**
     * @param $value
     * @param int $maxLength
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function maxLength($value, int $maxLength, string $parameterName): void
    {
        if (strlen($value) > $maxLength) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' exceeded maxLength of '$maxLength'"
            );
        }
    }

    /**
     * @param $value
     * @param int $minLength
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function minLength($value, int $minLength, string $parameterName): void
    {
        if (strlen($value) < $minLength) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' string length is lower than '$minLength'"
            );
        }
    }

    /**
     * @param $value
     * @param int $minimum
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function minimum(int $value, int $minimum, string $parameterName): void
    {
        if ($value < $minimum) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' lower than defined minimum of '$minimum'"
            );
        }
    }

    /**
     * @param int $value
     * @param int $maximum
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function maximum(int $value, int $maximum, string $parameterName): void
    {
        if ($value > $maximum) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' greater than defined maximum of '$maximum'"
            );
        }
    }


    /**
     * @param int $value
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function date($value, string $parameterName): void
    {
        if (!self::isValidIsoDate($value)) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' is not a valid iso date"
            );
        }
    }

    /**
     * @param int $value
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function dateTime($value, string $parameterName): void
    {
        if (!self::isValidIsoDateTime($value)) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' is not a valid iso date time"
            );
        }
    }

    /**
     * @param int $value
     * @param string $parameterName
     * @throws SchemaValidationException
     */
    public static function email($value, string $parameterName): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new SchemaValidationException(
                "Parameter '$parameterName' is not a valid email"
            );
        }
    }

    public static function isValidIsoDate($date)
    {
        if (preg_match(Patterns::EXTRACT_ISO_DATE_PARTS, $date, $parts) == true) {
            $time = gmmktime(0, 0, 0, $parts[2], $parts[3], $parts[1]);

            $input_time = strtotime($date);
            if ($input_time === false) {
                return false;
            }

            return $input_time == $time;
        }
        return false;
    }

    public static function isValidIsoDateTime($date)
    {
        if (preg_match(Patterns::EXTRACT_ISO_DATE_TIME_PARTS, $date, $parts) == true) {
            $time = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);

            $input_time = strtotime($date);
            if ($input_time === false) {
                return false;
            }

            return $input_time == $time;
        }
        return false;
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return bool
     * @throws BadSpecException
     */
    private static function isValidType(string $type, $value): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'integer':
                $value = is_numeric($value) && (int)$value == $value ? (int)$value : $value;
                return is_integer($value);
            case 'number':
                if (is_numeric($value)) {
                    $intVal = (int)$value == $value ? (int)$value : $value;
                    $floatVal = (float)$value == $value ? (float)$value : $value;
                    return is_integer($intVal) || is_float($floatVal);
                }
                return false;
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            case 'file':
                return true; // Not sure what to validate here
            default:
                throw new BadSpecException("Unknown data type '$type'");
        }
    }


    /**
     * @param $file
     * @param $mimeType
     * @param $parameterName
     * @throws SchemaValidationException
     */
    public static function mimeType(UploadedFile $file, string $mimeType, string $parameterName)
    {
        if ($file->getClientMediaType() !== $mimeType) {
            throw new SchemaValidationException(
                "Unsupported mime type " . $file->getClientMediaType() . " for $parameterName"
            );
        }
    }
}
