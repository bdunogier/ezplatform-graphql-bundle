<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\EzPlatformGraphQLBundle\GraphQL\Resolver;

use BD\EzPlatformGraphQLBundle\Exception\UnsupportedFieldTypeException;
use BD\EzPlatformGraphQLBundle\GraphQL\InputMapper\SearchQueryMapper;
use BD\EzPlatformGraphQLBundle\GraphQL\Value\ContentFieldValue;
use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\Core\FieldType;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository;
use eZ\Publish\Core\FieldType\RichText\Converter;
use GraphQL\Error\UserError;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
     * @var Repository\Repository
     */
    private $repository;

    /**
     * @var Converter
     */
    private $richTextConverter;

    public function __construct(
        Repository\Repository $repository,
        TypeResolver $typeResolver,
        SearchQueryMapper $queryMapper,
        Converter $richTextConverter)
    {
        $this->repository = $repository;
        $this->typeResolver = $typeResolver;
        $this->queryMapper = $queryMapper;
        $this->richTextConverter = $richTextConverter;
    }

    public function resolveDomainContentItems($contentTypeIdentifier, $query = null)
    {
        return array_map(
            function (Repository\Values\Content\Content $content) {
                return $content->contentInfo;
            },
            $this->findContentItemsByTypeIdentifier($contentTypeIdentifier, $query)
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
        $queryArg = $args['query'];
        $queryArg['ContentTypeIdentifier'] = $contentTypeIdentifier;
        if (isset($args['sortBy'])) {
            $queryArg['sortBy'] = $args['sortBy'];
        }
        $args['query'] = $queryArg;

        $query = $this->queryMapper->mapInputToQuery($args['query']);
        $searchResults = $this->getSearchService()->findContent($query);

        return array_map(
            function (Repository\Values\Content\Search\SearchHit $searchHit) {
                return $searchHit->valueObject;
            },
            $searchResults->searchHits
        );
    }

    public function resolveDomainSearch()
    {
        $searchResults = $this->getSearchService()->findContentInfo(new Query([]));

        return array_map(
            function (Repository\Values\Content\Search\SearchHit $searchHit) {
                return $searchHit->valueObject;
            },
            $searchResults->searchHits
        );
    }

    public function resolveMainUrlAlias(ContentInfo $contentInfo)
    {
        $aliases = $this->repository->getURLAliasService()->listLocationAliases(
            $this->getLocationService()->loadLocation($contentInfo->mainLocationId),
            false
        );

        return isset($aliases[0]->path) ? $aliases[0]->path : null;
    }

    public function updateDomainContent($input, Argument $args, $language): Repository\Values\Content\ContentInfo
    {
        if (isset($args['id'])) {
            $idArray = GlobalId::fromGlobalId($args['id']);
            $contentId = $idArray['id'];
        } elseif (isset($args['contentId'])) {
            $contentId = $args['contentId'];
        } else {
            throw new UserError("One argument out of id or contentId is required");
        }

        try {
            $contentInfo = $this->getContentService()->loadContentInfo($contentId);
        } catch (Repository\Exceptions\NotFoundException $e) {
            throw new UserError("Content with id $contentId could not be loaded");
        } catch (Repository\Exceptions\UnauthorizedException $e) {
            throw new UserError("You are not authorized to load this content");
        }
        try {
            $contentType = $this->getContentTypeService()->loadContentType($contentInfo->contentTypeId);
        } catch (Repository\Exceptions\NotFoundException $e) {
            throw new UserError("Content type with id $contentInfo->contentTypeId could not be loaded");
        }
        $versionNo = $args['versionNo'] ?? null;

        $contentUpdateStruct = $this->getContentService()->newContentUpdateStruct();

        foreach ($contentType->getFieldDefinitions() as $fieldDefinition) {
            if (isset($input[$fieldDefinition->identifier])) {
                try {
                    $contentUpdateStruct->setField(
                        $fieldDefinition->identifier,
                        $this->getInputFieldValue($input, $fieldDefinition),
                        $language
                    );
                } catch (UnsupportedFieldTypeException $e) {
                    continue;
                }
            }
        }

        if (!isset($versionNo)) {
            try {
                $versionInfo = $this->getContentService()->createContentDraft($contentInfo)->versionInfo;
            } catch (Repository\Exceptions\UnauthorizedException $e) {
                throw new UserError("You are not authorized to create a draft of this content");
            }
        } else {
            try {
                $versionInfo = $this->getContentService()->loadVersionInfo($contentInfo, $versionNo);
            } catch (Repository\Exceptions\NotFoundException $e) {
                throw new UserError("Version $versionNo was not found");
            } catch (Repository\Exceptions\UnauthorizedException $e) {
                throw new UserError("You are not authorized to load this version");
            }
            if ($versionInfo->status !== Repository\Values\Content\VersionInfo::STATUS_DRAFT) {
                try {
                    $versionInfo = $this->getContentService()->createContentDraft($contentInfo, $versionNo)->versionInfo;
                } catch (Repository\Exceptions\UnauthorizedException $e) {
                    throw new UserError("You are not authorized to create a draft from this version");
                }
            }
        }

        try {
            $contentDraft = $this->getContentService()->updateContent($versionInfo, $contentUpdateStruct);
        } catch (ContentFieldValidationException $e) {
            throw new UserError("The given input did not validate: " . $e->getMessage());
        } catch (Repository\Exceptions\ContentValidationException $e) {
            throw new UserError("The given input did not validate: " . $e->getMessage());
        } catch (Repository\Exceptions\UnauthorizedException $e) {
            throw new UserError("You are not authorized to update this version");
        }
        try {
            $this->getContentService()->publishVersion($contentDraft->versionInfo);
        } catch (Repository\Exceptions\BadStateException $e) {
            return [];
        } catch (Repository\Exceptions\UnauthorizedException $e) {
            throw new UserError("You are not authorized to publish this version");
        }

        return $this->getContentService()->loadContent($contentDraft->id)->contentInfo;
    }

    public function createDomainContent($input, $contentTypeIdentifier, $parentLocationId, $language)
    {
        $contentType = $this->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);

        $contentCreateStruct = $this->getContentService()->newContentCreateStruct($contentType, $language);
        foreach ($contentType->getFieldDefinitions() as $fieldDefinition) {
            if (isset($input[$fieldDefinition->identifier])) {
                $fieldValue = $this->getInputFieldValue($input, $fieldDefinition);
                $contentCreateStruct->setField($fieldDefinition->identifier, $fieldValue, $language);
            }
        }

        try {
            $contentDraft = $this->getContentService()->createContent(
                $contentCreateStruct,
                [$this->getLocationService()->newLocationCreateStruct($parentLocationId)]
            );
        }
        catch (ContentFieldValidationException $e) {
            reset($e->getFieldErrors());
            $fieldError = current($e->getFieldErrors());
            $error = str_replace($fieldError['eng-GB'], array_keys($fieldError['values']), array_values($fieldError['values']));
            throw new UserError($error);
        }

        $content = $this->getContentService()->publishVersion($contentDraft->versionInfo);

        return $content->contentInfo;
    }

    public function deleteDomainContent(Argument $args)
    {
        $globalId = null;

        if (isset($args['id'])) {
            $globalId = $args['id'];
            $idArray = GlobalId::fromGlobalId($args['id']);
            $contentId = $idArray['id'];
        } elseif (isset($args['contentId'])) {
            $contentId = $args['contentId'];
        } else {
            throw new UserError("One argument out of id or contentId is required");
        }

        $contentInfo = $this->getContentService()->loadContentInfo($contentId);
        if (!isset($globalId)) {
            $globalId = GlobalId::toGlobalId(
                $this->resolveDomainContentType($contentInfo),
                $contentId
            );
        }
        
        // @todo check type of domain object

        $this->getContentService()->deleteContent($contentInfo);

        return [
            'id' => $globalId,
            'contentId' => $contentId,
        ];
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

    public function resolveDomainContentType(Repository\Values\Content\ContentInfo $contentInfo)
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

    private function makeDomainContentTypeName(Repository\Values\ContentType\ContentType $contentType)
    {
        $converter = new CamelCaseToSnakeCaseNameConverter(null, false);

        return $converter->denormalize($contentType->identifier) . 'Content';
    }

    public function resolveContentName(ContentInfo $contentInfo)
    {
        return $this->repository->getContentService()->loadContentByContentInfo($contentInfo)->getName();
    }

    private function getInputFieldValue($input, Repository\Values\ContentType\FieldDefinition $fieldDefinition)
    {
        $supportedInputTypes = ['ezstring', 'ezimage', 'eztext', 'ezrichtext', 'ezauthor'];
        if (!in_array($fieldDefinition->fieldTypeIdentifier, $supportedInputTypes)) {
            throw new UnsupportedFieldTypeException($fieldDefinition->fieldTypeIdentifier, 'input');
        }

        if ($fieldDefinition->fieldTypeIdentifier === 'ezauthor') {
            $authors = [];
            foreach ($input[$fieldDefinition->identifier] as $authorInput) {
                $authors[] = new FieldType\Author\Author($authorInput);
            }
            $fieldValue = new FieldType\Author\Value($authors);
        } elseif ($fieldDefinition->fieldTypeIdentifier === 'ezimage') {
            $fieldInput = $input[$fieldDefinition->identifier];

            if (!$fieldInput['file'] instanceof UploadedFile) {
                return null;
            }
            $file = $fieldInput['file'];
            $fieldValue = new FieldType\Image\Value([
                'alternativeText' => $fieldInput['alternativeText'] ?? '',
                'fileName' => $file->getClientOriginalName(),
                'inputUri' => $file->getPathname(),
                'fileSize' => $file->getSize(),
            ]);
        } elseif ($fieldDefinition->fieldTypeIdentifier === 'ezrichtext') {
            $format = $input[$fieldDefinition->identifier]['format'];
            $input = $input[$fieldDefinition->identifier]['input'];

            if ($format === 'html') {
                $input = <<<HTML5EDIT
<section xmlns="http://ez.no/namespaces/ezpublish5/xhtml5/edit">$input</section>
HTML5EDIT;

                $dom = new \DOMDocument();
                $dom->loadXML($input);
                $docbook = $this->richTextConverter->convert($dom);
                $fieldValue = new FieldType\RichText\Value($docbook);
            } elseif ($format === 'markdown') {
                $parseDown = new \Parsedown();
                $html = $parseDown->text($input);
                $input = <<<HTML5EDIT
<section xmlns="http://ez.no/namespaces/ezpublish5/xhtml5/edit">$html</section>
HTML5EDIT;
                $dom = new \DOMDocument();
                $dom->loadXML($input);
                $docbook = $this->richTextConverter->convert($dom);
                $fieldValue = new FieldType\RichText\Value($docbook);
            } elseif ($format === 'docbook') {
                $fieldValue = new FieldType\RichText\Value($input);
            } else {
                throw new UserError("Unsupported richtext input format $format");
            }
        } else {
            $fieldValue = $input[$fieldDefinition->identifier];
        }

        return $fieldValue;
    }

    /**
     * @return \eZ\Publish\API\Repository\ContentService
     */
    private function getContentService()
    {
        return $this->repository->getContentService();
    }

    /**
     * @return \eZ\Publish\API\Repository\LocationService
     */
    private function getLocationService()
    {
        return $this->repository->getLocationService();
    }

    /**
     * @return \eZ\Publish\API\Repository\ContentTypeService
     */
    private function getContentTypeService()
    {
        return $this->repository->getContentTypeService();
    }

    /**
     * @return \eZ\Publish\API\Repository\SearchService
     */
    private function getSearchService()
    {
        return $this->repository->getSearchService();
    }
}
