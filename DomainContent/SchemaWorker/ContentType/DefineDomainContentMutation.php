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
        $domainContentInputName = $this->getNameHelper()->domainContentInputName($contentType);

        $schema[$domainContentInputName] = [
            'type' => 'input-object',
            'config' => [
                'fields' => [],
            ]
        ];

        if (!isset($schema['DomainContentMutation'])) {
            $schema['DomainContentMutation'] = [
                'type' => 'object',
                'inherits' => ['PlatformMutation'],
                'config' => [
                    'fields' => []
                ]
            ];
        }

        $schema
            ['DomainContentMutation']
            ['config']['fields']
            [$this->getNameHelper()->domainMutationCreateField($contentType)] = [
                'type' => $this->getNameHelper()->domainContentName($contentType) . '!',
                'resolve' => sprintf(
                    '@=mutation("CreateDomainContent", [args["input"], "%s", args["parentLocationId"], args["language"]])',
                    $contentType->identifier
                ),
                'args' => [
                    'input' => ['type' => $domainContentInputName],
                    'language' => ['type' => 'String', 'defaultValue' => "eng-GB"],
                    'parentLocationId' => ['type' => 'Int!'],
                ],
            ];

        // Domain content input type, no fields
        $schema[$domainContentInputName] = [
            'type' => 'input-object',
            'config' => [
                'fields' => []
            ]
        ];
    }

    public function canWork(array $schema, array $args)
    {
        return
            isset($args['ContentType']) && $args['ContentType'] instanceof ContentType
            && !isset($schema[$this->getNameHelper()->domainContentInputName($args['ContentType'])]);
    }
}