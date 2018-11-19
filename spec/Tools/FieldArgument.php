<?php
namespace spec\BD\EzPlatformGraphQL\Tools;

use BD\EzPlatformGraphQL\Schema\Builder\Input;
use Prophecy\Argument\Token\CallbackToken;

class FieldArgument
{
    public static function hasName($name)
    {
        return self::has('name', $name);
    }

    public static function hasType($type)
    {
        return self::has('type', $type);
    }

    public static function hasDescription($description)
    {
        return self::has('description', $description);
    }

    private static function has($property, $value) {
        return new CallbackToken(
            function(Input\Field $field) use ($property, $value) {
                return $field->$property === $value;
            }
        );
    }
}