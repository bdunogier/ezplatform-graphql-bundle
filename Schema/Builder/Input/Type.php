<?php
namespace BD\EzPlatformGraphQLBundle\Schema\Builder\Input;

class Type extends Input
{
    public $name;
    public $type;
    public $inherits = [];
    public $interfaces = [];
}