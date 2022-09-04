<?php
declare(strict_types=1);

namespace Dealt\Module\Dealtmodule\Api;

class DealtAPIAction
{
    public static $OFFER_AVAILABILITY = 'offerAvailability';
    public static $MISSION_WEBHOOK = 'missionWebhook';

    public static function cases()
    {
        return [
            static::$OFFER_AVAILABILITY,
            static::$MISSION_WEBHOOK,
        ];
    }
}
