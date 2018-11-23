<?php
namespace BD\EzPlatformGraphQLBundle\Schema;

interface GroupProvider
{
    public function getGroups(array $args);
}