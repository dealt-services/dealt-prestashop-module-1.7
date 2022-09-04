<?php

namespace Dealt\DealtSDK\GraphQL\Types\Input;

/**
 * @property string $country
 * @property string $zipCode
 * @property string $city
 * @property string $street1
 * @property string $street2
 * @property string $company
 */
class OfferAvailabilityQueryAddress extends AbstractAddress
{
    public static $inputName = 'OfferAvailabilityQuery_Address';
}
