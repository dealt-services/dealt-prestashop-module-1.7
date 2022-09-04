<?php

namespace Dealt\DealtSDK\GraphQL\Queries;

use Dealt\DealtSDK\GraphQL\GraphQLQuery;
use Dealt\DealtSDK\GraphQL\Types\Object\MissionsQueryResult;

class MissionsQuery extends GraphQLQuery
{
    public static $operationName       = 'missions';
    public static $operationParameters = [
        'apiKey'  => 'String!',
    ];
    public static $operationResult = MissionsQueryResult::class;
}
