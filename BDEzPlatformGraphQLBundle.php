<?php
namespace BD\EzPlatformGraphQLBundle;

use BD\EzPlatformGraphQLBundle\DependencyInjection\Compiler;
use BD\EzPlatformGraphQLBundle\DependencyInjection\Security\PolicyProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BDEzPlatformGraphQLBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\FieldValueTypesPass());
        $container->addCompilerPass(new Compiler\FieldValueBuildersPass());
        $container->addCompilerPass(new Compiler\SchemaWorkersPass());
        $container->addCompilerPass(new Compiler\SchemaBuildersPass());

        $this->loadPolicyProviders($container);
    }

    private function loadPolicyProviders(ContainerBuilder $container)
    {
        $extension = $container->getExtension('ezpublish');
        // Add the policy provider.
        $extension->addPolicyProvider(new PolicyProvider());
    }
}
