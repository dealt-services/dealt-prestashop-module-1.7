<?php

namespace Dealt\DealtSDK\GraphQL\Types\Object;

/**
 * @property string $currency_code
 * @property float  $amount
 *
 * @method Money fromJson()
 */
class Money extends AbstractObjectType
{
    public static $objectName = 'Money';

    public static $objectDefinition = [
        'currencyCode' => [
            'objectType' => 'String!',
            'proxy'      => 'currency_code',
        ],
        'amount'       => 'Float!',
    ];
}
