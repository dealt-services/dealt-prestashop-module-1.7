<?php

namespace Dealt\DealtSDK\Exceptions;

/**
 * GraphQL Failure Exception is thrown when an error
 * field is present in the response - the error message will
 * be the content of the first graphQlError.
 */
class GraphQLFailureException extends GraphQLException
{
}
