<?php
namespace BD\EzPlatformGraphQLBundle\GraphQL\ExpressionLanguage\Access;

/**
 * Expression language function that checks if the current user has access to a policy.
 */
class EzCanUserFunction extends BaseAccessFunction
{
    public function __construct()
    {
        parent::__construct(
            'ezCanUser',
            function ($policy) {
                return $this->buildHasAccessCode([str_replace('"', '', $policy)]);
            }
        );
    }
}