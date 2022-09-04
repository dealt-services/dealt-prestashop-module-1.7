<?php

namespace Dealt\DealtSDK\GraphQL\Types\Enum;

class CancelMissionMutationFailureReason
{
    /** @var string */
    public static $INVALID_API_KEY        = 'INVALID_API_KEY';
    /** @var string */
    public static $MISSION_NOT_FOUND      = 'MISSION_NOT_FOUND';
    /** @var string */
    public static $INVALID_MISSION_STATUS = 'INVALID_MISSION_STATUS';
}
