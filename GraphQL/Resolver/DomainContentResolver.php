<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\EzPlatformGraphQLBundle\GraphQL\Resolver;

use BD\EzPlatformGraphQLBundle\GraphQL\InputMapper\SearchQueryMapper;
use BD\EzPlatformGraphQLBundle\GraphQL\Value\ContentFieldValue;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\FieldType;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use GraphQL\Error\UserError;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class DomainContentResolver
{
    /**
     * @var \Overblog\GraphQLBundle\Resolver\TypeResolver
     */
    private $typeResolver;

    /**
     * @var SearchQueryMapper
     */
    private $queryMapper;

    /**
     * @var Repository
     */
    private $repository;

    public function __construct(
        Repository $repository,
        TypeResolver $typeResolver,
        SearchQueryMapper $queryMapper)
    {
        $this->typeResolver = $typeResolver;
        $this->queryMapper = $queryMapper;
        $this->repository = $repository;
    }

    public function resolveDomainContentItems($contentTypeIdentifier, $args = null)
    {
        return array_map(
            function (Content $content) {
                return $content->contentInfo;
            },
            $this->findContentItemsByTypeIdentifier($contentTypeIdentifier, $args)
        );
    }

    /**
     * Resolves a domain content item by id, and checks that it is of the requested type.
     */
    public function resolveDomainContentItem(Argument $args, $contentTypeIdentifier)
    {
        if (isset($args['id'])) {
            $contentInfo = $this->getContentService()->loadContentInfo($args['id']);
        } elseif (isset($args['remoteId'])) {
            $contentInfo = $this->getContentService()->loadContentInfoByRemoteId($args['remoteId']);
        } elseif (isset($args['locationId'])) {
            $contentInfo = $this->getLocationService()->loadLocation($args['locationId'])->contentInfo;
        }

        // @todo consider optimizing using a map of contentTypeId
        $contentType = $this->getContentTypeService()->loadContentType($contentInfo->contentTypeId);

        if ($contentType->identifier !== $contentTypeIdentifier) {
            throw new UserError("Content $contentInfo->id is not of type '$contentTypeIdentifier'");
        }

        return $contentInfo;
    }

    /**
     * @param string $contentTypeIdentifier
     *
     * @param \Overblog\GraphQLBundle\Definition\Argument $args
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    private function findContentItemsByTypeIdentifier($contentTypeIdentifier, Argument $args): array
    {
        $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);
        $fieldsArgument = [];
        foreach ($args->getRawArguments() as $argument => $value) {
            if (($fieldDefinition = $contentType->getFieldDefinition($argument)) === null) {
                continue;
            }

            if (!$fieldDefinition->isSearchable) {
                continue;
            }

            $fieldFilter = $this->buildFieldFilter($argument, $value);
            if ($fieldFilter !== null) {
                $fieldsArgument[] = $fieldFilter;
            }
        }

        $queryArg = [];
        $queryArg['ContentTypeIdentifier'] = $contentTypeIdentifier;
        $queryArg['Fields'] = $fieldsArgument;
        $args['query'] = $queryArg;

        $query = $this->queryMapper->mapInputToQuery($args['query']);
        $searchResults = $this->getSearchService()->findContent($query);

        return array_map(
            function (SearchHit $searchHit) {
                return $searchHit->valueObject;
            },
            $searchResults->searchHits
        );
    }

    public function resolveDomainSearch()
    {
        $searchResults = $this->getSearchService()->findContentInfo(new Query([]));

        return array_map(
            function (SearchHit $searchHit) {
                return $searchHit->valueObject;
            },
            $searchResults->searchHits
        );
    }

    public function resolveDomainFieldValue($contentInfo, $fieldDefinitionIdentifier)
    {
        $content = $this->getContentService()->loadContent($contentInfo->id);

        return new ContentFieldValue([
            'contentTypeId' => $contentInfo->contentTypeId,
            'fieldDefIdentifier' => $fieldDefinitionIdentifier,
            'content' => $content,
            'value' => $content->getFieldValue($fieldDefinitionIdentifier)
        ]);
    }

    public function resolveDomainRelationFieldValue($contentInfo, $fieldDefinitionIdentifier, $multiple = false)
    {
        $content = $this->getContentService()->loadContent($contentInfo->id);
        // @todo check content type
        $fieldValue = $content->getFieldValue($fieldDefinitionIdentifier);

        if (!$fieldValue instanceof FieldType\RelationList\Value) {
            throw new UserError("$fieldDefinitionIdentifier is not a RelationList field value");
        }

        if ($multiple) {
            return array_map(
                function ($contentId) {
                    return $this->getContentService()->loadContentInfo($contentId);
                },
                $fieldValue->destinationContentIds
            );
        } else {
            return
                isset($fieldValue->destinationContentIds[0])
                ? $this->getContentService()->loadContentInfo($fieldValue->destinationContentIds[0])
                : null;
        }
    }

    public function ResolveDomainContentType(ContentInfo $contentInfo)
    {
        static $contentTypesMap = [], $contentTypesLoadErrors = [];

        if (!isset($contentTypesMap[$contentInfo->contentTypeId])) {
            try {
                $contentTypesMap[$contentInfo->contentTypeId] = $this->getContentTypeService()->loadContentType($contentInfo->contentTypeId);
            } catch (\Exception $e) {
                $contentTypesLoadErrors[$contentInfo->contentTypeId] = $e;
                throw $e;
            }
        }

        return $this->makeDomainContentTypeName($contentTypesMap[$contentInfo->contentTypeId]);
    }

    private function makeDomainContentTypeName(ContentType $contentType)
    {
        $converter = new CamelCaseToSnakeCaseNameConverter(null, false);

        return $converter->denormalize($contentType->identifier) . 'Content';
    }

    private function getContentService()
    {
        return $this->repository->getContentService();
    }

    private function getLocationService()
    {
        return $this->repository->getLocationService();
    }

    private function getContentTypeService()
    {
        return $this->repository->getContentTypeService();
    }

    private function getSearchService()
    {
        return $this->repository->getSearchService();
    }

    private function buildFieldFilter($fieldDefinitionIdentifier, $value)
    {
        if (count($value) === 1) {
            $value = $value[0];
        }
        if (is_array($value)) {
            $operator = 'in';
            // @todo if 3 items, and first item is 'between', use next two items as value
        } else if (is_string($value)) {
            if ($value{0} === '~') {
                $value = substr($value, 1);
                $operator = 'like';
                if (strpos($value, '%') === false) {
                    $value = "%$value%";
                }
            } elseif ($value{0} === '<') {
                $value = substr($value, 1);
                if ($value{1} === '=') {
                    $operator = 'lte';
                    $value = substr($value, 2);
                } else {
                    $value = substr($value, 1);
                    $operator = 'lt';
                }
            } elseif ($value{0} === '<') {
                $value = substr($value, 1);
                if ($value{1} === '=') {
                    $operator = 'gte';
                    $value = substr($value, 2);
                } else {
                    $operator = 'gt';
                    $value = substr($value, 1);
                }
            } else {
                $operator = 'eq';
            }
        }

        return ['target' => $fieldDefinitionIdentifier, $operator => trim($value)];
    }
}
