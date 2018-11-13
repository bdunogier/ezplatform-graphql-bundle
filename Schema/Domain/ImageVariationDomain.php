<?php
namespace BD\EzPlatformGraphQLBundle\Schema\Domain\ImagesVariations;

use BD\EzPlatformGraphQLBundle\Schema;
use BD\EzPlatformGraphQLBundle\Schema\Builder;
use BD\EzPlatformGraphQLBundle\Schema\Domain;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use Generator;

/**
 * Adds configured image variations to the ImageVariationIdentifier type.
 */
class ImageVariationDomain implements Domain\Iterator, Schema\Worker, Schema\Initializer
{
    const TYPE = 'ImageVariationIdentifier';
    const ARG = 'ImageVariation';

    /**
     * @var ConfigResolver
     */
    private $configResolver;

    public function __construct(ConfigResolver $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    public function iterate(): Generator
    {
        foreach ($this->configResolver->getParameter('image_variations') as $identifier => $variation) {
            yield [self::ARG => ['identifier' => $identifier, 'variation' => $variation]];
        }
    }

    public function init(Builder $schema)
    {
        $schema->addType(new Builder\Input\Type([
            'name' => self::TYPE,
            'type' => 'enum',
        ]));
    }

    public function work(Builder $schema, array $args)
    {
        $schema->addValueToEnum(self::TYPE, new Builder\Input\EnumValue([
            'name' => $args[self::ARG]['identifier']
        ]));
    }

    public function canWork(Builder $schema, array $args)
    {
        return isset($args[self::ARG]['identifier']);
    }
}