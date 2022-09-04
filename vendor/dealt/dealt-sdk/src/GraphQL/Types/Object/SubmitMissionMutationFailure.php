<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

use Dealt\DealtSDK\GraphQL\Types\Enum\SubmitMissionMutationFailureReason;

/**
 * @property string $reason
 *
 * @method SubmitMissionMutationFailure fromJson()
 */
class SubmitMissionMutationFailure extends AbstractObjectType
{
    public static $objectName       = 'SubmitMissionMutation_Failure';
    public static $objectDefinition =  [
        'reason'  => [
            'objectType'  => 'SubmitMissionMutation_FailureReason!',
            'objectClass' => SubmitMissionMutationFailureReason::class,
            'isEnum'      => true,
        ],
    ];
}
