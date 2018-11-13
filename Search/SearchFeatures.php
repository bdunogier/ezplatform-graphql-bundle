<?php
namespace BD\EzPlatformGraphQLBundle\Search;

use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;

interface SearchFeatures
{
    /**
     * Tests if search supports field filtering on $fieldDefinition.
     *
     * @param FieldDefinition $fieldDefinition
     *
     * @return bool
     */
    public function supportsFieldCriterion(FieldDefinition $fieldDefinition);
}