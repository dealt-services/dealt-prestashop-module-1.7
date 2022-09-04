<?php

namespace Dealt\DealtSDK\Services;

use Dealt\DealtSDK\Exceptions\GraphQLException;
use Dealt\DealtSDK\Exceptions\GraphQLFailureException;
use Dealt\DealtSDK\GraphQL\Queries\OfferAvailabilityQuery;
use Dealt\DealtSDK\GraphQL\Types\Input\OfferAvailabilityQueryAddress;
use Dealt\DealtSDK\GraphQL\Types\Object\OfferAvailabilityQueryFailure;
use Dealt\DealtSDK\GraphQL\Types\Object\OfferAvailabilityQuerySuccess;

/**
 * DealtOffers Service :
 * Allows checking availability for an offer.
 */
class DealtOffers extends AbstractDealtService
{
    /**
     * Resolve the availability for a given offer id and
     * a customer address (at least a country and a zipCode).
     *
     * @param array{
     *      offer_id: string,
     *      address: array{
     *          country: string,
     *          zip_code: string,
     *          city?: string,
     *          street1?: string,
     *          street2?: string,
     *          company?: string
     *      }
     * } $params OfferAvailabilityQuery parameters
     *
     * @throws GraphQLFailureException|GraphQLException
     */
    public function availability(array $params): OfferAvailabilityQuerySuccess
    {
        $query = new OfferAvailabilityQuery();
        self::validateParameters($params, $query);

        $query = new OfferAvailabilityQuery();
        $query->setQueryVar('offerId', $params['offer_id']);
        $query->setQueryVar('address', OfferAvailabilityQueryAddress::fromArray($params['address']));

        /** @var OfferAvailabilityQuerySuccess|OfferAvailabilityQueryFailure */
        $result = $this->getGQLClient()->exec($query);

        if ($result instanceof OfferAvailabilityQueryFailure) {
            throw new GraphQLFailureException($result->reason);
        }

        return $result;
    }
}
