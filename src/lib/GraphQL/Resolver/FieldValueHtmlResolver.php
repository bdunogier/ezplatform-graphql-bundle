<?php
/**
 * Created by PhpStorm.
 * User: bdunogier
 * Date: 14/09/2018
 * Time: 12:55
 */

namespace BD\EzPlatformGraphQL\GraphQL\Resolver;

use BD\EzPlatformGraphQL\GraphQL\Value\ContentFieldValue;
use eZ\Publish\Core\MVC\Symfony\Templating\Twig\Extension\FieldRenderingExtension;
use Overblog\GraphQLBundle\Definition\Argument;

class FieldValueHtmlResolver
{
    /**
     * @var FieldRenderingExtension
     */
    private $fieldRenderer;

    public function __construct(FieldRenderingExtension $fieldRenderer)
    {
        $this->fieldRenderer = $fieldRenderer;
    }

    public function resolveFieldValueToHtml(ContentFieldValue $value, Argument $args)
    {
        return $this->fieldRenderer->renderField(
            $value->content,
            $value->fieldDefIdentifier,
            $args->getRawArguments());
    }
}