<?php

namespace Dealt\Module\Dealtmodule\Api;
use Dealt\DealtSDK\DealtClient;
use Dealt\DealtSDK\DealtEnvironment;
use Dealt\DealtSDK\Exceptions\GraphQLException;
use Dealt\DealtSDK\Exceptions\GraphQLFailureException;
use Dealt\DealtSDK\GraphQL\Types\Object\Mission;
use Dealt\DealtSDK\GraphQL\Types\Object\OfferAvailabilityQuerySuccess;
use DealtModuleLogger;
use Module;
use Dealt\Module\Dealtmodule\Api\DealtEnv;


Abstract class DealtGenericClient
{
    /**
     * @var DealtEnv
     */
    protected $env;
    protected $module;

    public function __construct($module, DealtEnv $env)
    {
        $this->env = $env;
        $this->module = $module;
    }

    protected $client;

    /**
     * Retrieves the DealtSDK\DealtClient or instantiates
     * a fresh instance on first call
     *
     * @return DealtClient
     */
    protected function getClient()
    {
        if ($this->client instanceof DealtClient) {
            return $this->client;
        }

        return new DealtClient([
            'api_key' => $this->env->getDealtApiKey(),
            'env' => $this->env->getName() === 'prod' ? DealtEnvironment::$PRODUCTION : DealtEnvironment::$TEST,
        ]);
    }

    protected function handleException($exception)
    {
        $body = '';
        $httpCode = 500;
        $hasResponse = method_exists($exception, 'hasResponse') ? $exception->hasResponse() : false;

        if (true === $hasResponse && method_exists($exception, 'getResponse')) {
            $body = $exception->getResponse()->getBody();
            $httpCode = $exception->getResponse()->getStatusCode();
        }
        DealtModuleLogger::log('Service temporarely indisponible', DealtModuleLogger::TYPE_ERROR, [
            'status' => false,
            'php version'=>phpversion(),
            'httpCode' => $httpCode,
            'body' => $body,
            'exceptionCode' => $exception->getCode(),
            'exceptionMessage' => $exception->getMessage(),
            'exceptionFile' => $exception->getFile(),
            'exceptionLine' => $exception->getLine(),
            'exceptionTrace' => $exception->getTrace(),
        ]);
        return [
            'status' => false,
            'httpCode' => $httpCode,
            'body' => $body,
            'exceptionCode' => $exception->getCode(),
            'exceptionMessage' => $exception->getMessage(),
        ];
    }

    protected function handleResponse($successMessage, $method, $arguments, $response)
    {
        if ($this->env->isDebugMode()) {
            DealtModuleLogger::log($successMessage, DealtModuleLogger::TYPE_SUCCESS, [
                'status' => '200',
                'method' => $method,
                'php version'=>phpversion(),
                'arguments' => json_encode($arguments),
                'response' => json_encode($response)
            ]);
        }
        return [
            'status' => '200',
            'method' => $method,
            'arguments' => $arguments,
            'response' => $response
        ];
    }

}
