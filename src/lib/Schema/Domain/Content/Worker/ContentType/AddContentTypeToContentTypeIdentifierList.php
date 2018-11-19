<?php
namespace BD\EzPlatformGraphQL\Schema\Domain\Content\Worker\ContentType;

use BD\EzPlatformGraphQL\Schema\Domain\Content\Worker\BaseWorker;
use BD\EzPlatformGraphQL\Schema\Initializer;
use BD\EzPlatformGraphQL\Schema\Worker;
use BD\EzPlatformGraphQL\Schema\Builder\Input;
use BD\EzPlatformGraphQL\Schema\Builder;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;

/**
 * Adds a content type to the content type identifiers list (ContentTypeIdentifier)
 */
class AddContentTypeToContentTypeIdentifierList extends BaseWorker implements Worker, Initializer
{
    const TYPE = 'ContentTypeIdentifier';

    public function work(Builder $schema, array $args)
    {
        $contentType = $args['ContentType'];

        $descriptions = $contentType->getDescriptions();
        $description = isset($descriptions['eng-GB']) ? $descriptions['eng-GB'] : 'No description available';

        $schema->addValueToEnum(
            self::TYPE,
            new Input\EnumValue(
                $contentType->identifier,
                ['description' => $description]
            )
        );
    }

    public function init(Builder $schema)
    {
        $schema->addType(new Input\Type(self::TYPE, 'enum'));
    }

    public function canWork(Builder $schema, array $args)
    {
        $canWork =
            isset($args['ContentType'])
            && $args['ContentType'] instanceof ContentType
            && $schema->hasType(self::TYPE);

        return $canWork;
    }
}