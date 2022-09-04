<?php

namespace Dealt\DealtSDK;

use Dealt\DealtSDK\Services\AbstractDealtService;
use Dealt\DealtSDK\Services\DealtServiceFactory;

class DealtClient extends CoreDealtClient
{
    /** @var DealtServiceFactory */
    private $serviceFactory;

    /**
     * Initializes a new client
     * Configuration settings include the following options:.
     *
     * - api_key (string): The Dealt API Key to be used for internal GraphQL requests.
     * - env (null|string): The Dealt API Environment ("production"|"test") - defaults to test.
     *
     * @param array<string, mixed> $config an array containing the client configuration setttings
     */
    public function __construct($config)
    {
        $this->serviceFactory = new DealtServiceFactory($this);
        parent::__construct($config);
    }

    public function __get(string $name): AbstractDealtService
    {
        return $this->serviceFactory->__get($name);
    }
}
