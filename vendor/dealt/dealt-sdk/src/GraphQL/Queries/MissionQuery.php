<?php

namespace Dealt\DealtSDK\GraphQL\Queries;

use Dealt\DealtSDK\GraphQL\GraphQLQuery;
use Dealt\DealtSDK\GraphQL\Types\Object\MissionQueryResult;

class MissionQuery extends GraphQLQuery
{
    public static $operationName       = 'mission';
    public static $operationParameters = [
        'apiKey'    => 'String!',
        'missionId' => 'UUID!',
    ];
    public static $operationResult = MissionQueryResult::class;
}
