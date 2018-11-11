<?php
namespace BD\EzPlatformGraphQLBundle\GraphQL\ExpressionLanguage\Access;

/**
 * Expression language function that checks if the current user has access to at least one
 * of the given policies, with or without limitations.
 */
class HasEzAccessToOneOfFunction extends BaseAccessFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasEzAccessToOneOf',
            function () {
                return $this->buildHasAccessCode(
                    array_map(
                        function($value) {
                            return str_replace('"', '', $value);
                        },
                        func_get_args()
                    )
                );
            }
        );
    }
}