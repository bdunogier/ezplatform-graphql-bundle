<?php
namespace BD\EzPlatformGraphQLBundle\Schema;

use BD\EzPlatformGraphQLBundle\Schema\Builder\Input;

interface Builder
{
    public function getSchema(): array;

    public function addType(Input\Type $type);

    /**
     * @param string $type
     * @param Input\Field $field
     * @return void
     */
    public function addFieldToType($type, Input\Field $field);

    /**
     * @param string $type
     * @param string $field
     * @param Input\Arg $arg
     * @return void
     */
    public function addArgToField($type, $field, Input\Arg $arg);

    /**
     * @param string $enum
     * @param Input\EnumValue $arg
     * @return void
     */
    public function addValueToEnum($enum, Input\EnumValue $arg);

    /**
     * @param string $type
     * @return bool
     */
    public function hasType($type): bool;

    /**
     * @param string $type
     * @param string $field
     * @return bool
     */
    public function hasTypeWithField($type, $field): bool;

    /**
     * @param string $type
     * @param string $field
     * @param $arg
     * @return bool
     */
    public function hasTypeFieldWithArg($type, $field, $arg): bool;

    /**
     * @param string $enum
     * @return bool
     */
    public function hasEnum($enum): bool;
}