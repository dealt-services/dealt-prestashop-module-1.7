<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

use Dealt\DealtSDK\GraphQL\Types\Enum\CancelMissionMutationFailureReason;

/**
 * @property string $reason
 *
 * @method CancelMissionMutationFailure fromJson()
 */
class CancelMissionMutationFailure extends AbstractObjectType
{
    public static $objectName       = 'CancelMissionMutation_Failure';
    public static $objectDefinition =  [
        'reason'  => [
            'objectType'  => 'CancelMissionMutation_FailureReason!',
            'objectClass' => CancelMissionMutationFailureReason::class,
            'isEnum'      => true,
        ],
    ];
}
