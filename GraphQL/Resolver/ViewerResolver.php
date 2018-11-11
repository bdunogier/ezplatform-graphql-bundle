<?php
namespace BD\EzPlatformGraphQLBundle\GraphQL\Resolver;

use eZ\Publish\API\Repository\Repository;

class ViewerResolver
{
    /**
     * @var Repository
     */
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function resolveViewer()
    {
        return $this->repository->sudo(
            function (Repository $repository) {
                return $repository->getUserService()->loadUser(
                    $repository->getPermissionResolver()->getCurrentUserReference()->getUserId()
                );
            }
        );
    }
}