<?php
namespace spec\BD\EzPlatformGraphQL\Tools;

use BD\EzPlatformGraphQL\Schema\Builder\Input;
use Prophecy\Argument\Token\CallbackToken;

class TypeArgument
{
    public static function isNamed($name) {
        return new CallbackToken(
            function (Input\Type $type) use($name) {
                return $type->name === $name;
            }
        );
    }

    public static function inherits($typeName) {
        return new CallbackToken(
            function (Input\type $type) use($typeName) {
                return in_array($typeName, $type->inherits);
            }
        );
    }
}