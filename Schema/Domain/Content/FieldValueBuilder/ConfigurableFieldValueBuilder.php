<?php
namespace BD\EzPlatformGraphQLBundle\Schema\Domain\Content\FieldValueBuilder;

use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;

/**
 * A Field Value Builder that is configured through its constructor.
 * It requires a GraphQL type, and accepts an optional resolve string.
 */
class ConfigurableFieldValueBuilder implements FieldValueBuilder
{
    const RESOLVE_FIELD = '@=resolver("%resolver%", [value, "%field_def_identifier%"]).%property%';
    const DEFAULT_RESOLVER = 'DomainFieldValue';
    const DEFAULT_TYPE = 'String';
    const DEFAULT_PROPERTY = 'value';

    private $typesMap = [];

    public function __construct($typesMap = [])
    {
        foreach ($typesMap as $index => $config) {
            if (!is_array($config)) {
                $config = ['type' => $config];
            }

            if (!isset($config['resolve'])) {
                $config['resolve'] = $resolveField = str_replace(
                    [
                        '%resolver%',
                        '%property%'
                    ],
                    [
                        $config['resolver'] ?? self::DEFAULT_RESOLVER,
                        $config['property'] ?? self::DEFAULT_PROPERTY
                    ],
                    self::RESOLVE_FIELD
                );
            }

            $typesMap[$index] = $config;
        }

        $this->typesMap = $typesMap;
    }

    public function buildDefinition(FieldDefinition $fieldDefinition)
    {
        if (!isset($this->typesMap[$fieldDefinition->fieldTypeIdentifier])) {
            return $this->updateForField(
                ['type' => self::DEFAULT_TYPE, 'resolve' => self::DEFAULT_RESOLVER],
                $fieldDefinition
            );
        }

        return $this->updateForField($this->typesMap[$fieldDefinition->fieldTypeIdentifier], $fieldDefinition);
    }

    private function updateForField(array $configuration, FieldDefinition $fieldDefinition)
    {
        $configuration['resolve'] = str_replace(
            '%field_def_identifier%',
            $fieldDefinition->identifier,
            $configuration['resolve']
        );

        return $configuration;
    }
}