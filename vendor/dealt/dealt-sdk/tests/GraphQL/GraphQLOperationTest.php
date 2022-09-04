<?php

use Dealt\DealtSDK\GraphQL\Mutations\CancelMissionMutation;
use Dealt\DealtSDK\GraphQL\Mutations\SubmitMissionMutation;
use Dealt\DealtSDK\GraphQL\Queries\MissionQuery;
use Dealt\DealtSDK\GraphQL\Queries\MissionsQuery;
use Dealt\DealtSDK\GraphQL\Queries\OfferAvailabilityQuery;
use Dealt\DealtSDK\Utils\GraphQLFormatter;
use PHPUnit\Framework\TestCase;

final class GraphQLOperationTest extends TestCase
{
    public function testMissionQueryFragment()
    {
        $query = <<<'GRAPHQL'
            query mission($apiKey: String!, $missionId: UUID!) {
                mission(apiKey: $apiKey, missionId: $missionId) {
                    __typename
                    ... on MissionQuery_Success {
                        __typename
                        mission {
                            id
                            offer {
                                id
                                name
                            }
                            status
                            createdAt
                        }
                    }
                    ... on MissionQuery_Failure {
                        __typename
                        reason
                    }
                }
            }
GRAPHQL;

        $this->assertEquals(GraphQLFormatter::formatQuery($query), MissionQuery::toQuery());
    }

    public function testMissionsQueryFragment()
    {
        $query = <<<'GRAPHQL'
            query missions($apiKey: String!) {
                missions(apiKey: $apiKey) {
                    __typename
                    ... on MissionsQuery_Success {
                        __typename
                        missions {
                            id
                            offer {
                                id
                                name
                            }
                            status
                            createdAt
                        }
                    }
                    ... on MissionsQuery_Failure {
                        __typename
                        reason
                    }
                }
            }
GRAPHQL;

        $this->assertEquals(GraphQLFormatter::formatQuery($query), MissionsQuery::toQuery());
    }

    public function testOfferAvailabilityQueryFragment()
    {
        $query = <<<'GRAPHQL'
            query offerAvailability($apiKey: String!, $offerId: UUID!, $address: OfferAvailabilityQuery_Address!) {
                offerAvailability(apiKey: $apiKey, offerId: $offerId, address: $address) {
                    __typename
                    ... on OfferAvailabilityQuery_Success {
                        __typename
                        available
                        netPrice {
                            currencyCode
                            amount
                        }
                        grossPrice {
                            currencyCode
                            amount
                        }
                        vat {
                            currencyCode
                            amount
                        }
                    }
                    ... on OfferAvailabilityQuery_Failure {
                        __typename
                        reason
                    }
                }
            }
GRAPHQL;

        $this->assertEquals(GraphQLFormatter::formatQuery($query), OfferAvailabilityQuery::toQuery());
    }

    public function testSubmitMissionMutationFragment()
    {
        $mutation = <<<'GRAPHQL'
            mutation submitMission($apiKey: String!, $offerId: UUID!, $address: SubmitMissionMutation_Address!, $customer: SubmitMissionMutation_Customer!, $webHookUrl: String, $extraDetails: String) {
                submitMission(apiKey: $apiKey, offerId: $offerId, address: $address, customer: $customer, webHookUrl: $webHookUrl, extraDetails: $extraDetails) {
                    __typename
                    ... on SubmitMissionMutation_Success {
                        __typename
                        mission {
                            id
                            offer {
                                id
                                name
                            }
                            status
                            createdAt
                        }
                    }
                    ... on SubmitMissionMutation_Failure {
                        __typename
                        reason
                    }
                }
            }
GRAPHQL;

        $this->assertEquals(GraphQLFormatter::formatQuery($mutation), GraphQLFormatter::formatQuery(SubmitMissionMutation::toQuery()));
    }

    public function testCancelMissionMutationFragment()
    {
        $mutation = <<<'GRAPHQL'
            mutation cancelMission($apiKey: String!, $missionId: UUID!) {
                cancelMission(apiKey: $apiKey, missionId: $missionId) {
                    __typename
                    ... on CancelMissionMutation_Success {
                        __typename
                        mission {
                            id
                            offer {
                                id
                                name
                            }
                            status
                            createdAt
                        }
                    }
                    ... on CancelMissionMutation_Failure {
                        __typename
                        reason
                    }
                }
            }
GRAPHQL;

        $this->assertEquals(GraphQLFormatter::formatQuery($mutation), GraphQLFormatter::formatQuery(CancelMissionMutation::toQuery()));
    }
}
