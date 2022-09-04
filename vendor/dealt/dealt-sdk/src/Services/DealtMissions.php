<?php

namespace Dealt\DealtSDK\Services;

use Dealt\DealtSDK\Exceptions\GraphQLFailureException;
use Dealt\DealtSDK\GraphQL\Mutations\CancelMissionMutation;
use Dealt\DealtSDK\GraphQL\Mutations\SubmitMissionMutation;
use Dealt\DealtSDK\GraphQL\Queries\MissionQuery;
use Dealt\DealtSDK\GraphQL\Queries\MissionsQuery;
use Dealt\DealtSDK\GraphQL\Types\Input\SubmitMissionMutationAddress;
use Dealt\DealtSDK\GraphQL\Types\Input\SubmitMissionMutationCustomer;
use Dealt\DealtSDK\GraphQL\Types\Object\CancelMissionMutationFailure;
use Dealt\DealtSDK\GraphQL\Types\Object\CancelMissionMutationSuccess;
use Dealt\DealtSDK\GraphQL\Types\Object\MissionQueryFailure;
use Dealt\DealtSDK\GraphQL\Types\Object\MissionQuerySuccess;
use Dealt\DealtSDK\GraphQL\Types\Object\MissionsQueryFailure;
use Dealt\DealtSDK\GraphQL\Types\Object\MissionsQuerySuccess;
use Dealt\DealtSDK\GraphQL\Types\Object\SubmitMissionMutationFailure;
use Dealt\DealtSDK\GraphQL\Types\Object\SubmitMissionMutationSuccess;

/**
 * DealtMissions Service :
 * Handles querying missions, submitting / canceling missions.
 */
class DealtMissions extends AbstractDealtService
{
    /**
     * Retrieves a mission by its mission_id.
     *
     * @throws GraphQLFailureException
     */
    public function get(string $mission_id): MissionQuerySuccess
    {
        $query = new MissionQuery();
        self::validateParameters(['mission_id' => $mission_id], $query);

        $query->setQueryVar('missionId', $mission_id);

        /** @var MissionQuerySuccess|MissionQueryFailure */
        $result = $this->getGQLClient()->exec($query);

        if ($result instanceof MissionQueryFailure) {
            throw new GraphQLFailureException($result->reason);
        }

        return $result;
    }

    /**
     * Retrieves all missions.
     *
     * @throws GraphQLFailureException
     */
    public function all(): MissionsQuerySuccess
    {
        $query = new MissionsQuery();

        /** @var MissionsQuerySuccess|MissionsQueryFailure */
        $result = $this->getGQLClient()->exec($query);

        if ($result instanceof MissionsQueryFailure) {
            throw new GraphQLFailureException($result->reason);
        }

        return $result;
    }

    /**
     * Posts a mission to the Dealt API.
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
     *      },
     *      customer: array{
     *          first_name: string,
     *          last_name: string,
     *          email_address: string,
     *          phone_number: string
     *      },
     *      webhook?: string
     * } $params SubmitMissionMutation parameters
     *
     * @throws GraphQLFailureException
     */
    public function submit(array $params): SubmitMissionMutationSuccess
    {
        $mutation = new SubmitMissionMutation();
        self::validateParameters($params, $mutation);

        $mutation->setQueryVar('offerId', $params['offer_id']);
        $mutation->setQueryVar('address', SubmitMissionMutationAddress::fromArray($params['address']));
        $mutation->setQueryVar('customer', SubmitMissionMutationCustomer::fromArray($params['customer']));

        if (isset($params['webhook'])) {
            $mutation->setQueryVar('webhook', $params['webhook']);
        }

        /** @var SubmitMissionMutationSuccess|SubmitMissionMutationFailure */
        $result = $this->getGQLClient()->exec($mutation);

        if ($result instanceof SubmitMissionMutationFailure) {
            throw new GraphQLFailureException($result->reason);
        }

        return $result;
    }

    /**
     * Cancels a mission by its mission_id.
     *
     * @throws GraphQLFailureException
     */
    public function cancel(string $mission_id): CancelMissionMutationSuccess
    {
        $mutation = new CancelMissionMutation();
        $mutation->setQueryVar('missionId', $mission_id);

        /** @var CancelMissionMutationSuccess|CancelMissionMutationFailure */
        $result = $this->getGQLClient()->exec($mutation);

        if ($result instanceof CancelMissionMutationFailure) {
            throw new GraphQLFailureException($result->reason);
        }

        return $result;
    }
}
