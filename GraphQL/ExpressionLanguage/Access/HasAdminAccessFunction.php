<?php
namespace BD\EzPlatformGraphQLBundle\GraphQL\ExpressionLanguage\Access;

class HasAdminAccessFunction extends BaseAccessFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasAdminAccess',
            function () {
                return $this->buildHasAccessCode(["section/view", "class/create", "role/read"]);
            }
        );
    }
}