<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

class MissionQueryResult extends AbstractUnionType
{
    public static $unionName       = 'MissionQuery_Result';
    public static $unionDefinition = [
        MissionQuerySuccess::class,
        MissionQueryFailure::class,
    ];
}
