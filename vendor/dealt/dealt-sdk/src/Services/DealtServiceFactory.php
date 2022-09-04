<?php

namespace Dealt\DealtSDK\Services;

use Dealt\DealtSDK\DealtClient;
use Exception;

class DealtServiceFactory
{
    /**
     * Internal map of available dealt services.
     *
     * @var array<string, string>
     */
    private static $classMap = [
        'offers'   => DealtOffers::class,
        'missions' => DealtMissions::class,
    ];

    /** @var DealtClient */
    private $client;

    /** @var array<string, AbstractDealtService> */
    private $services;

    public function __construct(DealtClient $client)
    {
        $this->client   = $client;
        $this->services = [];
    }

    /**
     * @return string|null
     */
    protected function getServiceClass(string $name)
    {
        return in_array($name, array_keys(self::$classMap)) ? self::$classMap[$name] : null;
    }

    /**
     * Undocumented function.
     *
     * @throws Exception
     */
    public function __get(string $name): AbstractDealtService
    {
        $serviceClass = $this->getServiceClass($name);

        if (null !== $serviceClass) {
            if (!array_key_exists($name, $this->services)) {
                /** @var AbstractDealtService */
                $serviceInstance       =  new $serviceClass($this->client);
                $this->services[$name] = $serviceInstance;
            }

            $service = $this->services[$name];

            return $service;
        }

        throw new Exception('unknown service requested');
    }
}
