<?php

namespace spec\BD\EzPlatformGraphQL\GraphQL\Resolver;

use BD\EzPlatformGraphQL\GraphQL\InputMapper\SearchQueryMapper;
use BD\EzPlatformGraphQL\GraphQL\Resolver\DomainContentResolver;
use eZ\Publish\API\Repository\Repository;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use PhpSpec\ObjectBehavior;

class DomainContentResolverSpec extends ObjectBehavior
{
    function let(
        Repository $repository,
        TypeResolver $typeResolver,
        SearchQueryMapper $searchQueryMapper
    ) {
        $this->beConstructedWith($repository, $typeResolver, $searchQueryMapper);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(DomainContentResolver::class);
    }
}
