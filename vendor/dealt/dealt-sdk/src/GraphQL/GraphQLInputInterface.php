<?php

namespace Dealt\DealtSDK\GraphQL;

use Dealt\DealtSDK\GraphQL\Types\Input\AbstractInputType;

interface GraphQLInputInterface
{
    /**
     * Sets a key/value pair on the input object.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setProperty($key, $value): AbstractInputType;

    /**
     * Returns the input object as a dictionary array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Parses the input type from a dictionary array.
     *
     * @param mixed $array
     */
    public static function fromArray($array): AbstractInputType;
}
