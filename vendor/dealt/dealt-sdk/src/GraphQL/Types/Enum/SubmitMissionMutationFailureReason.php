<?php

namespace Dealt\DealtSDK\GraphQL\Types\Enum;

class SubmitMissionMutationFailureReason
{
    /** @var string */
    public static $INVALID_API_KEY       = 'INVALID_API_KEY';
    /** @var string */
    public static $INVALID_ADDRESS       = 'INVALID_ADDRESS';
    /** @var string */
    public static $OFFER_NOT_FOUND       = 'OFFER_NOT_FOUND';
    /** @var string */
    public static $EXPERT_NOT_FOUND      = 'EXPERT_NOT_FOUND';
}
