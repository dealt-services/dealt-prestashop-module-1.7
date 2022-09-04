<?php

use Dealt\DealtSDK\DealtClient;
use Dealt\DealtSDK\DealtEnvironment;
use Dealt\DealtSDK\GraphQL\Types\Enum\MissionStatus;
use PHPUnit\Framework\TestCase;

final class EndToEndTest extends TestCase
{
    protected $client;

    public function __construct()
    {
        $this->client = new DealtClient([
            'api_key' => getenv('DEALT_TEST_API_KEY'),
            'env'     => DealtEnvironment::$TEST,
        ]);

        parent::__construct();
    }

    public function testChecksOfferAvailability()
    {
        $result = $this->client->offers->availability([
            'offer_id' => getenv('DEALT_TEST_OFFER_ID'),
            'address'  => [
                'country'  => 'France',
                'zip_code' => '92190',
                'street1'  => 'Test'
            ],
        ]);

        $this->assertEquals(true, $result->available);
    }

    public function testSubmitsMissionSuccessfully(): string
    {
        $result = $this->client->missions->submit([
            'offer_id' => getenv('DEALT_TEST_OFFER_ID'),
            'address'  => [
                'country'  => 'France',
                'zip_code' => '92190',
                'street1'  => 'Test'
            ],
            'customer' => [
                'first_name'    => 'Jean',
                'last_name'     => 'Test',
                'email_address' => 'no-exist@noexist.noexist',
                'phone_number'  => '+33600000000',
            ],
        ]);

        $this->assertEquals(getenv('DEALT_TEST_OFFER_ID'), $result->mission->offer->id);

        /* return for next test */
        return $result->mission->id;
    }

    /**
     * @param string $missionId
     * @depends testSubmitsMissionSuccessfully
     */
    public function testGetMissionByIdSuccessfully($missionId): string
    {
        $result = $this->client->missions->get($missionId);

        $this->assertEquals($missionId, $result->mission->id);
        $this->assertEquals(MissionStatus::$SUBMITTED, $result->mission->status);
        $this->assertEquals(getenv('DEALT_TEST_OFFER_ID'), $result->mission->offer->id);

        return $missionId;
    }

    // /**
    //  * @param string $missionId
    //  * @depends testGetMissionByIdSuccessfully
    //  */
    // public function testGetAllMissionsSuccessfully($missionId): string
    // {
    //     $result = $this->client->missions->all();

    //     $mission_ids = array_map(function ($mission) {
    //         return $mission->id;
    //     }, $result->missions);

    //     $this->assertEquals(true, in_array($missionId, $mission_ids));

    //     return $missionId;
    // }

    /**
     * @param string $missionId
     * @depends testGetMissionByIdSuccessfully
     */
    public function testCancelsMissionSuccessfully($missionId)
    {
        $result = $this->client->missions->cancel($missionId);

        $this->assertEquals($missionId, $result->mission->id);
        $this->assertEquals(MissionStatus::$CANCELLED, $result->mission->status);
    }
}
