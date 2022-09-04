<?php

namespace Dealt\DealtSDK;

use Dealt\DealtSDK\Exceptions\InvalidArgumentException;
use Dealt\DealtSDK\GraphQL\GraphQLClient;

/**
 * Client used to send requests to Dealt's API.
 *
 * @property \Dealt\DealtSDK\Services\DealtOffers   $offers
 * @property \Dealt\DealtSDK\Services\DealtMissions $missions
 */
class CoreDealtClient
{
    /** @var GraphQLClient */
    public $gqlClient;

    /**
     * Initializes a new client.
     *
     * @param array<string, mixed> $config an array containing the client configuration setttings
     */
    public function __construct($config = [])
    {
        if (!is_array($config)) {
            throw new InvalidArgumentException('$config must be an array');
        }
        $config = array_merge([
            'env' => DealtEnvironment::$TEST,
        ], $config);
        $this->validateConfig($config);
        $this->gqlClient = new GraphQLClient(strval($config['api_key']), strval($config['env']));
    }

    /**
     * @param array<string, mixed> $config the config object passed to the DealtClient constructor
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private function validateConfig($config)
    {
        if (!isset($config['api_key']) || !is_string($config['api_key'])) {
            throw new InvalidArgumentException('api_key must be a string');
        }

        if (!isset($config['env']) || !is_string($config['env']) || !in_array($config['env'], [DealtEnvironment::$PRODUCTION, DealtEnvironment::$TEST])) {
            throw new InvalidArgumentException('env must be a string set to "production" or "test"');
        }
    }
}
