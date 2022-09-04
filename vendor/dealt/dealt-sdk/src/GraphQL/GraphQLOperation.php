<?php

namespace Dealt\DealtSDK\GraphQL;

use Dealt\DealtSDK\Exceptions\GraphQLException;
use Dealt\DealtSDK\Exceptions\GraphQLFailureException;
use Dealt\DealtSDK\Exceptions\GraphQLInvalidParametersException;
use Dealt\DealtSDK\GraphQL\Types\Input\AbstractInputType;
use Dealt\DealtSDK\GraphQL\Types\Object\AbstractObjectType;
use Dealt\DealtSDK\Utils\GraphQLFormatter;
use Exception;

/**
 * @static @property string $operationType
 * @static @property string $operationName
 * @static @property array<string, mixed> $operationParameters
 * @static @property string $operationResult
 *
 * @property array<string, mixed> $queryVars
 */
abstract class GraphQLOperation implements GraphQLOperationInterface
{
    /** @var string */
    public static $operationType;

    /** @var string */
    public static $operationName;

    /** @var array<string, mixed> */
    public static $operationParameters;

    /** @var string */
    public static $operationResult;

    /** @var array<string, mixed> */
    public $queryVars;

    public function __construct()
    {
        $this->queryVars   = [];
    }

    /**
     * Returns the current GraphQL query/mutation name.
     */
    public static function getOperationName(): string
    {
        return static::$operationName;
    }

    /**
     * Guard function :
     * Will throw GraphQLInvalidParametersException when a required
     * operation variable is missing in the current operation.
     *
     * @throws GraphQLInvalidParametersException
     *
     * @return void
     */
    public function validateQueryParameters()
    {
        $params        = static::$operationParameters;
        $operationType = static::$operationType;
        $operationName = static::$operationName;

        foreach ($params as $param => $type) {
            $inputType = is_array($type) ? $type['inputType'] : $type;

            if (str_ends_with($inputType, '!') && !isset($this->queryVars[$param])) {
                throw new GraphQLInvalidParametersException("Missing parameter $$param of type $inputType in $operationName $operationType");
            }
        }
    }

    /**
     * builds the body of the GraphQL operation.
     *
     * @return string
     */
    public static function toQuery()
    {
        $operationType       = static::$operationType;
        $operationName       = static::$operationName;
        $operationResult     = static::$operationResult;
        $operationParameters = static::toQueryParametersDefinition();
        $queryParameters     = static::toQueryParameters();

        $query = "$operationType $operationName$operationParameters { $operationName({$queryParameters}) { __typename {$operationResult::toFragment()} } }";

        return GraphQLFormatter::formatQuery($query);
    }

    /**
     * builds the GraphQL operation parameters definition.
     */
    protected static function toQueryParametersDefinition(): string
    {
        $params = static::$operationParameters;

        if (empty($params)) {
            return '';
        }

        return '(' . array_reduce(
            array_keys($params),
            function ($accumulator, $key) use ($params) {
                $prefix    = $accumulator != '' ? ', ' : '';
                $type      = $params[$key];
                $inputType = is_array($type) ? $type['inputType'] : $type;

                return "$accumulator$prefix$$key: $inputType";
            },
            ''
        ) . ')';
    }

    /**
     * builds the GraphQL operation variable injections.
     */
    protected static function toQueryParameters(): string
    {
        $params = static::$operationParameters;

        return GraphQLFormatter::formatQueryParameters(array_reduce(
            array_keys($params),
            function ($accumulator, $key) {
                $prefix = $accumulator != '' ? ', ' : '';

                return "$accumulator$prefix$key: $$key";
            },
            ''
        ));
    }

    /**
     * Builds the variables to be passed to the body of the
     * GraphQL operation.
     *
     * @return array<string, mixed>
     */
    public function toQueryVariables(): array
    {
        $args = $this->queryVars;

        return array_reduce(
            array_keys($args),
            function ($accumulator, $key) use ($args) {
                $input             = $args[$key];
                $accumulator[$key] = $input instanceof AbstractInputType ? $input->toArray() : $input;

                return $accumulator;
            },
            []
        );
    }

    /**
     * Parses a GraphQL query result and casts it
     * to the underlying result class which extends AbstractObjectType.
     *
     * @param string $result
     *
     * @throws GraphQLFailureException
     * @throws GraphQLException
     */
    public function parseResult($result): GraphQLObjectInterface
    {
        $operationName = static::$operationName;
        $query_name    = $this->getOperationName();

        /** @var object */
        $json = json_decode($result);

        if (isset($json->errors)) {
            throw new GraphQLFailureException($json->errors[0]->message);
        }

        if (isset($json->data) && isset($json->data->$query_name)) {
            try {
                return static::$operationResult::fromJson($json->data->$query_name);
            } catch (Exception $e) {
                throw new GraphQLException("Unable to parse result for operation $operationName ({$e->getMessage()})");
            }
        }

        throw new GraphQLException("Unable to parse result for operation $operationName");
    }

    /**
     * Sets the API key variable for the current operation.
     */
    public function setApiKey(string $apiKey): GraphQLOperationInterface
    {
        $this->setQueryVar('apiKey', $apiKey);

        return $this;
    }

    /**
     * Sets a queryVar key/value pair.
     *
     * @param mixed $value
     *
     * @return self
     */
    public function setQueryVar(string $key, $value)
    {
        $this->queryVars[$key] = $value;

        return $this;
    }
}
