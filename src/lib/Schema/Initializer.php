<?php
namespace BD\EzPlatformGraphQL\Schema;

interface Initializer
{
    public function init(Builder $schema);
}