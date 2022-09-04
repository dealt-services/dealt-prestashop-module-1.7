<?php

namespace Dealt\DealtSDK\Utils;

class GraphQLFormatter
{
    /**
     * Format query by removing new lines, spaces and tabs.
     */
    public static function formatQuery(string $query): string
    {
        return trim((string) preg_replace('/\s\s+/', ' ', $query));
    }

    /**
     * Format parameters by stripping quotes on keys
     * when json encoding an array object.
     */
    public static function formatQueryParameters(string $queryParams): string
    {
        return trim((string) preg_replace('/"([^"]+)"\s*:\s*/', ' $1: ', $queryParams));
    }

    public static function camelToSnakeCase(string $input): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    public static function snakeToCamelCase(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}
