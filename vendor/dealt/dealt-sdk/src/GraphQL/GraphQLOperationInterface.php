<?php

namespace Dealt\DealtSDK\GraphQL;

interface GraphQLOperationInterface
{
    public function setApiKey(string $apiKey): GraphQLOperationInterface;

    /**
     * @return string
     */
    public static function toQuery();

    /**
     * @return string
     */
    public static function getOperationName();

    /**
     * @return array<string, mixed>
     */
    public function toQueryVariables();

    /**
     * @param string $result
     */
    public function parseResult($result): GraphQLObjectInterface;

    /**
     * @return void
     */
    public function validateQueryParameters();
}
