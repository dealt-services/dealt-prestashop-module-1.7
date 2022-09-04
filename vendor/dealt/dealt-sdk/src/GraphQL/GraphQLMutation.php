<?php

namespace Dealt\DealtSDK\GraphQL;

abstract class GraphQLMutation extends GraphQLOperation
{
    /** @var string */
    public static $operationType = 'mutation';
}
