<?php
namespace BD\EzPlatformGraphQL\Schema;

interface GroupProvider
{
    public function getGroups(array $args);
}