<?php
namespace BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\FieldDefinition;

use BD\EzPlatformGraphQLBundle\DomainContent\FieldValueBuilder\FieldValueBuilder;
use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\BaseWorker;
use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\SchemaWorker;
use BD\EzPlatformGraphQLBundle\Search\SearchFeatures;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;
use eZ\Publish\Core\Repository\Helper\FieldTypeRegistry;

/**
 * Adds the field definition, if it is searchable, as a filter on the type's collection.
 */
class AddFieldDefinitionToCollectionFilters extends BaseWorker implements SchemaWorker
{
    /**
     * @var SearchFeatures
     */
    private $searchFeatures;

    public function __construct(SearchFeatures $searchFeatures)
    {
        $this->searchFeatures = $searchFeatures;
    }

    public function work(array &$schema, array $args)
    {
        $domainGroupName = $this->getNameHelper()->domainGroupName($args['ContentTypeGroup']);
        $domainContentCollectionField = $this->getNameHelper()->domainContentCollectionField($args['ContentType']);
        $fieldDefinitionField = $this->getNameHelper()->fieldDefinitionField($args['FieldDefinition']);

        $filter = [
            'type' => $this->getFilterType($args['FieldDefinition']),
            'description' => 'Filter content based on the ' . $args['FieldDefinition']->identifier . ' field',
        ];

        $schema
            [$domainGroupName]
            ['config']['fields']
            [$domainContentCollectionField]
            ['args'][$fieldDefinitionField] = $filter;
    }

    public function canWork(array $schema, array $args)
    {
        return
            isset($args['FieldDefinition'])
            && $args['FieldDefinition'] instanceof FieldDefinition
            & isset($args['ContentType'])
            && $args['ContentType'] instanceof ContentType
            && $this->searchFeatures->supportsFieldCriterion($args['FieldDefinition']);
    }

    /**
     * @param ContentType $contentType
     * @return string
     */
    protected function getDomainContentName(ContentType $contentType): string
    {
        return $this->getNameHelper()->domainContentName($contentType);
    }

    /**
     * @param FieldDefinition $fieldDefinition
     * @return string
     */
    protected function getFieldDefinitionField(FieldDefinition $fieldDefinition): string
    {
        return $this->getNameHelper()->fieldDefinitionField($fieldDefinition);
    }

    private function isSearchable(FieldDefinition $fieldDefinition): bool
    {
        return $fieldDefinition->isSearchable
            // should only be verified if legacy is the current search engine
            && $this->converterRegistry->getConverter($fieldDefinition->fieldTypeIdentifier)->getIndexColumn() !== false;
    }

    private function getFilterType(FieldDefinition $fieldDefinition)
    {
        switch ($fieldDefinition->fieldTypeIdentifier)
        {
            case 'ezboolean':
                return 'Boolean';
            default:
                return 'String';
        }
    }
}