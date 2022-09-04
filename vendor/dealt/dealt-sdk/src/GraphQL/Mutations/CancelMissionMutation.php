<?php

namespace Dealt\DealtSDK\GraphQL\Mutations;

use Dealt\DealtSDK\GraphQL\GraphQLMutation;
use Dealt\DealtSDK\GraphQL\Types\Object\CancelMissionMutationResult;

class CancelMissionMutation extends GraphQLMutation
{
    public static $operationName       = 'cancelMission';
    public static $operationParameters = [
        'apiKey'    => 'String!',
        'missionId' => 'UUID!',
    ];

    public static $operationResult = CancelMissionMutationResult::class;
}
