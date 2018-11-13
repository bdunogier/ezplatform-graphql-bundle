<?php
namespace BD\EzPlatformGraphQLBundle\Schema;

interface Initializer
{
    public function init(Builder $schema);
}