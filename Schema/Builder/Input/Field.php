<?php
namespace BD\EzPlatformGraphQLBundle\Schema\Builder\Input;

class Field extends Input
{
    public $name;
    public $description;
    public $type;
    public $resolve;
}