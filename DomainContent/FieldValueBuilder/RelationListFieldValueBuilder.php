<?php
namespace BD\EzPlatformGraphQLBundle\DomainContent\FieldValueBuilder;

use BD\EzPlatformGraphQLBundle\DomainContent\NameHelper;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;

class RelationListFieldValueBuilder implements FieldValueBuilder
{
    /**
     * @var NameHelper
     */
    private $nameHelper;

    /**
     * @var ContentTypeService
     */
    private $contentTypeService;

    public function __construct(NameHelper $nameHelper, ContentTypeService $contentTypeService)
    {
        $this->nameHelper = $nameHelper;
        $this->contentTypeService = $contentTypeService;
    }

    public function buildDefinition(FieldDefinition $fieldDefinition)
    {
        $settings = $fieldDefinition->getFieldSettings();
        $constraints = $fieldDefinition->getValidatorConfiguration();

        if (count($settings['selectionContentTypes']) === 1) {
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier($settings['selectionContentTypes'][0]);
            $type = $this->nameHelper->domainContentName($contentType);
        } else {
            $type = 'DomainContent';
        }

        $isMultiple = 'false';
        if ($constraints['RelationListValueValidator']['selectionLimit'] !== 1) {
            $isMultiple = 'true';
            $type = "[$type]";
        }

        $resolver = sprintf(
            '@=resolver("DomainRelationFieldValue", [value, "%s", %s])',
            $fieldDefinition->identifier,
            $isMultiple
        );

        $field = [
            'type' => $type,
            'resolve' => $resolver
        ];

        if (isset($contentType)) {
            $field['public'] = sprintf(
                '@=service("ezplatform_graphql.can_user").viewContentOfType("%s")',
                $contentType->identifier
            );
        }

        return $field;
    }

    private function mapFieldTypeIdentifierToGraphQLType($fieldTypeIdentifier)
    {
        return isset($this->typesMap[$fieldTypeIdentifier]) ? $this->typesMap[$fieldTypeIdentifier] : 'GenericFieldValue';
    }
}