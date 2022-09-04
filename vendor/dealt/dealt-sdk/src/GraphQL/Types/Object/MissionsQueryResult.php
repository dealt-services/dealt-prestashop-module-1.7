<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

class MissionsQueryResult extends AbstractUnionType
{
    public static $unionName       = 'MissionsQuery_Result';
    public static $unionDefinition = [
        MissionsQuerySuccess::class,
        MissionsQueryFailure::class,
    ];
}
