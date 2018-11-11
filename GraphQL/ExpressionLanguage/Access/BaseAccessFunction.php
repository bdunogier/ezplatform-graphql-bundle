<?php
namespace BD\EzPlatformGraphQLBundle\GraphQL\ExpressionLanguage\Access;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

abstract class BaseAccessFunction extends ExpressionFunction
{
    protected function buildHasAccessCode(array $policies)
    {
        $checks = array_map(
            function($policy) {
                list($module, $function) = explode('/', $policy);
                return sprintf(
                    '(true === ($access = $pr->hasAccess("%s", "%s")) || is_array($access))',
                    $module,
                    $function
                );
            },
            $policies
        );

        return sprintf('(function() use ($globalVariable) {
  $pr = $globalVariable->get("container")->get("eZ\Publish\API\Repository\PermissionResolver");
  return %s;
})()', implode('||', $checks));
    }

}