<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

/**
 * @property bool  $available
 * @property Money $net_price
 * @property Money $gross_price
 * @property Money $vat_price
 *
 * @method OfferAvailabilityQuerySuccess fromJson()
 */
class OfferAvailabilityQuerySuccess extends AbstractObjectType
{
    public static $objectName       = 'OfferAvailabilityQuery_Success';
    public static $objectDefinition =  [
        'available' => 'Boolean!',
        'netPrice'  => [
            'objectType'  => 'Money!',
            'objectClass' => Money::class,
            'proxy'       => 'net_price',
        ],
        'grossPrice' => [
            'objectType'  => 'Money!',
            'objectClass' => Money::class,
            'proxy'       => 'gross_price',
        ],
        'vat' => [
            'objectType'  => 'Money!',
            'objectClass' => Money::class,
            'proxy'       => 'vat_price',
        ],
    ];
}
