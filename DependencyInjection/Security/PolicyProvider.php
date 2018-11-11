<?php
namespace BD\EzPlatformGraphQLBundle\DependencyInjection\Security;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Security\PolicyProvider\YamlPolicyProvider;

class PolicyProvider extends YamlPolicyProvider
{
    protected function getFiles()
    {
        return [__DIR__ . '/../../Resources/config/policies.yml'];
    }
}