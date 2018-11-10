<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\EzPlatformGraphQLBundle\GraphQL\Resolver;

use DOMDocument;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\Values\Content\Relation;
use eZ\Publish\Core\FieldType\RichText\Converter as RichTextConverterInterface;
use eZ\Publish\Core\FieldType\RichText;

class RichTextResolver
{
    /**
     * @var RichTextConverterInterface
     */
    private $richTextConverter;
    /**
     * @var RichTextConverterInterface
     */
    private $richTextEditConverter;

    /**
     * @var RichText\Type
     */
    private $fieldType;
    /**
     * @var ContentService
     */
    private $contentService;

    public function __construct(
        RichTextConverterInterface $richTextConverter,
        RichTextConverterInterface $richTextEditConverter,
        ContentService $contentService,
        RichText\Type $fieldType
    )
    {
        $this->richTextConverter = $richTextConverter;
        $this->richTextEditConverter = $richTextEditConverter;
        $this->fieldType = $fieldType;
        $this->contentService = $contentService;
    }

    public function xmlToHtml5(DOMDocument $document)
    {
        return $this->richTextConverter->convert($document)->saveHTML();
    }

    public function xmlToHtml5Edit(DOMDocument $document)
    {
        return $this->richTextEditConverter->convert($document)->saveHTML();
    }

    public function resolveEmbeds(RichText\Value $value)
    {
        foreach ($this->fieldType->getRelations($value)[Relation::EMBED]['contentIds'] as $embeddedContentId) {
            // @todo Handle locationIds as well
            yield $this->contentService->loadContentInfo($embeddedContentId);
        }
    }
}
