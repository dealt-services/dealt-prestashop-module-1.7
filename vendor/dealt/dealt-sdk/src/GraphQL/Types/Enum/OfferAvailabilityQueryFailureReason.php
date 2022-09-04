<?php

namespace Dealt\DealtSDK\GraphQL\Types\Enum;

class OfferAvailabilityQueryFailureReason
{
    /** @var string */
    public static $INVALID_API_KEY       = 'INVALID_API_KEY';
    /** @var string */
    public static $INVALID_CONFIGURATION = 'INVALID_CONFIGURATION';
    /** @var string */
    public static $OFFER_NOT_FOUND       = 'OFFER_NOT_FOUND';
    /** @var string */
    public static $INVALID_ADDRESS       = 'INVALID_ADDRESS';
}
