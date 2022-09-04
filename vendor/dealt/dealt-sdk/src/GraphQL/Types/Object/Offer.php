<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

/**
 * @property string $id
 * @property string $name
 *
 * @method Offer fromJson()
 */
class Offer extends AbstractObjectType
{
    public static $objectName = 'Mission';

    public static $objectDefinition = [
        'id'   => 'ID!',
        'name' => 'String!',
    ];
}
