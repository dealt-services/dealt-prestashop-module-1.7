<?php

namespace Dealt\DealtSDK\GraphQL;

interface GraphQLObjectInterface
{
    /**
     * Sets a key/value pair on the object.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setProperty($key, $value): GraphQLObjectInterface;

    /**
     * static response parser from JSON object.
     *
     * @param mixed $json
     */
    public static function fromJson($json): GraphQLObjectInterface;

    /**
     * Builds the GraphQL sub-fragment when building the
     * final GraphQL operation.
     */
    public static function toFragment(): string;
}
