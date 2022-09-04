<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

class OfferAvailabilityQueryResult extends AbstractUnionType
{
    public static $unionName       = 'OfferAvailabilityQuery_Result';
    public static $unionDefinition = [
        OfferAvailabilityQuerySuccess::class,
        OfferAvailabilityQueryFailure::class,
    ];
}
