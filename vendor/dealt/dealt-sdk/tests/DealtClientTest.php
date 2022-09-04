<?php

use Dealt\DealtSDK\DealtClient;
use Dealt\DealtSDK\DealtEnvironment;
use Dealt\DealtSDK\Exceptions\InvalidArgumentException;
use Dealt\DealtSDK\Services\DealtMissions;
use Dealt\DealtSDK\Services\DealtOffers;
use PHPUnit\Framework\TestCase;

final class DealtClientTest extends TestCase
{
    public function testInitializesCorrectly()
    {
        $this->assertInstanceOf(
            DealtClient::class,
            new DealtClient(['api_key' => 'xxx', 'env' => DealtEnvironment::$PRODUCTION])
        );
    }

    public function testInitializesCorrectlyWhenMissingEnv()
    {
        $this->assertInstanceOf(
            DealtClient::class,
            new DealtClient(['api_key' => 'xxx'])
        );
    }

    public function testThrowsWhenMissingApiKey()
    {
        $this->expectException(InvalidArgumentException::class);
        new DealtClient(['env' => DealtEnvironment::$TEST]);
    }

    public function testThrowsWhenGivenWrongApiKeyType()
    {
        $this->expectException(InvalidArgumentException::class);
        new DealtClient(['api_key' => []]);
    }

    public function testThrowsWhenGivenWrongEnvKeyType()
    {
        $this->expectException(InvalidArgumentException::class);
        new DealtClient(['api_key' => '', 'env' => []]);
    }

    public function testResolvesMissionsService()
    {
        $client = new DealtClient(['api_key' => 'xxx']);
        $this->assertInstanceOf(
            DealtMissions::class,
            $client->missions
        );
    }

    public function testResolvesOffersService()
    {
        $client = new DealtClient(['api_key' => 'xxx']);
        $this->assertInstanceOf(
            DealtOffers::class,
            $client->offers
        );
    }
}
