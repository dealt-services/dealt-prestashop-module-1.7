<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

use Dealt\DealtSDK\GraphQL\Types\Enum\MissionQueryFailureReason;

/**
 * @property string $reason
 *
 * @method MissionQueryFailure fromJson()
 */
class MissionQueryFailure extends AbstractObjectType
{
    public static $objectName       = 'MissionQuery_Failure';
    public static $objectDefinition =  [
        'reason'  => [
            'objectType'  => 'MissionQuery_FailureReason!',
            'objectClass' => MissionQueryFailureReason::class,
            'isEnum'      => true,
        ],
    ];
}
