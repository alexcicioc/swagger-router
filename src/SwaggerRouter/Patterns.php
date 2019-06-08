<?php

namespace Alexcicioc\SwaggerRouter;

class Patterns
{
    const MATCH_PATH_PARAM_DEFINITION = '/\{.*\}/U';
    const EXTRACT_PATH_PARAM_NAME = '/\{(.*)\}/U';
    const MATCH_PATH_PARAM_VALUE = '[a-zA-Z0-9-_]+';
    const EXTRACT_PATH_PARAM_VALUE = '([a-zA-Z0-9-_]+)';

    const EXTRACT_ISO_DATE_TIME_PARTS = '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})\.(\d{3})Z$/';
    const EXTRACT_ISO_DATE_PARTS = '/^(\d{4})-(\d{2})-(\d{2})$/';

    public static function getMatchPathParams(string $definitionPath): string
    {
        $escapedDefinitionPath = str_replace('/', '\/', $definitionPath);
        $pathPattern = preg_replace(
            self::MATCH_PATH_PARAM_DEFINITION,
            self::MATCH_PATH_PARAM_VALUE,
            $escapedDefinitionPath
        );

        return "/^$pathPattern\/?$/";
    }

    public static function getExtractPathParams(string $definitionPath): string
    {
        // Create a regex to extract the parameter values
        $pathPattern = preg_replace(
            Patterns::MATCH_PATH_PARAM_DEFINITION,
            Patterns::EXTRACT_PATH_PARAM_VALUE,
            $definitionPath
        );
        $pathPattern = str_replace('/', '\/', $pathPattern);

        return "/$pathPattern/";
    }
}