<?php

namespace Dealt\DealtSDK\GraphQL\Queries;

use Dealt\DealtSDK\GraphQL\GraphQLQuery;
use Dealt\DealtSDK\GraphQL\Types\Input\OfferAvailabilityQueryAddress;
use Dealt\DealtSDK\GraphQL\Types\Object\OfferAvailabilityQueryResult;

class OfferAvailabilityQuery extends GraphQLQuery
{
    public static $operationName       = 'offerAvailability';
    public static $operationParameters = [
        'apiKey'  => 'String!',
        'offerId' => 'UUID!',
        'address' => [
            'inputType'  => 'OfferAvailabilityQuery_Address!',
            'inputClass' => OfferAvailabilityQueryAddress::class,
        ],
    ];
    public static $operationResult = OfferAvailabilityQueryResult::class;
}
