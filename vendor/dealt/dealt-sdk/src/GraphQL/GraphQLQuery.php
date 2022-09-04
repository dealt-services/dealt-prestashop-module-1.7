<?php

namespace Dealt\DealtSDK\GraphQL;

abstract class GraphQLQuery extends GraphQLOperation
{
    /** @var string */
    public static $operationType = 'query';
}
