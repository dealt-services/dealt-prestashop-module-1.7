<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

class CancelMissionMutationResult extends AbstractUnionType
{
    public static $unionName       = 'CancelMissionMutation_Result';
    public static $unionDefinition = [
        CancelMissionMutationSuccess::class,
        CancelMissionMutationFailure::class,
    ];
}
