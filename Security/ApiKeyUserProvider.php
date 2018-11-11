<?php
namespace BD\EzPlatformGraphQLBundle\Security;

use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\MVC\Symfony\Security\User as SecurityUser;
use eZ\Publish\API\Repository\Values\User\User as ApiUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiKeyUserProvider implements UserProviderInterface
{
    /**
     * @var Repository
     */
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function getUsernameForApiKey($apiKey)
    {
        $user = $this->findUserWithToken($apiKey);

        if ($user === null) {
            throw new CustomUserMessageAuthenticationException(
                sprintf('API Key "%s" does not exist.', $apiKey)
            );
        }

        return $user->login;
    }

    public function loadUserByUsername($username)
    {
        try {
            $user = new SecurityUser(
                $this->repository->getUserService()->loadUserByLogin($username),
                array('ROLE_USER')
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return SecurityUser::class === $class;
    }

    /**
     * @param string $apiKey
     * @return ApiUser\null
     */
    private function findUserWithToken($apiKey)
    {
        $filter = new Criterion\LogicalAnd([
            new Criterion\ContentTypeIdentifier('apikey'),
            new Criterion\Field('apikey', '=', $apiKey),
        ]);

        try {
            $apiKeyContent = $this->repository->sudo(
                function (Repository $repository) use ($filter) {
                    return $repository->getSearchService()->findSingle($filter);
                }
            );
            return $this->loadUserFromApiKeyContentInfo($apiKeyContent->contentInfo);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function loadUserFromApiKeyContentInfo(ContentInfo $contentInfo)
    {
        try {
            return $this->repository->sudo(
                function (Repository $repository) use ($contentInfo) {
                    $locationService = $repository->getLocationService();
                    return $repository->getUserService()->loadUser(
                        $locationService->loadLocation(
                            $locationService->loadLocation($contentInfo->mainLocationId)->parentLocationId
                        )->contentInfo->id
                    );
                }
            );
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException(
                sprintf('No User could be loaded for the API Key')
            );
        }
    }
}