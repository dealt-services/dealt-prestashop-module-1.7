<?php

namespace Dealt\DealtSDK\GraphQL\Types\Input;

abstract class AbstractCustomer extends AbstractInputType
{
    public static $inputDefinition = [
        'firstName'             => 'String!',
        'lastName'              => 'String!',
        'emailAddress'          => 'EmailAddress!',
        'phoneNumber'           => 'PhoneNumber!',
        'customerProductPrice'  => 'Float',
        'customerServicePrice'  => 'Float',
    ];
}
