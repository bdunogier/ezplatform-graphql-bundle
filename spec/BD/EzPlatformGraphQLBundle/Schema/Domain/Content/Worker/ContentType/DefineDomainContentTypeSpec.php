<?php

namespace spec\BD\EzPlatformGraphQLBundle\Schema\Domain\Content\Worker\ContentType;

use BD\EzPlatformGraphQLBundle\Schema\Builder\SchemaBuilder;
use BD\EzPlatformGraphQLBundle\Schema\Domain\Content\NameHelper;
use BD\EzPlatformGraphQLBundle\Schema\Domain\Content\Worker\ContentType\DefineDomainContentType;
use BD\EzPlatformGraphQLBundle\spec\Tools\ContentTypeArgument;
use BD\EzPlatformGraphQLBundle\spec\Tools\TypeArgument;
use Prophecy\Argument;

class DefineDomainContentTypeSpec extends ContentTypeWorkerBehavior
{
    const TYPE_TYPE = 'TestTypeContentType';

    function let(NameHelper $nameHelper)
    {
        $nameHelper
            ->domainContentTypeName(ContentTypeArgument::withIdentifier(self::TYPE_IDENTIFIER))
            ->willReturn(self::TYPE_TYPE);

        $this->setNameHelper($nameHelper);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(DefineDomainContentType::class);
    }

    function it_can_not_work_if_args_do_not_include_a_ContentTypeGroup(SchemaBuilder $schema)
    {
        $this->canWork($schema, [])->shouldBe(false);
    }

    function it_can_not_work_if_args_do_not_include_a_ContentType(SchemaBuilder $schema)
    {
        $args = $this->args();
        unset($args['ContentType']);
        $this->canWork($schema, $args)->shouldBe(false);
    }

    function it_defines_a_DomainContent_type_based_on_the_ContentType(SchemaBuilder $schema)
    {
        $schema
            ->addType(Argument::allOf(
                TypeArgument::isNamed(self::TYPE_TYPE),
                TypeArgument::hasType('object'),
                TypeArgument::inherits('BaseDomainContentType'),
                TypeArgument::implements('DomainContentType')
            ))
            ->shouldBeCalled();

        $this->work($schema, $this->args());
    }
}
