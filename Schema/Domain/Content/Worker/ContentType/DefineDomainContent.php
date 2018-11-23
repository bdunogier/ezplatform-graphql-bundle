<?php
namespace BD\EzPlatformGraphQLBundle\Schema\Domain\Content\Worker\ContentType;

use BD\EzPlatformGraphQLBundle\Schema\Domain\Content\Worker\BaseWorker;
use BD\EzPlatformGraphQLBundle\Schema\Worker;
use BD\EzPlatformGraphQLBundle\Schema\Builder\Input;
use BD\EzPlatformGraphQLBundle\Schema\GroupProvider;
use BD\EzPlatformGraphQLBundle\Schema\Builder;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;

class DefineDomainContent extends BaseWorker implements Worker, GroupProvider
{
    public function work(Builder $schema, array $args)
    {
        $schema->addType(new Input\Type(
            $this->typeName($args), 'object',
            [
                'inherits' => 'AbstractDomainContent',
                'interfaces' => 'DomainContent'
            ]
        ));
    }

    public function canWork(Builder $schema, array $args)
    {
        return
            isset($args['ContentType'])
            && $args['ContentType'] instanceof ContentType
            && !$schema->hasType($this->typeName($args));
    }

    protected function typeName(array $args): string
    {
        return $this->getNameHelper()->domainContentName($args['ContentType']);
    }

    public function getGroups(array $args)
    {
        // @todo implement me
    }
}