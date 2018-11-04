<?php
namespace BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\ContentType;

use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\BaseWorker;
use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\SchemaWorker;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup;

class DefineDomainContentMutation extends BaseWorker implements SchemaWorker
{
    public function work(array &$schema, array $args)
    {
        $contentType = $args['ContentType'];

        // This should ideally be some "init" worker type
        if (!isset($schema['DomainContentMutation'])) {
            $schema['DomainContentMutation'] = [
                'type' => 'object',
                'inherits' => ['PlatformMutation'],
                'config' => [
                    'fields' => []
                ]
            ];
        }

        // create mutation field
        $schema
            ['DomainContentMutation']
            ['config']['fields']
            [$this->getCreateField($contentType)] = [
                // @todo Use payload
                'type' => $this->getNameHelper()->domainContentName($contentType) . '!',
                'resolve' => sprintf(
                    '@=mutation("CreateDomainContent", [args["input"], "%s", args["parentLocationId"], args["language"]])',
                    $contentType->identifier
                ),
                'args' => [
                    'input' => ['type' => $this->getCreateInputName($contentType) . '!'],
                    'language' => ['type' => 'String', 'defaultValue' => "eng-GB"],
                    'parentLocationId' => ['type' => 'Int!'],
                ],
            ];

        // Create mutation input type, no fields
        $schema[$this->getCreateInputName($contentType)] = [
            'type' => 'input-object',
            'config' => [
                'fields' => []
            ]
        ];

        // Update mutation field
        $schema
            ['DomainContentMutation']
            ['config']['fields']
            [$this->getUpdateField($contentType)] = [
            // @todo Use payload
            'type' => $this->getNameHelper()->domainContentName($contentType) . '!',
            'resolve' => '@=mutation("UpdateDomainContent", [args["input"], args, args["versionNo"], args["language"]])',
            'args' => [
                'input' => ['type' => $this->getUpdateInputName($contentType) . '!'],
                'language' => ['type' => 'String', 'defaultValue' => "eng-GB"],
                'id' =>  ['type' => 'ID', 'description' => 'ID of the content item to update'],
                'contentId' => ['type' => 'Int', 'description' => 'ID of the content item to update'],
                'versionNo' => ['type' => 'Int', 'description' => 'Optional version number to update. If it is a draft, it is saved, not published. If it is archived, it is used as the source version for the update, to complete missing fields.'],
            ],
        ];

        // Update mutation input
        $schema[$this->getUpdateInputName($contentType)] = [
            'type' => 'input-object',
            'config' => [
                'fields' => []
            ]
        ];
    }

    public function canWork(array $schema, array $args)
    {
        return isset($args['ContentType'])
               && $args['ContentType'] instanceof ContentType
               && !isset($args['FieldDefinition']);
    }

    /**
     * @param $contentType
     * @return string
     */
    protected function getCreateInputName($contentType): string
    {
        return $this->getNameHelper()->domainContentCreateInputName($contentType);
    }

    /**
     * @param $contentType
     * @return string
     */
    protected function getUpdateInputName($contentType): string
    {
        return $this->getNameHelper()->domainContentUpdateInputName($contentType);
    }

    /**
     * @param $contentType
     * @return string
     */
    protected function getCreateField($contentType): string
    {
        return $this->getNameHelper()->domainMutationCreateContentField($contentType);
    }

    /**
     * @param $contentType
     * @return string
     */
    protected function getUpdateField($contentType): string
    {
        return $this->getNameHelper()->domainMutationUpdateContentField($contentType);
    }
}