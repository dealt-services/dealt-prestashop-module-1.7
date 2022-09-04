<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

/**
 * @property Mission $mission
 *
 * @method CancelMissionMutationSuccess fromJson()
 */
class CancelMissionMutationSuccess extends AbstractObjectType
{
    public static $objectName       = 'CancelMissionMutation_Success';
    public static $objectDefinition =  [
        'mission'  => [
            'objectType'  => 'Mission!',
            'objectClass' => Mission::class,
        ],
    ];
}
