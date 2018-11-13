<?php
namespace BD\EzPlatformGraphQLBundle\Search;

use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;

class LegacySearchFeatures implements SearchFeatures
{
    /**
     * @var \eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry
     */
    private $converterRegistry;

    public function __construct(ConverterRegistry $converterRegistry)
    {
        $this->converterRegistry = $converterRegistry;
    }

    public function supportsFieldCriterion(FieldDefinition $fieldDefinition)
    {
        return $this->converterRegistry->getConverter($fieldDefinition->fieldTypeIdentifier)->getIndexColumn() !== false;
    }
}