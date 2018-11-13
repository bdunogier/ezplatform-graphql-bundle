<?php
namespace BD\EzPlatformGraphQLBundle\DependencyInjection\Compiler;

use BD\EzPlatformGraphQLBundle\Schema\Domain\Content\Worker\FieldDefinition\AddFieldDefinitionToDomainContentType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FieldValueBuildersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(AddFieldDefinitionToDomainContentType::class)) {
            return;
        }

        $definition = $container->findDefinition(AddFieldDefinitionToDomainContentType::class);
        $taggedServices = $container->findTaggedServiceIds('ezplatform_graphql.field_value_builder');

        $builders = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['type'])) {
                    throw new \InvalidArgumentException(
                        "The ezplatform_graphql.field_value_builder tag requires a 'type' property set to the Field Type's identifier"
                    );
                }

                $builders[$tag['type']] = new Reference($id);
            }
        }

        $definition->setArgument('$fieldValueBuilders', $builders);
    }
}