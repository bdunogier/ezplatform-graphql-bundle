<?php
namespace BD\EzPlatformGraphQLBundle\DependencyInjection\Factory;

use BD\EzPlatformGraphQLBundle\Search\SearchFeatures;
use eZ\Bundle\EzPublishCoreBundle\ApiLoader\RepositoryConfigurationProvider;

class SearchFeaturesFactory
{
    /**
     * @var RepositoryConfigurationProvider
     */
    private $configurationProvider;

    /**
     * @var SearchFeatures[]
     */
    private $searchFeatures = [];

    public function __construct(RepositoryConfigurationProvider $configurationProvider, array $searchFeatures)
    {
        $this->configurationProvider = $configurationProvider;
        $this->searchFeatures = $searchFeatures;
    }

    public function build()
    {
        $searchEngine = $this->configurationProvider->getRepositoryConfig()['search']['engine'];

        if (isset($this->searchFeatures[$searchEngine])) {
            return $this->searchFeatures[$searchEngine];
        } else {
            throw new \InvalidArgumentException("Search engine not found");
        }
    }
}