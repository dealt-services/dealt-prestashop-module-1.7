<?php

use Dealt\DealtSDK\DealtClient;
use Dealt\DealtSDK\DealtEnvironment;
use Dealt\DealtSDK\Exceptions\GraphQLFailureException;
use Dealt\DealtSDK\Exceptions\InvalidArgumentException;
use Dealt\DealtSDK\GraphQL\GraphQLClient;
use Dealt\DealtSDK\GraphQL\Types\Object\Money;
use Dealt\DealtSDK\GraphQL\Types\Object\OfferAvailabilityQuerySuccess;
use Dealt\DealtSDK\Services\DealtOffers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DealtOffersTest extends TestCase
{
    protected $client;
    protected $graphQLClientStub;

    public function __construct()
    {
        parent::__construct();
        $this->client = new DealtClient([
            'api_key' => 'test-api-key',
            'env'     => DealtEnvironment::$TEST,
        ]);

        /*  @var MockObject */
        $this->graphQLClientStub         = $this->createPartialMock(GraphQLClient::class, ['request']);
        $this->graphQLClientStub->apiKey = 'test-api-key';
        $this->client->gqlClient         = $this->graphQLClientStub;
    }

    public function testResolvesOfferAvailability()
    {
        $service  = new DealtOffers($this->client);
        $response = strval(json_encode([
            'data' => [
                'offerAvailability' => [
                    '__typename' => 'OfferAvailabilityQuery_Success',
                    'available'  => true,
                    'netPrice'   => [
                        'amount'       => 70,
                        'currencyCode' => 'EUR',
                    ],
                    'grossPrice' => [
                        'amount'       => 84,
                        'currencyCode' => 'EUR',
                    ],
                    'vat' => [
                        'amount'       => 14,
                        'currencyCode' => 'EUR',
                    ],
                ],
            ],
        ]));

        $this->graphQLClientStub->expects($this->once())->method('request')->willReturn($response);

        $result  = $service->availability([
            'offer_id' => 'offer-uuid-0001',
            'address'  => [
                'country'  => 'France',
                'zip_code' => '92190',
            ],
        ]);

        $this->assertInstanceOf(OfferAvailabilityQuerySuccess::class, $result);

        $this->assertInstanceOf(Money::class, $result->gross_price);
        $this->assertEquals(84, $result->gross_price->amount);
        $this->assertEquals('EUR', $result->gross_price->currency_code);

        $this->assertInstanceOf(Money::class, $result->net_price);
        $this->assertEquals(70, $result->net_price->amount);
        $this->assertEquals('EUR', $result->net_price->currency_code);

        $this->assertInstanceOf(Money::class, $result->vat_price);
        $this->assertEquals(14, $result->vat_price->amount);
        $this->assertEquals('EUR', $result->vat_price->currency_code);
    }

    public function testThrowsOnAvailabilityFailure()
    {
        $this->expectException(GraphQLFailureException::class);

        $service  = new DealtOffers($this->client);
        $response = strval(json_encode([
            'data' => [
                'offerAvailability' => [
                    '__typename' => 'OfferAvailabilityQuery_Failure',
                    'reason'     => 'OFFER_NOT_FOUND',
                ],
            ],
        ]));

        $this->graphQLClientStub->expects($this->once())->method('request')->willReturn($response);

        $service->availability([
            'offer_id' => 'offer-uuid-0001',
            'address'  => [
                'country'  => 'France',
                'zip_code' => '92190',
            ],
        ]);
    }

    public function testShouldThrowWhenOfferAvailabilityProvidedInconsistentParams()
    {
        $this->expectException(InvalidArgumentException::class);

        $service = new DealtOffers($this->client);
        $service->availability([]);
    }
}
