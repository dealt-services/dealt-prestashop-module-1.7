<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

use Dealt\DealtSDK\GraphQL\Types\Enum\MissionStatus;

/**
 * @property string $id
 * @property Offer  $offer
 * @property string $created_at
 * @property string $status
 *
 * @method Mission fromJson()
 */
class Mission extends AbstractObjectType
{
    public static $objectName = 'Mission';

    public static $objectDefinition = [
        'id'     => 'ID!',
        'offer'  => [
            'objectType'  => 'Offer!',
            'objectClass' => Offer::class,
        ],
        'status' => [
            'objectType'  => 'Offer!',
            'objectClass' => MissionStatus::class,
            'isEnum'      => true,
        ],
        'createdAt' => [
            'objectType' => 'DateTime!',
            'proxy'      => 'created_at',
        ],
    ];
}
