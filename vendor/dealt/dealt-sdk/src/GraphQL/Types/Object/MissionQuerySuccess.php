<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

/**
 * @property Mission $mission
 *
 * @method MissionQuerySuccess fromJson()
 */
class MissionQuerySuccess extends AbstractObjectType
{
    public static $objectName       = 'MissionQuery_Success';
    public static $objectDefinition =  [
        'mission'  => [
            'objectType'  => 'Mission',
            'objectClass' => Mission::class,
        ],
    ];
}
