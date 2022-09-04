<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

/**
 * @property Mission[] $missions
 *
 * @method MissionsQuerySuccess fromJson()
 */
class MissionsQuerySuccess extends AbstractObjectType
{
    public static $objectName       = 'MissionsQuery_Success';
    public static $objectDefinition =  [
        'missions'  => [
            'objectType'  => '[Mission!]!',
            'objectClass' => Mission::class,
            'isArray'     => true,
        ],
    ];
}
