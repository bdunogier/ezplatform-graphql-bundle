<?php
namespace BD\EzPlatformGraphQLBundle\Schema\Domain\Content\Worker\ContentType;

use BD\EzPlatformGraphQLBundle\Schema\Domain\Content\Worker\BaseWorker;
use BD\EzPlatformGraphQLBundle\Schema\Worker;
use BD\EzPlatformGraphQLBundle\Schema\Builder;
use BD\EzPlatformGraphQLBundle\Schema\Builder\Input;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup;

class AddContentTypeToDomainGroupTypes extends BaseWorker implements Worker
{
    public function work(Builder $schema, array $args)
    {
        $resolve = sprintf(
            '@=resolver("ContentType", [{"identifier": "%s"}])',
            $args['ContentType']->identifier
        );

        $schema->addFieldToType(
            $this->groupTypesName($args),
            new Input\Field(
                $this->typeField($args),
                $this->typeName($args),
                ['resolve' => $resolve]
            )
        );
    }

    public function canWork(Builder $schema, array $args)
    {
        return
            isset($args['ContentType'])
            && $args['ContentType'] instanceof ContentType
            && isset($args['ContentTypeGroup'])
            && $args['ContentTypeGroup'] instanceof ContentTypeGroup
            && !$schema->hasTypeWithField($this->groupTypesName($args), $this->typeField($args));
    }

    protected function typeField(array $args): string
    {
        return $this->getNameHelper()->domainContentField($args['ContentType']);
    }

    protected function groupTypesName($contentTypeGroup): string
    {
        return $this->getNameHelper()->domainGroupName($contentTypeGroup) . 'Types';
    }

    protected function typeName($args): string
    {
        return $this->getNameHelper()->domainContentTypeName($args['ContentType']);
    }
}