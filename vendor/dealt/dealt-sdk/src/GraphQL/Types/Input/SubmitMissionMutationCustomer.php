<?php

namespace Dealt\DealtSDK\GraphQL\Types\Input;

/**
 * @property string $firstName
 * @property string $lastName
 * @property string $emailAddress
 * @property string $phoneNumber
 * @property string $customerProductPrice
 * @property string $customerServicePrice
 */
class SubmitMissionMutationCustomer extends AbstractCustomer
{
    public static $inputName = 'SubmitMissionMutation_Customer';
}
