<?php
namespace BD\EzPlatformGraphQLBundle\Search;

use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;

class SolrSearchFeatures implements SearchFeatures
{
    public function supportsFieldCriterion(FieldDefinition $fieldDefinition)
    {
        return true;
    }
}