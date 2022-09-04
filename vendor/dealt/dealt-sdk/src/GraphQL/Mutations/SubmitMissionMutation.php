<?php

namespace Dealt\DealtSDK\GraphQL\Mutations;

use Dealt\DealtSDK\GraphQL\GraphQLMutation;
use Dealt\DealtSDK\GraphQL\Types\Input\SubmitMissionMutationAddress;
use Dealt\DealtSDK\GraphQL\Types\Input\SubmitMissionMutationCustomer;
use Dealt\DealtSDK\GraphQL\Types\Object\SubmitMissionMutationResult;

class SubmitMissionMutation extends GraphQLMutation
{
    public static $operationName       = 'submitMission';
    public static $operationParameters = [
        'apiKey'  => 'String!',
        'offerId' => 'UUID!',
        'address' => [
            'inputType'  => 'SubmitMissionMutation_Address!',
            'inputClass' => SubmitMissionMutationAddress::class,
        ],
        'customer' => [
            'inputType'  => 'SubmitMissionMutation_Customer!',
            'inputClass' => SubmitMissionMutationCustomer::class,
        ],
        'webHookUrl'   => 'String',
        'extraDetails' => 'String',
    ];

    public static $operationResult = SubmitMissionMutationResult::class;
}
