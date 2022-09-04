<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

/**
 * @property Mission $mission
 *
 * @method SubmitMissionMutationSuccess fromJson()
 */
class SubmitMissionMutationSuccess extends AbstractObjectType
{
    public static $objectName       = 'SubmitMissionMutation_Success';
    public static $objectDefinition =  [
        'mission'  => [
            'objectType'  => 'Mission!',
            'objectClass' => Mission::class,
        ],
    ];
}
