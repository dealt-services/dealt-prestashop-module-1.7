<?php

namespace Dealt\DealtSDK\GraphQL\Types\Input;

abstract class AbstractAddress extends AbstractInputType
{
    public static $inputDefinition = [
        'country'       => 'String!',
        'zipCode'       => 'String!',
        'city'          => 'String',
        'street1'       => 'String',
        'street2'       => 'String',
        'complementary' => 'String',
        'company'       => 'String',
    ];
}
