<?php

namespace Dealt\DealtSDK\GraphQL;

use Dealt\DealtSDK\DealtEnvironment;
use Dealt\DealtSDK\Exceptions\GraphQLException;
use Exception;

/**
 * Minimal GraphQL Client for interacting with the
 * Dealt API.
 */
class GraphQLClient
{
    /** @var string[] */
    private static $HEADERS = ['Content-Type: application/json'];

    /** @var string */
    public $apiKey;

    /** @var string */
    public $endpoint;

    /**
     * @param string $apiKey Dealt API key
     * @param string $env    Dealt environment
     */
    public function __construct(string $apiKey, string $env)
    {
        $this->apiKey   = $apiKey;
        $this->endpoint = [
            DealtEnvironment::$PRODUCTION => 'https://api.dealt.fr/graphql',
            DealtEnvironment::$TEST       => 'https://api.test.dealt.fr/graphql',
        ][$env];
    }

    /**
     * Public request execution function
     * can be used for queries or mutations.
     */
    public function exec(GraphQLOperationInterface $operation): GraphQLObjectInterface
    {
        try {
            $operation->setApiKey($this->apiKey);
            $operation->validateQueryParameters();

            $result = $this->request($operation);

            return $operation->parseResult($result);
        } catch (Exception $e) {
            throw new GraphQLException($e->getMessage());
        }
    }

    /**
     * Executes a GraphQL request to the Dealt API endpoint.
     *
     * @throws Exception
     */
    public function request(GraphQLOperationInterface $operation): string
    {
        $context  = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => $this->merge_headers(),
                'content'       => json_encode([
                    'query'         => $operation->toQuery(),
                    'operationName' => $operation->getOperationName(),
                    'variables'     => $operation->toQueryVariables(),
                ]),
                'ignore_errors' => true,
            ],
        ]);

        $result   = @file_get_contents($this->endpoint, false, $context);

        if ($result == false) {
            throw new Exception('something went wrong while connecting to the Dealt API');
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function merge_headers(): array
    {
        return array_merge(GraphQLClient::$HEADERS, []);
    }
}
