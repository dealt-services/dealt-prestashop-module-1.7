<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

use Dealt\DealtSDK\GraphQL\Types\Enum\MissionsQueryFailureReason;

/**
 * @property string $reason
 *
 * @method MissionsQueryFailure fromJson()
 */
class MissionsQueryFailure extends AbstractObjectType
{
    public static $objectName       = 'MissionsQuery_Failure';
    public static $objectDefinition =  [
        'reason'  => [
            'objectType'  => 'MissionsQuery_FailureReason!',
            'objectClass' => MissionsQueryFailureReason::class,
            'isEnum'      => true,
        ],
    ];
}
