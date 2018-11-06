<?php
namespace BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\FieldDefinition;

use BD\EzPlatformGraphQLBundle\DomainContent\FieldValueBuilder\FieldValueBuilder;
use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\BaseWorker;
use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\SchemaWorker;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;

class AddFieldDefinitionToDomainContentMutation extends BaseWorker implements SchemaWorker
{
    const OPERATION_CREATE = 'create';
    const OPERATION_UPDATE = 'update';

    public function work(array &$schema, array $args)
    {
        $fieldDefinition = $args['FieldDefinition'];
        $contentType = $args['ContentType'];

        $fieldDefinitionField = $this->getFieldDefinitionField($fieldDefinition);

        $schema
            [$this->getCreateInputName($contentType)]
            ['config']['fields']
            [$fieldDefinitionField] = [
                'type' => $this->getFieldDefinitionToGraphQLType($args['FieldDefinition'], self::OPERATION_CREATE),
                'description' => $fieldDefinition->getDescriptions()['eng-GB'] ?? '',
            ];

        $schema
            [$this->getUpdateInputName($contentType)]
            ['config']['fields']
            [$fieldDefinitionField] = [
                'type' => $this->getFieldDefinitionToGraphQLType($args['FieldDefinition'], self::OPERATION_UPDATE),
                'description' => $fieldDefinition->getDescriptions()['eng-GB'] ?? '',
            ];
    }

    public function canWork(array $schema, array $args): bool
    {
        return
            isset($args['ContentType'])
            && $args['ContentType'] instanceof ContentType
            && isset($args['FieldDefinition'])
            && $args['FieldDefinition'] instanceof FieldDefinition;
    }

    /**
     * @param ContentType $contentType
     * @return string
     */
    protected function getCreateInputName(ContentType $contentType): string
    {
        return $this->getNameHelper()->domainContentCreateInputName($contentType);
    }

    /**
     * @param ContentType $contentType
     * @return string
     */
    private function getUpdateInputName($contentType): string
    {
        return $this->getNameHelper()->domainContentUpdateInputName($contentType);
    }

    /**
     * @param FieldDefinition $fieldDefinition
     * @return string
     */
    protected function getFieldDefinitionField(FieldDefinition $fieldDefinition): string
    {
        return $this->getNameHelper()->fieldDefinitionField($fieldDefinition);
    }

    private function getFieldDefinitionToGraphQLType(FieldDefinition $fieldDefinition, $operation): string
    {
        $map = [
            'ezauthor' => '[AuthorInput]',
            // @todo needs custom input for other fields
            'ezbinaryfile' => 'String',
            'ezboolean' => 'Boolean',
            // @todo might be multiple
            'ezcountry' => 'String',
            // @todo needs custom input for other fields
            'ezmediafile' => 'String',
            'ezfloat' => 'Float',
            // @todo needs custom input for other fields
            'ezimage' => 'ImageFieldInput',
            'ezinteger' => 'Int',
            'ezmedia' => 'String',
            'ezobjectrelation' => 'Int',
            'ezobjectrelationlist' => '[Int]',
            // @todo multi-input format, with type + richtext ?
            'ezrichtext' => 'RichTextFieldInput',
            'ezstring' => 'String',
            // @todo might be multiple.
            'ezselection' => 'Int',
            'eztext' => 'String',
            // @todo externalize to package
            'query' => null
        ];

        $requiredFlag = $operation == self::OPERATION_CREATE && $fieldDefinition->isRequired ? '!': '';

        return isset($map[$fieldDefinition->fieldTypeIdentifier])
            ? ($map[$fieldDefinition->fieldTypeIdentifier] . $requiredFlag)
            : ('String' . $requiredFlag);
    }
}