<?php

namespace Dealt\DealtSDK\GraphQL\Types\Input;

use Dealt\DealtSDK\Exceptions\GraphQLInvalidParametersException;
use Dealt\DealtSDK\Exceptions\InvalidArgumentException;
use Dealt\DealtSDK\GraphQL\GraphQLInputInterface;
use Dealt\DealtSDK\Utils\GraphQLFormatter;

/**
 * Generic GraphQL input type class.
 *
 * @todo handle nested input types
 */
abstract class AbstractInputType implements GraphQLInputInterface
{
    /** @var string */
    public static $inputName;

    /** @var array<string, mixed> */
    public static $inputDefinition;

    public function toArray(): array
    {
        $paramKeys = array_keys(static::$inputDefinition);
        $arr       = [];

        foreach ($paramKeys as $param) {
            if (isset($this->$param)) {
                $arr[$param] = $this->$param;
            }
        }

        return $arr;
    }

    public function setProperty($key, $value): self
    {
        $paramKeys = array_keys(static::$inputDefinition);
        $inputName = static::$inputName;
        if (!in_array($key, $paramKeys)) {
            throw new GraphQLInvalidParametersException("Trying to set unallowed parameter $key on {$inputName}");
        }

        $this->$key = $value;

        return $this;
    }

    public static function fromArray($array): AbstractInputType
    {
        if (!is_array($array)) {
            throw new InvalidArgumentException();
        }

        $inputClass = static::class;
        $class      = new $inputClass();

        foreach ($array as $param => $value) {
            $class->setProperty(GraphQLFormatter::snakeToCamelCase($param), $value);
        }

        return $class;
    }
}
