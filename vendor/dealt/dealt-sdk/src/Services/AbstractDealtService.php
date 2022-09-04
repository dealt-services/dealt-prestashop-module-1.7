<?php

namespace Dealt\DealtSDK\Services;

use Dealt\DealtSDK\DealtClient;
use Dealt\DealtSDK\Exceptions\InvalidArgumentException;
use Dealt\DealtSDK\GraphQL\GraphQLClient;
use Dealt\DealtSDK\GraphQL\GraphQLOperation;
use Dealt\DealtSDK\GraphQL\Types\Input\AbstractInputType;
use Dealt\DealtSDK\Utils\GraphQLFormatter;

abstract class AbstractDealtService
{
    /** @var DealtClient */
    protected $client;

    /**
     * @param DealtClient $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    public function getGQLClient(): GraphQLClient
    {
        return $this->client->gqlClient;
    }

    /**
     * Generic validation function used by services executing GraphQL operations.
     *
     * @todo validate types as well
     *
     * @param array<string, mixed> $params
     * @param GraphQLOperation     $operation
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public static function validateParameters($params, $operation)
    {
        $operationName   = $operation::getOperationName();

        try {
            self::validateInputParameters($params, $operation::$operationParameters);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("{$e->getMessage()} on $operationName");
        }
    }

    /**
     * Recursively validates operation parameters provided as snake case
     * against the GraphQL input definitions (usually camel case).
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $inputParameters
     * @param string               $paramPath
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private static function validateInputParameters($params, $inputParameters, $paramPath = '')
    {
        /* Reject unsupported parameters */
        foreach (array_keys($params) as $key) {
            if (!array_key_exists(GraphQLFormatter::snakeToCamelCase($key), $inputParameters)) {
                throw new InvalidArgumentException("unsupported parameter \$params{$paramPath}[\"$key\"]");
            }
        }

        /* validate required params */
        foreach ($inputParameters as $key => $inputDefinition) {
            if ($key == 'apiKey') {
                continue;
            }

            /** @var string */
            $inputType = is_array($inputDefinition) ? $inputDefinition['inputType'] : $inputDefinition;
            $required  = str_ends_with($inputType, '!');
            $paramKey  = GraphQLFormatter::camelToSnakeCase($key);

            if ($required && (!array_key_exists($paramKey, $params) || !isset($params[$paramKey]))) {
                throw new InvalidArgumentException("missing \$params{$paramPath}[\"$paramKey\"]");
            }

            if ($required && is_array($inputDefinition)) {
                /** @var array<string, mixed> */
                $subParams = $params[$paramKey];

                /** @var AbstractInputType */
                $subTypeKeys = $inputDefinition['inputClass'];
                self::validateInputParameters($subParams, $subTypeKeys::$inputDefinition, "{$paramPath}[\"{$paramKey}\"]");
            }
        }
    }
}
