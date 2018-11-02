<?php
namespace BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\FieldDefinition;

use BD\EzPlatformGraphQLBundle\DomainContent\FieldValueBuilder\FieldValueBuilder;
use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\BaseWorker;
use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\SchemaWorker;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;

class AddFieldDefinitionToDomainContentMutation extends BaseWorker implements SchemaWorker
{
    public function work(array &$schema, array $args)
    {
        $fieldDefinition = $args['FieldDefinition'];
        $contentType = $args['ContentType'];

        $fieldDefinitionField = $this->getFieldDefinitionField($fieldDefinition);
        $domainInputName = $this->getDomainContentInputName($contentType);

        $schema
            [$domainInputName]
            ['config']['fields']
            [$fieldDefinitionField] = [
                'type' => $this->getFieldDefinitionToGraphQLType($args['FieldDefinition']),
                'description' => $fieldDefinition->getDescriptions()['eng-GB'] ?? '',
            ];
    }

    public function canWork(array $schema, array $args)
    {
        return
            isset($args['FieldDefinition'])
            && $args['FieldDefinition'] instanceof FieldDefinition
            & isset($args['ContentType'])
            && $args['ContentType'] instanceof ContentType
            && !$this->isFieldDefined($schema, $args);
    }

    /**
     * @param ContentType $contentType
     * @return string
     */
    protected function getDomainContentInputName(ContentType $contentType): string
    {
        return $this->getNameHelper()->domainContentInputName($contentType);
    }

    /**
     * @param FieldDefinition $fieldDefinition
     * @return string
     */
    protected function getFieldDefinitionField(FieldDefinition $fieldDefinition): string
    {
        return $this->getNameHelper()->fieldDefinitionField($fieldDefinition);
    }

    private function isFieldDefined($schema, $args)
    {
        return isset(
            $schema[$this->getNameHelper()->domainContentInputName($args['ContentType'])]
                   ['config']['fields']
                   [$this->getFieldDefinitionField($args['FieldDefinition'])]);
    }

    private function getFieldDefinitionToGraphQLType(FieldDefinition $fieldDefinition)
    {
        $map = [
            'ezauthor' => '[AuthorInput]',
            'ezbinaryfile' => 'String',
            'ezboolean' => 'Boolean',
            'ezcountry' => 'String',
            'ezmediafile' => 'String',
            'ezfloat' => 'Float',
            'ezimage' => 'String',
            'ezinteger' => 'Int',
            'ezmedia' => 'String',
            'ezobjectrelation' => 'Int',
            'ezobjectrelationlist' => '[Int]',
            'ezstring' => 'String',
            'ezselection' => 'Int',
            'eztext' => 'String',
        ];

        $requiredFlag = $fieldDefinition->isRequired ? '!': '';

        return isset($map[$fieldDefinition->fieldTypeIdentifier])
            ? ($map[$fieldDefinition->fieldTypeIdentifier] . $requiredFlag)
            : ('String' . $requiredFlag);
    }
}