<?php
namespace BD\EzPlatformGraphQLBundle\Schema\Domain\Content;

use BD\EzPlatformGraphQLBundle\Schema\Builder\Input;
use BD\EzPlatformGraphQLBundle\Schema\Domain\Iterator;
use BD\EzPlatformGraphQLBundle\Schema\Builder;
use eZ\Publish\API\Repository\ContentTypeService;
use Generator;

class ContentDomainIterator implements Iterator
{
    /**
     * @var ContentTypeService
     */
    private $contentTypeService;

    public function __construct(ContentTypeService $contentTypeService)
    {
        $this->contentTypeService = $contentTypeService;
    }
    
    public function init(Builder $schema)
    {
        $schema->addType(
            new Input\Type('Domain', 'object', ['inherits' => ['Platform']])
        );
    }

    public function iterate(): Generator
    {
        foreach ($this->contentTypeService->loadContentTypeGroups() as $contentTypeGroup) {
            $args = ['ContentTypeGroup' => $contentTypeGroup];
            yield $args;

            foreach ($this->contentTypeService->loadContentTypes($contentTypeGroup) as $contentType) {
                $args['ContentType'] = $contentType;
                yield $args;

                foreach ($contentType->getFieldDefinitions() as $fieldDefinition) {
                    $args['FieldDefinition'] = $fieldDefinition;
                    yield $args;
                }
            }
        }
    }
}