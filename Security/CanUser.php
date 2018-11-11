<?php
namespace BD\EzPlatformGraphQLBundle\Security;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\BadStateException;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use GraphQL\Error\UserError;

class CanUser
{
    /**
     * @var PermissionResolver
     */
    private $permissionResolver;

    const MODULE = 'graphql';

    const FUNCTION_CONTENT = 'content';
    /**
     * @var ContentTypeService
     */
    private $contentTypeService;

    public function __construct(ContentTypeService $contentTypeService, PermissionResolver $permissionResolver)
    {
        $this->permissionResolver = $permissionResolver;
        $this->contentTypeService = $contentTypeService;
    }

    public function viewContentOfType($identifier)
    {
        try {
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier($identifier);
        } catch (NotFoundException $e) {
            throw new UserError("Content type '$identifier' not found'");
        }

        $contentInfo = new ContentInfo(['contentTypeId' => $contentType->id]);
        try {
            return $this->permissionResolver->canUser(self::MODULE, self::FUNCTION_CONTENT, $contentInfo);
        } catch (BadStateException $e) {;
            throw new UserError($e->getMessage(), 0, $e);
        } catch (InvalidArgumentException $e) {
            throw new UserError($e->getMessage(), 0, $e);
        }
    }
}