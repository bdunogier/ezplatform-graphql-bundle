<?php
/**
 * Created by PhpStorm.
 * User: bdunogier
 * Date: 29/10/2018
 * Time: 12:39
 */

namespace BD\EzPlatformGraphQLBundle\GraphQL\Relay;


use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;

class DomainConnectionBuilder extends ConnectionBuilder
{
    const PREFIX = 'DomainContent:';
}