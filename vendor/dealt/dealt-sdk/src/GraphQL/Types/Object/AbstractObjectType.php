<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

use Dealt\DealtSDK\GraphQL\GraphQLObjectInterface;

abstract class AbstractObjectType implements GraphQLObjectInterface
{
    /** @var string */
    public static $objectName;

    /** @var array<string, string|array{ proxy?: string, isEnum?: bool, isArray?: bool, objectType: string, objectClass?: string }> */
    public static $objectDefinition;

    public static function toFragment(): string
    {
        $definitions = static::$objectDefinition;
        $fragments   = [];

        foreach ($definitions as $key => $definition) {
            if (is_array($definition) && isset($definition['objectClass']) && (!isset($definition['isEnum']) || $definition['isEnum'] !== true)) {
                /** @var AbstractObjectType|string */
                $subType = $definition['objectClass'];
                array_push($fragments, "$key { {$subType::toFragment()} }");
                continue;
            }

            array_push($fragments, $key);
        }

        return join(' ', $fragments);
    }

    public function setProperty($key, $value): GraphQLObjectInterface
    {
        /** @var array<string, array{ proxy?: string }> */
        $definitions = static::$objectDefinition;

        if (in_array($key, array_keys($definitions)) || array_reduce($definitions, function ($hasProxyKey, $definition) use ($key) {
            return $hasProxyKey || (isset($definition['proxy']) && $definition['proxy'] == $key);
        }, false)) {
            $this->$key = $value;
        }

        return $this;
    }

    public static function fromJson($json): GraphQLObjectInterface
    {
        $objectClass = static::class;
        /** @var array<string, string|array{ proxy?: string, isEnum?: bool, isArray?: bool, objectClass?: string }> */
        $definitions = static::$objectDefinition;

        /** @var GraphQLObjectInterface */
        $class      = new $objectClass();

        foreach ($definitions as $key => $definition) {
            if (!isset($json->$key)) {
                continue;
            }

            /** @var string */
            $_key = is_array($definition) && isset($definition['proxy']) ? $definition['proxy'] : $key;

            if (is_array($definition) && isset($definition['objectClass'])) {
                $subObjectClass = $definition['objectClass'];

                // enum parsing
                if (isset($definition['isEnum']) && $definition['isEnum'] === true) {
                    $staticKey = $json->$key;
                    $class->setProperty($_key, $subObjectClass::$$staticKey);
                    continue;
                }

                /** @var AbstractObjectType */
                $subClass = new $subObjectClass();

                // nested array response parsing
                if (isset($definition['isArray']) && $definition['isArray'] === true && is_array($json->$key)) {
                    $subObjectArray = $json->$key;

                    $class->setProperty($_key, array_map(function ($obj) use ($subClass) {
                        return $subClass->fromJson($obj);
                    }, $subObjectArray));
                } else {
                    // top-level object parsing
                    $class->setProperty($_key, $subClass->fromJson($json->$key));
                }
            } else {
                $class->setProperty($_key, $json->$key);
            }
        }

        return $class;
    }

    public function serialize(): string
    {
        /** @var array<string, string|array{ proxy?: string }> */
        $definitions = static::$objectDefinition;

        $keys = array_map(function ($key, $definition) {
            if (is_array($definition) && isset($definition['proxy'])) {
                return $definition['proxy'];
            }

            return $key;
        }, array_keys($definitions), $definitions);

        $obj = [];

        foreach ($keys as $key) {
            if (isset($this->$key)) {
                $obj[$key] = $this->$key;
            }
        }

        return strval(json_encode($obj));
    }
}
